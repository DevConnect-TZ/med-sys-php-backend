# SMTP setup on cPanel

Production mail settings belong in the deployed backend's `.env`, not in Git.
Never paste a full `.env` into a support message. Replace any disclosed mailbox password.

1. In cPanel, open **Email Accounts > Connect Devices** for your sending mailbox.
   Use the outgoing hostname, port, full mailbox username, and mailbox password shown there.
2. Locate the environment file used by the running backend:

   ```bash
   php artisan tinker --execute="dump(app()->environmentFilePath());"
   ```

3. Edit that exact file. Replace existing mail settings and remove duplicate entries.
   For a provider specifying implicit TLS on port 465:

   ```dotenv
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=your-outgoing-mail-server
   MAIL_PORT=465
   MAIL_USERNAME="your-mailbox@example.com"
   MAIL_PASSWORD="your-mailbox-password"
   MAIL_FROM_ADDRESS="your-mailbox@example.com"
   MAIL_FROM_NAME="AFYA Medical Center"
   ```

   For STARTTLS on port 587, use `MAIL_SCHEME=smtp` and `MAIL_PORT=587`.
   Use your provider's settings; do not disable certificate verification.
   If `MAIL_URL` exists, remove it unless you intentionally use it to override SMTP settings.

4. Reload configuration and run the SMTP test:

   ```bash
   php artisan config:clear
   php artisan mail:test recipient@example.com
   ```

   The command prints effective settings without printing credentials, sender addresses,
   recipient addresses, or SMTP URLs. It sends immediately through SMTP, even if the
   default mailer is `log`, and warns when application emails use a different mailer.
   Success means SMTP handoff completed, not that the recipient inbox received the message.
   Check **Email > Track Delivery** if mail is handled by the cPanel server, or your
   external SMTP provider's delivery report otherwise.

   If the effective host remains `127.0.0.1:2525`, the edited settings are not active.
   Check the displayed file path, duplicate keys, and hosting environment overrides.
   Run `php artisan config:cache` only after verifying the settings.

## Queued notifications

Appointment, invoice, prescription, and laboratory emails use the configured queue.
With `QUEUE_CONNECTION=database`, run a worker using your hosting provider's process
manager. On shared hosting without a persistent worker, a cPanel cron job can run:

```bash
cd /absolute/path/to/backend && /absolute/path/to/php artisan queue:work --stop-when-empty --tries=3 --timeout=60
```

Schedule every minute and prevent overlapping runs using your host's locking support.
Ensure the PHP CLI version matches the application and the jobs/failed_jobs tables
have been migrated. Check failed jobs with `php artisan queue:failed`. Review failures
before retrying them. A queue worker does not fix SMTP credentials or log-only mail.
Restart persistent workers after configuration changes with `php artisan queue:restart`.

Password reset and invitation emails currently send immediately. Invitation creation
can report success even if sending fails; inspect the backend logs for that failure.

## Frontend links

Set `FRONTEND_URL` in the deployed environment to the frontend's HTTPS origin.
Then refresh Laravel's configuration. Password reset and invitation links use
`config('app.frontend_url')`, so they continue working with cached configuration.
