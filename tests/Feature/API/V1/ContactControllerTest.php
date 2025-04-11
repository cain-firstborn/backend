<?php

namespace Tests\Feature\API\V1;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\ContactSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\WithCache;
use Tests\Traits\WithTranslator;

class ContactControllerTest extends TestCase
{
    use WithCache;
    use WithTranslator;
    use LazilyRefreshDatabase;

    /**
     * API Endpoint used in tests.
     *
     * @var string
     */
    private string $uri = '/v1/contact';

    /**
     * Data to be used in the tests.
     */
    private array $data = [
        'name'    => 'test',
        'email'   => 'test@example.com',
        'message' => 'test',
    ];

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Admin::factory()->support()->create();
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function it_validates_the_request(string $field, mixed $value, string $rule, array $replace = [], int $status = Response::HTTP_UNPROCESSABLE_ENTITY): void
    {
        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    $field => $value,
                ]
            )
            ->assertSee($this->translator->get("validation.$rule", ['attribute' => $field, ...$replace]))
            ->assertStatus($status);
    }

    #[Test]
    public function it_creates_new_user_and_caches_it_if_not_exists(): void
    {
        $this->assertDatabaseMissing('users', $this->data);
        $this->assertFalse($this->cache->has("user:{$this->data['email']}"));

        DB::enableQueryLog();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data,
            )
            ->assertSee($this->translator->get('response.contact.submitted'))
            ->assertCreated();

        DB::disableQueryLog();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => $this->data['email']]);
        $this->assertTrue($this->cache->has("user:{$this->data['email']}"));
        $this->assertEquals($this->data['email'], $this->cache->get("user:{$this->data['email']}")->email);
        $this->assertContains('insert into "users" ("email", "updated_at", "created_at") values (?, ?, ?)', array_column(DB::getQueryLog(), 'query'));
    }

    #[Test]
    public function it_retrieves_user_from_cache_if_exists(): void
    {
        $this->cache->forever(
            key  : "contact:{$this->data['email']}",
            value: User::factory()->create(['email' => $this->data['email']])
        );

        DB::enableQueryLog();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data
            )
            ->assertSee($this->translator->get('response.contact.submitted'))
            ->assertCreated();

        DB::disableQueryLog();

        $this->assertNotContains('insert into "users" ("email", "updated_at", "created_at") values (?, ?, ?)', array_column(DB::getQueryLog(), 'query'));
    }

    #[Test]
    public function it_creates_new_message(): void
    {
        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data
            )
            ->assertCreated();

        $user = User::query()->firstWhere('email', $this->data['email']);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'name'    => $this->data['name'],
            'message' => $this->data['message'],
        ]);

        $this->assertTrue($user->messages->isNotEmpty());
    }

    #[Test]
    public function it_sends_a_notification_to_support(): void
    {
        Notification::fake();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data
            )
            ->assertCreated();

        Notification::assertSentTo(Admin::support(), ContactSubmitted::class);
    }

    #[Test]
    public function it_handles_concurrent_signup_attempts_properly(): void
    {
        Notification::fake();

        for ($i = 0; $i < 6; $i++) {
            $this
                ->json(
                    method: 'POST',
                    uri   : $this->uri,
                    data  : $this->data
                )
                ->assertStatus($i < 5 ? Response::HTTP_CREATED : Response::HTTP_TOO_MANY_REQUESTS);
        }

        Notification::assertSentToTimes(Admin::support(), ContactSubmitted::class, 5);
    }

    /**
     * Set of failed validation scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'name is required'                         => [
                'field' => 'name',
                'value' => null,
                'rule'  => 'required',
            ],
            'name has to be string'                    => [
                'field' => 'name',
                'value' => 1,
                'rule'  => 'string',
            ],
            'name has to be minimum 3 characters'      => [
                'field'   => 'name',
                'value'   => Str::random(2),
                'rule'    => 'min.string',
                'replace' => ['min' => 3],
            ],
            'name has to be maximum 50 characters'     => [
                'field'   => 'name',
                'value'   => Str::random(51),
                'rule'    => 'max.string',
                'replace' => ['max' => 50],
            ],
            'email is required'                        => [
                'field' => 'email',
                'value' => null,
                'rule'  => 'required',
            ],
            'email has to be email'                    => [
                'field' => 'email',
                'value' => 'test',
                'rule'  => 'email',
            ],
            'message is required'                      => [
                'field' => 'message',
                'value' => null,
                'rule'  => 'required',
            ],
            'message has to be string'                 => [
                'field' => 'message',
                'value' => 1,
                'rule'  => 'string',
            ],
            'message has to be minimum 3 characters'   => [
                'field'   => 'message',
                'value'   => Str::random(2),
                'rule'    => 'min.string',
                'replace' => ['min' => 3],
            ],
            'message has to be maximum 200 characters' => [
                'field'   => 'message',
                'value'   => Str::random(201),
                'rule'    => 'max.string',
                'replace' => ['max' => 200],
            ],
        ];
    }
}
