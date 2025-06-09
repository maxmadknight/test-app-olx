<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Subscription;
use App\Notifications\EmailConfirmationNotification;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class EmailConfirmationNotificationTest extends TestCase
{
    public function test_notification_contains_correct_content(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $token     = 'verification-token-123';
        $expiresAt = Carbon::now()->addHours(24);

        $subscription                     = new Subscription;
        $subscription->email              = $email;
        $subscription->verification_token = $token;
        $subscription->token_expires_at   = $expiresAt;

        $notification = new EmailConfirmationNotification;

        // Act
        $mailMessage = $notification->toMail($subscription);

        // Assert
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Please confirm your email address', $mailMessage->subject);

        // Check that the mail message contains all required lines
        $content = $this->getMailMessageContent($mailMessage);
        $this->assertStringContainsString('Thank you for using our application', $content);
        $this->assertStringContainsString('To complete your subscription', $content);
        $this->assertStringContainsString('This verification link will expire', $content);
        $this->assertStringContainsString('If you did not create an account', $content);

        // Check that the action button exists with correct text
        $this->assertEquals('Confirm Email Address', $mailMessage->actionText);
        $this->assertNotEmpty($mailMessage->actionUrl);
        $this->assertStringContainsString('api/verify', $mailMessage->actionUrl);
        $this->assertStringContainsString('token='.$token, $mailMessage->actionUrl);
        $this->assertStringContainsString('email='.urlencode($email), $mailMessage->actionUrl);
    }

    public function test_notification_channels(): void
    {
        // Arrange
        $notification = new EmailConfirmationNotification;

        // Act
        $channels = $notification->via(null);

        // Assert
        $this->assertEquals(['mail'], $channels);
    }

    public function test_notification_to_array(): void
    {
        // Arrange
        $email     = 'test@example.com';
        $token     = 'verification-token-123';
        $expiresAt = Carbon::now()->addHours(24);

        $subscription                     = new Subscription;
        $subscription->email              = $email;
        $subscription->verification_token = $token;
        $subscription->token_expires_at   = $expiresAt;

        $notification = new EmailConfirmationNotification;

        // Act
        $array = $notification->toArray($subscription);

        // Assert
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('token', $array);
        $this->assertArrayHasKey('expires_at', $array);
        $this->assertEquals($email, $array['email']);
        $this->assertEquals($token, $array['token']);
        // Don't compare the exact timestamp, just check it's a Carbon instance
        $this->assertInstanceOf(Carbon::class, $array['expires_at']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Define the route for testing
        $this->app['router']->get('api/verify', fn () => 'verification page')->name('verify-email');
    }

    /**
     * Helper method to extract content from MailMessage
     */
    private function getMailMessageContent(MailMessage $message): string
    {
        $content = '';

        // Include intro lines
        foreach ($message->introLines as $line) {
            $content .= $line.' ';
        }

        // Include outro lines
        foreach ($message->outroLines as $line) {
            $content .= $line.' ';
        }

        return $content;
    }
}
