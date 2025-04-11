<?php

namespace Tests\Feature\Filament\Resources;

use App\Filament\Resources\MessageResource;
use App\Filament\Resources\MessageResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\Admin;
use App\Models\Message;
use App\Models\SignUp;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithLivewire;

class MessageResourceTest extends TestCase
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
            ->get(MessageResource::getUrl())
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_list_messages(): void
    {
        $messages = Message::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->assertCanSeeTableRecords($messages);
    }

    #[Test]
    public function it_can_view_messages(): void
    {
        $this
            ->get(
                MessageResource::getUrl(
                    name      : 'view',
                    parameters: [
                        'record' => Message::factory()->create(),
                    ]
                )
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_delete_messages(): void
    {
        $message = Message::factory()->create();

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->callTableAction(Tables\Actions\DeleteAction::class, $message);

        $this->assertModelMissing($message);
    }

    #[Test]
    public function it_can_bulk_delete_messages(): void
    {
        $messages = Message::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->callTableBulkAction(Tables\Actions\DeleteAction::class, $messages);

        $messages->each(fn(Message $message) => $this->assertModelMissing($message));
    }

    #[Test]
    public function it_has_a_form(): void
    {
        $message = Message::factory()->create();

        $this->livewire
            ->test(
                name  : Pages\ViewMessage::class,
                params: [
                    'record' => $message->id,
                ]
            )
            ->assertFormExists()
            ->assertFormFieldExists('email', function (TextInput $field): bool {
                return $field->isDisabled() && $field->isRequired();
            })
            ->assertFormFieldExists('created_at', function (TextInput $field): bool {
                return $field->isDisabled() && $field->isVisible();
            })
            ->assertFormFieldExists('message', function (Textarea $field): bool {
                return
                    $field->isDisabled() &&
                    $field->isRequired() &&
                    $field->shouldAutosize() &&
                    $field->getRows() === 3 &&
                    $field->getColumnSpan('default') === 'full';
            });
    }

    #[Test]
    public function it_can_get_eloquent_query_with_eager_loads(): void
    {
        $this->assertArrayHasKey('user', MessageResource::getEloquentQuery()->getEagerLoads());
    }

    #[Test]
    public function it_has_expected_table_columns(): void
    {
        $messages = Message::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->assertCanRenderTableColumn('id')
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('user.email')
            ->assertCanRenderTableColumn('created_at')
            ->assertSee($messages->map(fn(Message $message) => UserResource::getUrl('view', ['record' => $message->user->id]))->toArray());
    }

    #[Test]
    #[DataProvider('sortable')]
    public function it_can_sort_the_table(string $column): void
    {
        $messages = Message::factory()
            ->count(5)
            ->create();

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->sortTable($column)
            ->assertCanSeeTableRecords($messages->sortBy($column), inOrder: true)
            ->sortTable($column, 'desc')
            ->assertCanSeeTableRecords($messages->sortByDesc($column), inOrder: true);
    }

    #[Test]
    #[DataProvider('searchable')]
    public function it_can_search_the_table(string $column): void
    {
        $messages = Message::factory()
            ->count(5)
            ->create();

        $value = $messages->first()->$column;

        $this->livewire
            ->test(Pages\ListMessages::class)
            ->searchTable($value)
            ->assertCanSeeTableRecords($messages->where($column, '=', $value))
            ->assertCanNotSeeTableRecords($messages->where($column, '!=', $value));
    }

    #[Test]
    #[DataProvider('filterable')]
    public function it_can_filter_the_table_by_created_at(bool $from, bool $until): void
    {
        /** @var Collection<int, SignUp> $messages */
        $messages = Message::factory()
            ->count(5)
            ->create();

        $data = [
            'from'  => $from
                ? Message::query()
                    ->oldest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
            'until' => $until
                ? Message::query()
                    ->latest()
                    ->first()
                    ->created_at
                    ->toDateString()
                : null,
        ];

        $table = $this->livewire
            ->test(Pages\ListMessages::class)
            ->assertCanSeeTableRecords($messages)
            ->filterTable(
                name: 'created_at',
                data: $data
            );

        if ($from && !$until) {
            $table->assertCanSeeTableRecords(
                records: $messages->toQuery()
                    ->whereDate('created_at', '>=', $data['from'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $messages->toQuery()
                    ->whereDate('created_at', '<', $data['from'])
                    ->get()
            );
        }

        if (!$from && $until) {
            $table->assertCanSeeTableRecords(
                records: $messages
                    ->toQuery()
                    ->whereDate('created_at', '<=', $data['until'])
                    ->get()
            );

            $table->assertCanNotSeeTableRecords(
                records: $messages
                    ->toQuery()
                    ->whereDate('created_at', '>', $data['until'])
                    ->get()
            );
        }

        if ($from && $until) {
            $table->assertCanSeeTableRecords(
                records: $messages
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
            'id'      => ['id'],
            'name'    => ['name'],
            'email'   => ['email'],
            'message' => ['message'],
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

