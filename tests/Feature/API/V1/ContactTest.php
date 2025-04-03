<?php

namespace Tests\Feature\API\V1;

use App\Models\User;
use App\Notifications\ContactSubmitted;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ContactTest extends TestCase
{
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
     * Test Cache instance.
     */
    private CacheManager $cache;

    /**
     * Set up the test environment.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = app()->make(CacheManager::class);

        Notification::fake();
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
            ->assertSee(trans("validation.$rule", ['attribute' => $field, ...$replace]))
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
            ->assertSee(trans('response.contact.submitted'))
            ->assertCreated();

        DB::disableQueryLog();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => $this->data['email']]);
        $this->assertTrue($this->cache->has("user:{$this->data['email']}"));
        $this->assertEquals($this->data['email'], Cache::get("user:{$this->data['email']}")->email);
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
            ->assertSee(trans('response.contact.submitted'))
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
    public function it_does_not_creates_new_message_during_cooldown(): void
    {
        $this->cache->put(
            key  : "contact_cooldown:{$this->data['email']}",
            value: true,
            ttl  : now()->addDay()
        );

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data
            )
            ->assertCreated();

        $this->assertDatabaseCount('messages', 0);
    }

    #[Test]
    public function it_creates_new_signup_after_cooldown_expires(): void
    {
        $this->it_creates_new_message();

        $this->travel(1)->day();

        $this->it_creates_new_message();
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

        Notification::assertSentTo(
            notifiable  : new AnonymousNotifiable(),
            notification: ContactSubmitted::class,
            callback    : fn(ContactSubmitted $notification, array $channels, AnonymousNotifiable $notifiable): bool => in_array(config('mail.from.address'), $notifiable->routes)
        );

        $this->assertTrue($this->cache->has("contact_cooldown:{$this->data['email']}"));
    }

    #[Test]
    public function it_does_not_send_notification_during_cooldown(): void
    {
        Notification::fake();

        $this->cache->put(
            key  : "contact_cooldown:{$this->data['email']}",
            value: true,
            ttl  : now()->addDay()
        );

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : $this->data
            )
            ->assertCreated();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_resumes_notifications_after_cooldown_expires(): void
    {
        $this->it_sends_a_notification_to_support();

        $this->travel(1)->day();

        $this->it_sends_a_notification_to_support();
    }

    #[Test]
    public function it_handles_concurrent_signup_attempts_properly(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this
                ->json(
                    method: 'POST',
                    uri   : $this->uri,
                    data  : $this->data
                )
                ->assertCreated();
        }

        Notification::assertSentToTimes(new AnonymousNotifiable(), ContactSubmitted::class);
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
