<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Admin;
use App\Models\SignUp;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithLivewire;

class UserResourceTest extends TestCase
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
            ->get(UserResource::getUrl())
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_list_users(): void
    {
        $users = User::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    #[Test]
    public function it_can_view_users(): void
    {
        $this
            ->get(
                UserResource::getUrl(
                    name      : 'view',
                    parameters: [
                        'record' => User::factory()->create(),
                    ]
                )
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_has_a_form(): void
    {
        $user = User::factory()->create();

        $this->livewire
            ->test(
                name  : Pages\ViewUser::class,
                params: [
                    'record' => $user->id,
                ]
            )
            ->assertFormExists()
            ->assertFormFieldExists('email', fn(TextInput $field): bool => $field->isDisabled() && $field->isRequired())
            ->assertFormFieldExists('created_at', fn(TextInput $field): bool => $field->isDisabled());
    }

    #[Test]
    public function it_can_get_eloquent_query_with_counts(): void
    {
        $this->assertEquals(
            User::query()
                ->withCount([
                    'signups',
                    'messages',
                ])
                ->toRawSql(),
            UserResource::getEloquentQuery()->toRawSql(),
        );
    }

    #[Test]
    #[DataProvider('managers')]
    public function it_renders_relation_manager(string $relation, string $manager): void
    {
        $user = User::factory()
            ->has(SignUp::factory(), $relation)
            ->create();

        $this->livewire
            ->test(
                name  : $manager,
                params: [
                    'ownerRecord' => $user,
                    'pageClass'   => Pages\ViewUser::class,
                ]
            )
            ->assertSuccessful()
            ->assertCanSeeTableRecords($user->$relation);
    }

    #[Test]
    public function it_has_expected_table_columns(): void
    {
        User::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListUsers::class)
            ->assertCanRenderTableColumn('id')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('created_at')
            ->assertCanRenderTableColumn('signups_count')
            ->assertCanRenderTableColumn('messages_count');
    }

    #[Test]
    #[DataProvider('sortable')]
    public function it_can_sort_the_table(string $column): void
    {
        $users = User::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListUsers::class)
            ->sortTable($column)
            ->assertCanSeeTableRecords($users->sortBy($column), inOrder: true)
            ->sortTable($column, 'desc')
            ->assertCanSeeTableRecords($users->sortByDesc($column), inOrder: true);
    }

    #[Test]
    #[DataProvider('searchable')]
    public function it_can_search_the_table(string $column): void
    {
        $users = User::factory()
            ->count(5)
            ->create();

        $value = $users->first()->$column;

        $this->livewire
            ->test(Pages\ListUsers::class)
            ->searchTable($value)
            ->assertCanSeeTableRecords($users->where($column, '=', $value))
            ->assertCanNotSeeTableRecords($users->where($column, '!=', $value));
    }

    #[Test]
    #[DataProvider('filterable')]
    public function it_can_filter_the_table_by_created_at(bool $from, bool $until): void
    {
        /** @var Collection<int, User> $users */
        $users = User::factory()
            ->count(5)
            ->create();

        $data = [
            'from'  => $from
                ? User::query()
                    ->oldest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
            'until' => $until
                ? User::query()
                    ->latest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
        ];

        $table = $this->livewire
            ->test(Pages\ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->filterTable(
                name: 'created_at',
                data: $data
            );

        if ($from && !$until) {
            $table->assertCanSeeTableRecords(
                records: $users->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $users->toQuery()
                    ->whereDate('created_at', '<', $data['from'])
                    ->get()
            );
        }

        if (!$from && $until) {
            $table->assertCanSeeTableRecords(
                records: $users
                    ->toQuery()
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $users
                    ->toQuery()
                    ->whereDate('created_at', '>', $data['until'])
                    ->get()
            );
        }

        if ($from && $until) {
            $table->assertCanSeeTableRecords(
                records: $users
                    ->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );
        }
    }

    /**
     * Set of Filament Relation Managers.
     */
    public static function managers(): array
    {
        return [
            'signups'  => [
                'relation' => 'signups',
                'manager'  => RelationManagers\SignupsRelationManager::class,
            ],
            'messages' => [
                'relation' => 'messages',
                'manager'  => RelationManagers\MessagesRelationManager::class,
            ],
        ];
    }

    /**
     * Set of sortable Filament table columns.
     */
    public static function sortable(): array
    {
        return [
            'id'         => ['id'],
            'created at' => ['created_at'],
            'signups'    => ['signups_count'],
            'messages'   => ['messages_count'],
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

