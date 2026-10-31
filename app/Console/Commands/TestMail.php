<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestMail extends Command
{
    protected $signature = 'mail:test {recipient : Email address to receive the test} {--mailer=smtp : Configured SMTP mailer to test}';

    protected $description = 'Check effective mail settings and send a test through SMTP';

    public function handle(): int
    {
        $recipient = $this->argument('recipient');
        $mailer = $this->option('mailer');
        $settings = config('mail.mailers', [])[$mailer] ?? null;

        $this->table(['Setting', 'Effective value'], [
            ['Environment file', app()->environmentFilePath()],
            ['Configuration cached', app()->configurationIsCached() ? 'yes' : 'no'],
            ['Default mailer', config('mail.default')],
            ['Test mailer', $mailer],
            ['SMTP host', $settings['host'] ?? '(unset)'],
            ['SMTP port', $settings['port'] ?? '(unset)'],
            ['SMTP scheme', $settings['scheme'] ?? '(automatic)'],
            ['SMTP URL override', empty($settings['url']) ? 'no' : 'yes'],
            ['Queue connection', config('queue.default')],
        ]);

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid recipient email address.');

            return self::FAILURE;
        }

        if (($settings['transport'] ?? null) !== 'smtp') {
            $this->error('Select an SMTP mailer. Log, array, and failover mailers cannot verify SMTP delivery.');

            return self::FAILURE;
        }

        if (config('mail.default') !== $mailer) {
            $this->warn('Application emails use a different default mailer. Update MAIL_MAILER in the environment file above, then run php artisan config:clear.');
        }

        try {
            Mail::mailer($mailer)->raw('AFYA SMTP test. This message was sent immediately, without a queue worker.', function ($message) use ($recipient) {
                $message->to($recipient)->subject('AFYA SMTP delivery test');
            });
        } catch (Throwable) {
            // Transport errors can contain credentials or server responses. Do not print them.
            $this->error('SMTP handoff failed. Verify the effective host, port, scheme, mailbox credentials, and sender in your environment file.');
            $this->line('If settings differ from the saved file, run php artisan config:clear and check for duplicate entries or server environment overrides.');

            return self::FAILURE;
        }

        $this->info('SMTP handoff completed. This does not confirm inbox delivery.');
        $this->line('Check the recipient inbox and spam folder, then the sending provider delivery report (cPanel: Email > Track Delivery).');

        return self::SUCCESS;
    }
}
