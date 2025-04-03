<?php

namespace Tests\Unit\Models;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_has_expected_columns_in_database(): void
    {
        $this->assertTrue(
            Schema::hasColumns('messages', [
                'id',
                'user_id',
                'name',
                'message',
                'created_at',
            ])
        );
    }

    #[Test]
    public function it_returns_correct_attributes(): void
    {
        $message = Message::factory()->create()->fresh();

        $this->assertEquals(
            [
                'id',
                'user_id',
                'name',
                'message',
                'created_at',
            ],
            array_keys($message->toArray())
        );
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user    = User::factory()->create();
        $message = Message::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $message->user);
    }
}
