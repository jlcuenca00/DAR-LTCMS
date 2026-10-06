# Production backup failure email

The nightly job can email the responsible operator when the backup script exits with an error. Successful backups do not send mail. Recipient configuration is stored privately on the server, outside the repository.

After deployment, create `~/.config/dar-ltcms/backup-alert-email` containing one confirmed email address and set its permissions to 600. Test delivery with:

```bash
cd /home/darltcms/htdocs/darltcms.me
php scripts/send_production_backup_alert.php --test
```

Confirm inbox receipt. Then replace the existing nightly backup cron command (preserving the 18:30 UTC schedule) with:

```cron
30 18 * * * /usr/bin/bash /home/darltcms/htdocs/darltcms.me/scripts/run_production_backup_with_alert.sh
```

The wrapper retains `darltcms-backup` logs, preserves a failed backup's exit status even if email delivery also fails, and logs alert-delivery failure separately. Configure one backup cron entry only.

This uses the application's existing email service. It cannot notify if the server, cron scheduler, or email provider is down, and is not an independent missed-backup monitor. Check recent snapshots and the restore procedure during turnover. Alert messages omit raw logs and credentials. Test mode sends a clearly labeled test message without running or failing a backup.
