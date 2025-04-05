<?php

namespace Tests\Feature\API\V1;

use App\Models\User;
use App\Notifications\UserSignedUp;
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

class SingUpTest extends TestCase
{
    use WithCache;
    use WithTranslator;
    use LazilyRefreshDatabase;

    /**
     * Email address used in tests.
     *
     * @var string
     */
    private string $email = 'email@example.com';

    /**
     * API Endpoint used in tests.
     *
     * @var string
     */
    private string $uri = '/v1/sign-up';

    #[Test]
    #[DataProvider('scenarios')]
    public function it_validates_the_request(string $field, string|null $value, string $rule, array|null $replace = [], int $status = Response::HTTP_UNPROCESSABLE_ENTITY): void
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
        $this->assertDatabaseMissing('users', [
            'email' => $this->email,
        ]);
        $this->assertFalse($this->cache->has("user:$this->email"));

        DB::enableQueryLog();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertSee(trans('response.user.created'))
            ->assertCreated();

        DB::disableQueryLog();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => $this->email,
        ]);
        $this->assertTrue($this->cache->has("user:$this->email"));
        $this->assertEquals($this->email, $this->cache->get("user:$this->email")->email);
        $this->assertContains('insert into "users" ("email", "updated_at", "created_at") values (?, ?, ?)', array_column(DB::getQueryLog(), 'query'));
    }

    #[Test]
    public function it_retrieves_user_from_cache_if_exists(): void
    {
        $this->cache->forever("user:$this->email", User::factory()->create([
            'email' => $this->email,
        ]));

        DB::enableQueryLog();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertSee(trans('response.user.created'))
            ->assertCreated();

        DB::disableQueryLog();

        $this->assertNotContains('insert into "users" ("email", "updated_at", "created_at") values (?, ?, ?)', array_column(DB::getQueryLog(), 'query'));
    }

    #[Test]
    public function it_creates_new_signup(): void
    {
        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertCreated();

        $user = User::query()->firstWhere('email', $this->email);

        $this->assertDatabaseHas('sign_ups', ['user_id' => $user->id]);
        $this->assertTrue($user->signings->isNotEmpty());
    }

    #[Test]
    public function it_does_not_creates_new_signup_during_cooldown(): void
    {
        $this->cache->put(
            key  : "signup_cooldown:$this->email",
            value: true,
            ttl  : now()->addDay()
        );

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertCreated();

        $this->assertDatabaseCount('sign_ups', 0);
    }

    #[Test]
    public function it_creates_new_signup_after_cooldown_expires(): void
    {
        $this->it_creates_new_signup();

        $this->travel(1)->day();

        $this->it_creates_new_signup();
    }

    #[Test]
    public function it_sends_a_notification_to_user(): void
    {
        Notification::fake();

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertCreated();

        $user = User::query()->firstWhere('email', $this->email);

        Notification::assertSentTo($user, UserSignedUp::class);

        $this->assertTrue($this->cache->has("signup_cooldown:$this->email"));
    }

    #[Test]
    public function it_does_not_send_notification_during_cooldown(): void
    {
        Notification::fake();

        $this->cache->put(
            key  : "signup_cooldown:$this->email",
            value: true,
            ttl  : now()->addDay()
        );

        $this
            ->json(
                method: 'POST',
                uri   : $this->uri,
                data  : [
                    'email' => $this->email,
                ]
            )
            ->assertCreated();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_resumes_notifications_after_cooldown_expires(): void
    {
        $this->it_sends_a_notification_to_user();

        $this->travel(1)->day();

        $this->it_sends_a_notification_to_user();
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
                    data  : [
                        'email' => $this->email,
                    ]
                )
                ->assertCreated();
        }

        $user = User::query()->firstWhere('email', $this->email);

        Notification::assertSentToTimes($user, UserSignedUp::class);
    }

    /**
     * Set of failed validation scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'email is required'                      => [
                'field' => 'email',
                'value' => null,
                'rule'  => 'required',
            ],
            'email has to be email'                  => [
                'field' => 'email',
                'value' => 'test',
                'rule'  => 'email',
            ],
            'email has to be maximum 255 characters' => [
                'field'   => 'email',
                'value'   => Str::random(256),
                'rule'    => 'max.string',
                'replace' => ['max' => 255],
            ],
        ];
    }
}
