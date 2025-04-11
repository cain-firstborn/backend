<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\SignUpResource;
use App\Filament\Resources\SignUpResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\Admin;
use App\Models\SignUp;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithLivewire;

class SignUpResourceTest extends TestCase
{
    use WithLivewire;
    use LazilyRefreshDatabase;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Admin::factory()->create());
    }

    #[Test]
    public function it_can_render_page(): void
    {
        $this
            ->get(SignUpResource::getUrl())
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_list_signups(): void
    {
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->assertCanSeeTableRecords($signups);
    }

    #[Test]
    public function it_can_delete_signups(): void
    {
        $signup = SignUp::factory()->create();

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->callTableAction(Tables\Actions\DeleteAction::class, $signup);

        $this->assertModelMissing($signup);
    }

    #[Test]
    public function it_can_bulk_delete_signups(): void
    {
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->callTableBulkAction(Tables\Actions\DeleteAction::class, $signups);

        $signups->each(fn(SignUp $signup) => $this->assertModelMissing($signup));
    }

    #[Test]
    public function it_can_get_eloquent_query_with_eager_loads(): void
    {
        $this->assertArrayHasKey('user', SignUpResource::getEloquentQuery()->getEagerLoads());
    }

    #[Test]
    public function it_has_expected_table_columns(): void
    {
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->assertCanRenderTableColumn('id')
            ->assertCanRenderTableColumn('user.email')
            ->assertCanRenderTableColumn('created_at')
            ->assertSee($signups->map(fn(SignUp $signup) => UserResource::getUrl('view', ['record' => $signup->user->id]))->toArray());
    }

    #[Test]
    #[DataProvider('sortable')]
    public function it_can_sort_the_table(string $column): void
    {
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->sortTable($column)
            ->assertCanSeeTableRecords($signups->sortBy($column), inOrder: true)
            ->sortTable($column, 'desc')
            ->assertCanSeeTableRecords($signups->sortByDesc($column), inOrder: true);
    }

    #[Test]
    #[DataProvider('searchable')]
    public function it_can_search_the_table(string $column): void
    {
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $value = $signups->first()->$column;

        $this->livewire
            ->test(Pages\ListSignUps::class)
            ->searchTable($value)
            ->assertCanSeeTableRecords($signups->where($column, '=', $value))
            ->assertCanNotSeeTableRecords($signups->where($column, '!=', $value));
    }

    #[Test]
    #[DataProvider('filterable')]
    public function it_can_filter_the_table_by_created_at(bool $from, bool $until): void
    {
        /** @var Collection<int, SignUp> $signups */
        $signups = SignUp::factory()
            ->count(5)
            ->create();

        $data = [
            'from'  => $from
                ? SignUp::query()
                    ->oldest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
            'until' => $until
                ? SignUp::query()
                    ->latest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
        ];

        $table = $this->livewire
            ->test(Pages\ListSignUps::class)
            ->assertCanSeeTableRecords($signups)
            ->filterTable(
                name: 'created_at',
                data: $data
            );

        if ($from && !$until) {
            $table->assertCanSeeTableRecords(
                records: $signups->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $signups->toQuery()
                    ->whereDate('created_at', '<', $data['from'])
                    ->get()
            );
        }

        if (!$from && $until) {
            $table->assertCanSeeTableRecords(
                records: $signups
                    ->toQuery()
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $signups
                    ->toQuery()
                    ->whereDate('created_at', '>', $data['until'])
                    ->get()
            );
        }

        if ($from && $until) {
            $table->assertCanSeeTableRecords(
                records: $signups
                    ->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );
        }
    }

    /**
     * Set of sortable Filament table columns.
     */
    public static function sortable(): array
    {
        return [
            'id'         => ['id'],
            'created at' => ['created_at'],
        ];
    }

    /**
     * Set of searchable Filament table columns.
     */
    public static function searchable(): array
    {
        return [
            'id'    => ['id'],
            'email' => ['email'],
        ];
    }

    /**
     * Set of filterable data for Filament filter.
     */
    public static function filterable(): array
    {
        return [
            'from'           => [
                'from'  => true,
                'until' => false,
            ],
            'until'          => [
                'from'  => false,
                'until' => true,
            ],
            'from and until' => [
                'from'  => true,
                'until' => true,
            ],
        ];
    }
}

