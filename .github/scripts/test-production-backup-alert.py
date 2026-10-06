"""Exercise the real cron wrapper with isolated failing/succeeding tools."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[2] / 'scripts/run_production_backup_with_alert.sh'


class BackupAlertTest(unittest.TestCase):
    def run_wrapper(self, backup_status=0, mail_status=0, logger_status=0):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / 'scripts').mkdir()
            (root / 'bin').mkdir()
            shutil.copyfile(SCRIPT, root / 'scripts/run.sh')
            (root / 'scripts/backup_dar_ltcms_production.sh').write_text(
                f'echo backup-output; exit {backup_status}\n')
            for name, body in {
                'php': f'echo alert >> "$HOME/calls"; exit {mail_status}',
                'logger': f'cat >/dev/null; exit {logger_status}',
            }.items():
                path = root / 'bin' / name
                path.write_text('#!/bin/bash\n' + body + '\n')
                path.chmod(0o700)
            environment = dict(os.environ, HOME=str(root))
            result = subprocess.run(['bash', str(root / 'scripts/run.sh')],
                                    env=environment, capture_output=True, text=True, timeout=10)
            calls = (root / 'calls').read_text() if (root / 'calls').exists() else ''
            return result, calls

    def test_success_sends_no_mail(self):
        result, calls = self.run_wrapper()
        self.assertEqual(0, result.returncode)
        self.assertEqual('', calls)

    def test_failure_sends_one_alert_and_preserves_exit(self):
        result, calls = self.run_wrapper(backup_status=7)
        self.assertEqual(7, result.returncode)
        self.assertEqual('alert\n', calls)

    def test_mail_failure_is_visible_without_masking_backup_failure(self):
        result, calls = self.run_wrapper(backup_status=7, mail_status=3)
        self.assertEqual(7, result.returncode)
        self.assertEqual('alert\n', calls)
        self.assertIn('ALERT DELIVERY FAILED', result.stderr)

    def test_logging_failure_is_not_reported_as_backup_success(self):
        result, calls = self.run_wrapper(logger_status=4)
        self.assertEqual(4, result.returncode)
        self.assertEqual('', calls)


if __name__ == '__main__':
    unittest.main()
