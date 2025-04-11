<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithLivewire;

class AdminResourceTest extends TestCase
{
    use WithLivewire;
    use WithFaker;
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
            ->get(AdminResource::getUrl())
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_list_admins(): void
    {
        $admins = Admin::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->assertCanSeeTableRecords($admins);
    }

    #[Test]
    public function it_can_edit_admins(): void
    {
        $this
            ->get(
                AdminResource::getUrl(
                    name      : 'edit',
                    parameters: [
                        'record' => Admin::factory()->create(),
                    ]
                )
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_delete_admins(): void
    {
        $admin = Admin::factory()->create();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->callTableAction(Tables\Actions\DeleteAction::class, $admin);

        $this->assertModelMissing($admin);
    }

    #[Test]
    public function it_can_create_admins()
    {
        $admin = Admin::factory()->make();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->callAction('create', [
                'name'     => $admin->name,
                'email'    => $admin->email,
                'password' => $admin->password,
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseCount('admins', 2);
        $this->assertDatabaseHas('admins', $admin->getAttributes());
    }

    #[Test]
    public function it_can_update_admins()
    {
        $admin = Admin::factory()->create();

        $this->livewire
            ->test(
                name  : Pages\EditAdmin::class,
                params: [
                    'record' => $admin->id,
                ]
            )
            ->set('data.name', $name = $this->faker->name)
            ->set('data.email', $email = $this->faker->email)
            ->set('data.password', $password = $this->faker->password)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admins', [
            'name'  => $name,
            'email' => $email,
        ]);

        $this->assertTrue(Hash::check($password, $admin->refresh()->password));
    }

    #[Test]
    public function it_can_bulk_delete_admins(): void
    {
        $admins = Admin::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->callTableBulkAction(Tables\Actions\DeleteAction::class, $admins);

        $admins->each(fn(Admin $admin) => $this->assertModelMissing($admin));
    }

    #[Test]
    public function it_has_expected_table_columns(): void
    {
        Admin::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->assertCanRenderTableColumn('id')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('created_at');
    }

    #[Test]
    #[DataProvider('sortable')]
    public function it_can_sort_the_table(string $column): void
    {
        $admins = Admin::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->sortTable($column)
            ->assertCanSeeTableRecords($admins->sortBy($column), inOrder: true)
            ->sortTable($column, 'desc')
            ->assertCanSeeTableRecords($admins->sortByDesc($column), inOrder: true);
    }

    #[Test]
    #[DataProvider('searchable')]
    public function it_can_search_the_table(string $column): void
    {
        $admins = Admin::factory()
            ->count(5)
            ->create();

        $value = $admins->first()->$column;

        $this->livewire
            ->test(Pages\ListAdmins::class)
            ->searchTable($value)
            ->assertCanSeeTableRecords($admins->where($column, '=', $value))
            ->assertCanNotSeeTableRecords($admins->where($column, '!=', $value));
    }

    #[Test]
    #[DataProvider('filterable')]
    public function it_can_filter_the_table_by_created_at(bool $from, bool $until): void
    {
        /** @var Collection<int, Admin> $admins */
        $admins = Admin::factory()
            ->count(5)
            ->create();

        $data = [
            'from'  => $from
                ? Admin::query()
                    ->oldest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
            'until' => $until
                ? Admin::query()
                    ->latest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
        ];

        $table = $this->livewire
            ->test(Pages\ListAdmins::class)
            ->assertCanSeeTableRecords($admins)
            ->filterTable(
                name: 'created_at',
                data: $data
            );

        if ($from && !$until) {
            $table->assertCanSeeTableRecords(
                records: $admins->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $admins->toQuery()
                    ->whereDate('created_at', '<', $data['from'])
                    ->get()
            );
        }

        if (!$from && $until) {
            $table->assertCanSeeTableRecords(
                records: $admins
                    ->toQuery()
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $admins
                    ->toQuery()
                    ->whereDate('created_at', '>', $data['until'])
                    ->get()
            );
        }

        if ($from && $until) {
            $table->assertCanSeeTableRecords(
                records: $admins
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
            'name'  => ['name'],
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

