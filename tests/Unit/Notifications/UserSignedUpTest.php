<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\UserSignedUp;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSignedUpTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('scenarios')]
    public function it_renders_a_notification(string $locale): void
    {
        $this->app->setLocale($locale);

        $user         = User::factory()->make();
        $notification = new UserSignedUp();
        $mail         = $notification->toMail($user);
        $content      = strip_tags($mail->render());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertEquals(trans('notification.signup.subject'), $mail->subject);
        $this->assertStringContainsString(trans('notification.hello'), $content);
        $this->assertStringContainsString(strip_tags(trans('notification.signup.content')), $content);
        $this->assertStringContainsString(trans('notification.regards'), $content);
        $this->assertStringContainsString(trans('notification.rights'), $content);
    }

    /**
     * Set of locale scenarios.
     *
     * @return array<array-key, array>
     */
    public static function scenarios(): array
    {
        return [
            'english' => ['en'],
            'german'  => ['de'],
            'french'  => ['fr'],
            'italian' => ['it'],
        ];
    }
}
