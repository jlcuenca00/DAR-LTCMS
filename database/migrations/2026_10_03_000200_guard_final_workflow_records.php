<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION dar_ltcms_guard_final_application() RETURNS trigger AS $$
            DECLARE
                excluded text[] := ARRAY['workflow_revision', 'updated_at'];
                old_release text;
                new_release text;
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    IF OLD.status IN ('approved', 'not_approved', 'released', 'denied') THEN
                        RAISE EXCEPTION 'Finalized applications cannot be deleted.';
                    END IF;
                    RETURN OLD;
                END IF;

                IF NEW.workflow_revision < OLD.workflow_revision THEN
                    RAISE EXCEPTION 'Workflow revision cannot decrease.';
                END IF;

                IF OLD.status IN ('not_approved', 'released', 'denied')
                    AND (to_jsonb(NEW) - excluded) IS DISTINCT FROM (to_jsonb(OLD) - excluded) THEN
                    RAISE EXCEPTION 'Historical finalized applications are immutable.';
                END IF;

                IF OLD.status = 'approved' THEN
                    IF (to_jsonb(NEW) - (excluded || ARRAY[
                        'release_status', 'ready_for_release_at', 'released_at', 'released_by',
                        'release_recipient_name', 'release_logbook_reference', 'csm_status',
                        'date_of_clearance_release'
                    ])) IS DISTINCT FROM (to_jsonb(OLD) - (excluded || ARRAY[
                        'release_status', 'ready_for_release_at', 'released_at', 'released_by',
                        'release_recipient_name', 'release_logbook_reference', 'csm_status',
                        'date_of_clearance_release'
                    ])) THEN
                        RAISE EXCEPTION 'Approved application decision fields are immutable.';
                    END IF;

                    IF (to_jsonb(NEW) - excluded) IS DISTINCT FROM (to_jsonb(OLD) - excluded) THEN
                        old_release := COALESCE(OLD.release_status, 'not_ready');
                        new_release := COALESCE(NEW.release_status, 'not_ready');
                        IF old_release = 'not_ready' AND new_release = 'ready_for_release' THEN
                            IF NEW.ready_for_release_at IS NULL OR
                                (to_jsonb(NEW) - (excluded || ARRAY['release_status', 'ready_for_release_at']))
                                IS DISTINCT FROM
                                (to_jsonb(OLD) - (excluded || ARRAY['release_status', 'ready_for_release_at'])) THEN
                                RAISE EXCEPTION 'Invalid Ready for Release mutation.';
                            END IF;
                        ELSIF old_release = 'ready_for_release' AND new_release = 'released' THEN
                            IF NEW.released_at IS NULL OR NEW.released_by IS NULL OR
                                NULLIF(btrim(NEW.release_recipient_name), '') IS NULL OR
                                NEW.date_of_clearance_release IS NULL OR
                                (to_jsonb(NEW) - (excluded || ARRAY[
                                    'release_status', 'released_at', 'released_by', 'release_recipient_name',
                                    'release_logbook_reference', 'csm_status', 'date_of_clearance_release'
                                ])) IS DISTINCT FROM (to_jsonb(OLD) - (excluded || ARRAY[
                                    'release_status', 'released_at', 'released_by', 'release_recipient_name',
                                    'release_logbook_reference', 'csm_status', 'date_of_clearance_release'
                                ])) THEN
                                RAISE EXCEPTION 'Invalid Released to Client mutation.';
                            END IF;
                        ELSE
                            RAISE EXCEPTION 'Release tracking cannot skip, reverse, or rewrite a recorded stage.';
                        END IF;
                    END IF;
                END IF;

                IF (to_jsonb(NEW) - excluded) IS DISTINCT FROM (to_jsonb(OLD) - excluded) THEN
                    NEW.workflow_revision := GREATEST(NEW.workflow_revision, OLD.workflow_revision + 1);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER workflow_final_application_guard
            BEFORE UPDATE OR DELETE ON land_transfer_applications
            FOR EACH ROW EXECUTE FUNCTION dar_ltcms_guard_final_application();

            CREATE OR REPLACE FUNCTION dar_ltcms_guard_application_child() RETURNS trigger AS $$
            DECLARE
                parent_id bigint;
                parent_status text;
            BEGIN
                IF TG_OP = 'UPDATE' AND NEW.land_transfer_application_id IS DISTINCT FROM OLD.land_transfer_application_id THEN
                    RAISE EXCEPTION 'Application child records cannot be reassigned.';
                END IF;
                parent_id := CASE WHEN TG_OP = 'DELETE' THEN OLD.land_transfer_application_id ELSE NEW.land_transfer_application_id END;
                SELECT status INTO parent_status FROM land_transfer_applications WHERE id = parent_id FOR UPDATE;
                IF parent_status IN ('approved', 'not_approved', 'released', 'denied') THEN
                    RAISE EXCEPTION 'Finalized application child records are immutable.';
                END IF;

                IF TG_TABLE_NAME = 'application_compliance_notices' THEN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'Compliance history cannot be deleted.';
                    ELSIF TG_OP = 'UPDATE' THEN
                        IF OLD.resolved_at IS NOT NULL OR
                            NEW.resolved_at IS NULL OR NEW.resolved_by IS NULL OR
                            (to_jsonb(NEW) - ARRAY['resolved_at', 'resolved_by', 'resolved_by_name_snapshot', 'resolution_note', 'updated_at'])
                            IS DISTINCT FROM
                            (to_jsonb(OLD) - ARRAY['resolved_at', 'resolved_by', 'resolved_by_name_snapshot', 'resolution_note', 'updated_at']) THEN
                            RAISE EXCEPTION 'Compliance requests are immutable and may only be resolved once.';
                        END IF;
                    END IF;
                END IF;
                IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION dar_ltcms_bump_child_workflow_revision() RETURNS trigger AS $$
            BEGIN
                UPDATE land_transfer_applications
                SET workflow_revision = workflow_revision + 1
                WHERE id = CASE WHEN TG_OP = 'DELETE' THEN OLD.land_transfer_application_id ELSE NEW.land_transfer_application_id END;
                RETURN NULL;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach (['application_parcels', 'application_documents', 'application_compliance_notices'] as $table) {
            DB::unprepared("CREATE TRIGGER workflow_child_guard BEFORE INSERT OR UPDATE OR DELETE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION dar_ltcms_guard_application_child();
                CREATE TRIGGER workflow_child_revision AFTER INSERT OR UPDATE OR DELETE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION dar_ltcms_bump_child_workflow_revision();");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }
        foreach (['application_parcels', 'application_documents', 'application_compliance_notices'] as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS workflow_child_guard ON {$table};
                DROP TRIGGER IF EXISTS workflow_child_revision ON {$table};");
        }
        DB::unprepared('DROP TRIGGER IF EXISTS workflow_final_application_guard ON land_transfer_applications;
            DROP FUNCTION IF EXISTS dar_ltcms_guard_final_application();
            DROP FUNCTION IF EXISTS dar_ltcms_guard_application_child();
            DROP FUNCTION IF EXISTS dar_ltcms_bump_child_workflow_revision();');
    }
};
