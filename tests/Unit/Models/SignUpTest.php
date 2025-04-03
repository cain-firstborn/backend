<?php

namespace Tests\Unit\Models;

use App\Models\SignUp;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_has_expected_columns_in_database(): void
    {
        $this->assertTrue(
            Schema::hasColumns('sign_ups', [
                'id',
                'user_id',
                'created_at',
            ])
        );
    }

    #[Test]
    public function it_returns_correct_attributes(): void
    {
        $singing = SignUp::factory()->create()->fresh();

        $this->assertEquals(
            [
                'id',
                'user_id',
                'created_at',
            ],
            array_keys($singing->toArray())
        );
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user    = User::factory()->create();
        $singing = SignUp::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $singing->user);
    }
}
