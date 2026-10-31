<?php

namespace Tests\Feature;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TestMailCommandTest extends TestCase
{
    public function test_log_mailer_cannot_report_smtp_success(): void
    {
        Mail::shouldReceive('mailer')->never();

        $this->artisan('mail:test', ['recipient' => 'recipient@example.com', '--mailer' => 'log'])
            ->expectsOutputToContain('Select an SMTP mailer')
            ->assertExitCode(1);
    }

    public function test_invalid_recipient_does_not_attempt_sending(): void
    {
        Mail::shouldReceive('mailer')->never();

        $this->artisan('mail:test', ['recipient' => 'invalid'])
            ->expectsOutputToContain('Provide a valid recipient')
            ->assertExitCode(1);
    }

    public function test_success_reports_handoff_and_warns_about_default_mailer(): void
    {
        config(['mail.default' => 'log']);
        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('raw')->once()->withArgs(function ($text, $callback) {
            $message = Mockery::mock();
            $message->shouldReceive('to')->once()->with('recipient@example.com')->andReturnSelf();
            $message->shouldReceive('subject')->once()->with('AFYA SMTP delivery test')->andReturnSelf();
            $callback($message);

            return str_contains($text, 'without a queue worker');
        });
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);

        $this->artisan('mail:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('Application emails use a different default mailer')
            ->expectsOutputToContain('SMTP handoff completed. This does not confirm inbox delivery.')
            ->assertExitCode(0);
    }

    public function test_transport_failure_returns_error_without_printing_server_response(): void
    {
        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('raw')->once()->andThrow(new \RuntimeException('private-server-response'));
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);

        $this->artisan('mail:test', ['recipient' => 'recipient@example.com'])
            ->expectsOutputToContain('SMTP handoff failed')
            ->doesntExpectOutputToContain('private-server-response')
            ->assertExitCode(1);
    }
}
