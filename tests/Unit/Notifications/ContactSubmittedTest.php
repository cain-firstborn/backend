<?php

namespace Tests\Unit\Notifications;

use App\Models\Message;
use App\Notifications\ContactSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithTranslator;

class ContactSubmittedTest extends TestCase
{
    use WithTranslator;
    use LazilyRefreshDatabase;

    #[Test]
    #[DataProvider('scenarios')]
    public function it_renders_a_notification(string $locale): void
    {
        $this->app->setLocale($locale);

        $message      = Message::factory()->make();
        $notification = new ContactSubmitted($message);
        $mail         = $notification->toMail();
        $content      = strip_tags($mail->render());

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertEquals(strip_tags($this->translator->get('notification.contact.subject', ['name' => $message->name])), $mail->subject);
        $this->assertStringContainsString(strip_tags($this->translator->get('notification.contact.greeting')), $content);

        foreach ($message->only('name', 'message') as $attribute) {
            $this->assertStringContainsString($attribute, $content);
        }

        $this->assertStringNotContainsString($this->translator->get('notification.regards'), $content);
        $this->assertStringNotContainsString($this->translator->get('notification.rights'), $content);
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
