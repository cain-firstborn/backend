<?php

namespace Tests\Unit\Models;

use App\Models\SignUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function it_has_expected_columns_in_database(): void
    {
        $this->assertTrue(
            Schema::hasColumns('users', [
                'id',
                'email',
                'created_at',
                'updated_at',
            ])
        );
    }

    #[Test]
    public function it_returns_correct_attributes(): void
    {
        $user = User::factory()->create()->fresh();

        $this->assertEquals(
            [
                'id',
                'email',
                'created_at',
                'updated_at',
            ],
            array_keys($user->toArray())
        );
    }

    #[Test]
    public function it_has_many_signups(): void
    {
        $user   = User::factory()->create();
        $singup = SignUp::factory()->for($user)->create();

        $this->assertTrue($user->signups->contains($singup));
        $this->assertEquals(1, $user->signups->count());
        $this->assertInstanceOf(Collection::class, $user->signups);
    }
}
