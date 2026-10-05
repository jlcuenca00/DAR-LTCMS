"""Run the real backup script with isolated stub tools; no database or network."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[2] / "scripts/backup_dar_ltcms_production.sh"


class ProductionBackupTest(unittest.TestCase):
    def run_backup(self, options, fail_backup=False, fail_resolver=False):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / "scripts").mkdir()
            shutil.copyfile(SCRIPT, root / "scripts/backup.sh")
            (root / "bin").mkdir()
            calls = root / "calls"
            tools = {
                "php": "if [ \"$FAIL_RESOLVER\" = 1 ]; then printf partial; exit 1; fi; printf '%s\\0' 'PGHOST=resolved-host' 'PGPORT=5544' 'PGDATABASE=resolved-db' 'PGUSER=resolved-user' 'PGPASSWORD=test password' 'PGSSLMODE=verify-full' 'PGSSLROOTCERT=/test/root cert' 'PGSSLCERT=' 'PGSSLKEY='",
                "pg_dump": 'printf "%s\\n" "target:$PGHOST:$PGPORT:$PGDATABASE:$PGUSER:$PGSSLMODE:$PGSSLROOTCERT" >> "$CALLS"; for arg in "$@"; do case "$arg" in --file=*) printf dump > "${arg#--file=}";; esac; done',
                "pg_restore": "exit 0",
                "psql": "printf 0",
                "restic": 'printf "%s\\n" "$*" >> "$CALLS"; if [ "$1" = backup ] && [ "$FAIL_BACKUP" = 1 ]; then exit 1; fi',
            }
            for name, body in tools.items():
                tool = root / "bin" / name
                tool.write_text("#!/bin/bash\n" + body + "\n")
                tool.chmod(0o700)
            password = root / "password"
            password.write_text("testing-only")
            password.chmod(0o600)
            config = root / "backup.env"
            config.write_text(
                'RESTIC_REPOSITORY="s3:https://example.invalid/test"\n'
                + f'RESTIC_PASSWORD_FILE="{password}"\n'
                + 'AWS_ACCESS_KEY_ID=test\nAWS_SECRET_ACCESS_KEY=test\n'
                + 'DB_CONNECTION=pgsql\nDB_HOST=localhost\nDB_PORT=5432\n'
                + 'DB_DATABASE=test\nDB_USERNAME=test\nDB_PASSWORD=test\n'
                + f'BACKUP_STAGING_DIR="{root}/stage"\n'
                + options
            )
            config.chmod(0o600)
            environment = {k: v for k, v in os.environ.items()
                           if not k.startswith(("BACKUP_", "RESTIC_", "DB_", "AWS_"))}
            environment.update(HOME=str(root), BACKUP_ENV_FILE=str(config),
                               CALLS=str(calls), FAIL_BACKUP="1" if fail_backup else "0",
                               FAIL_RESOLVER="1" if fail_resolver else "0")
            result = subprocess.run(["bash", str(root / "scripts/backup.sh")],
                                    env=environment, capture_output=True, text=True, timeout=15)
            self.assertFalse((root / "stage/database-settings.tmp").exists())
            self.assertFalse((root / "stage/database.dump").exists())
            self.assertFalse((root / "stage/database.dump.tmp").exists())
            self.assertFalse((root / "stage/backup-manifest.txt").exists())
            return result, calls.read_text() if calls.exists() else ""

    def test_configuration_overrides_reach_restic(self):
        result, calls = self.run_backup(
            "BACKUP_KEEP_DAILY=2\nBACKUP_KEEP_WEEKLY=3\nBACKUP_KEEP_MONTHLY=1\n"
            "BACKUP_CHECK_PERCENT=9\nBACKUP_HOST=custom-host\nBACKUP_TAG=custom-tag\n"
        )
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn("target:resolved-host:5544:resolved-db:resolved-user:verify-full:/test/root cert", calls)
        self.assertIn("--host custom-host --tag custom-tag", calls)
        self.assertIn("--keep-daily 2 --keep-weekly 3 --keep-monthly 1", calls)
        self.assertIn("check --read-data-subset=9%", calls)

    def test_defaults_and_explicit_sampling_skip(self):
        result, calls = self.run_backup("BACKUP_CHECK_PERCENT=0\n")
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn("--keep-daily 7 --keep-weekly 4 --keep-monthly 3", calls)
        self.assertNotIn("check --read-data-subset", calls)

    def test_resolver_failure_stops_before_dump_or_upload(self):
        result, calls = self.run_backup("", fail_resolver=True)
        self.assertNotEqual(0, result.returncode)
        self.assertEqual("", calls)

    def test_upload_failure_does_not_prune_and_cleans_dump(self):
        result, calls = self.run_backup("", fail_backup=True)
        self.assertNotEqual(0, result.returncode)
        self.assertNotIn("forget ", calls)


if __name__ == "__main__":
    unittest.main()
