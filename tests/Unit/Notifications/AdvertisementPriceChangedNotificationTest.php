<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Advertisement;
use App\Models\Subscription;
use App\Notifications\AdvertisementPriceChangedNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class AdvertisementPriceChangedNotificationTest extends TestCase
{
    public function test_notification_contains_correct_content(): void
    {
        // Arrange
        $oldPrice    = 100;
        $oldCurrency = 'USD';
        $newPrice    = 150;
        $newCurrency = 'USD';
        $url         = 'https://example.com/ad/123';

        $advertisement           = new Advertisement;
        $advertisement->price    = $newPrice;
        $advertisement->currency = $newCurrency;
        $advertisement->url      = $url;

        $subscription = new Subscription;

        $notification = new AdvertisementPriceChangedNotification(
            $advertisement,
            $oldPrice,
            $oldCurrency
        );

        // Act
        $mailMessage = $notification->toMail($subscription);

        // Assert
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Price change on OLX ad', $mailMessage->subject);

        // Check that the mail message contains all required lines
        $content = $this->getMailMessageContent($mailMessage);
        $this->assertStringContainsString('Price for advertisement was changed', $content);
        $this->assertStringContainsString('Advertisement: '.$url, $content);
        $this->assertStringContainsString('Old price: '.$oldPrice.' '.$oldCurrency, $content);
        $this->assertStringContainsString('New price: '.$newPrice.' '.$newCurrency, $content);

        // Check that the action button exists with correct text and URL
        $this->assertEquals('View advertisement', $mailMessage->actionText);
        $this->assertEquals($url, $mailMessage->actionUrl);
    }

    public function test_notification_channels(): void
    {
        // Arrange
        $advertisement = new Advertisement;
        $oldPrice      = 100;
        $oldCurrency   = 'USD';
        $notification  = new AdvertisementPriceChangedNotification(
            $advertisement,
            $oldPrice,
            $oldCurrency
        );

        // Act
        $channels = $notification->via(null);

        // Assert
        $this->assertEquals(['mail'], $channels);
    }

    public function test_notification_to_array(): void
    {
        // Arrange
        $advertisement           = new Advertisement;
        $advertisement->id       = 1;
        $advertisement->price    = 150;
        $advertisement->currency = 'USD';
        $advertisement->url      = 'https://example.com/ad/123';

        $oldPrice    = 100;
        $oldCurrency = 'USD';

        $notification = new AdvertisementPriceChangedNotification(
            $advertisement,
            $oldPrice,
            $oldCurrency
        );

        // Act
        $array = $notification->toArray(new Subscription);

        // Assert
        $this->assertEquals([
            'advertisement_id' => 1,
            'old_price'        => $oldPrice,
            'old_currency'     => $oldCurrency,
            'new_price'        => 150,
            'new_currency'     => 'USD',
            'url'              => 'https://example.com/ad/123',
        ], $array);
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
