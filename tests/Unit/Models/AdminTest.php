<?php

namespace Tests\Unit\Models;

use App\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithConfig;

class AdminTest extends TestCase
{
    use WithConfig;
    use LazilyRefreshDatabase;

    #[Test]
    public function it_has_expected_columns_in_database(): void
    {
        $this->assertTrue(
            Schema::hasColumns('admins', [
                'id',
                'name',
                'email',
                'password',
                'remember_token',
                'created_at',
                'updated_at',
            ])
        );
    }

    #[Test]
    public function it_returns_correct_attributes(): void
    {
        $admin = Admin::factory()->create()->fresh();

        $this->assertEquals(
            [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
            array_keys($admin->toArray())
        );

        $this->assertNotEquals(
            [
                'password',
                'remember_token',
            ],
            array_keys($admin->toArray())
        );
    }

    #[Test]
    public function it_returns_support_admin(): void
    {
        $admin = Admin::factory()->support()->create();

        $this->assertTrue($admin->is(Admin::support()));
    }
}
