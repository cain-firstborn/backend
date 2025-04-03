<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateSignUpAction;
use App\Http\Requests\API\CreateSignUpRequest;
use App\Models\User;
use App\Notifications\UserSignedUp;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSignUpTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Test Action class instance.
     */
    private CreateSignUpAction $action;

    /**
     * Test Cache instance.
     */
    private CacheManager $cache;

    /**
     * Test Form Request instance.
     */
    private CreateSignUpRequest $request;

    /**
     * Set up the test environment.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->action  = $this->app->make(CreateSignUpAction::class);
        $this->cache   = $this->app->make(CacheManager::class);
        $this->request = new CreateSignUpRequest([
            'email' => 'email@example.com',
        ]);

        $this->request->setContainer($this->app)->validateResolved();

        Notification::fake();
    }

    #[Test]
    public function it_creates_new_user_if_not_cached(): void
    {
        $this->assertDatabaseEmpty('users');
        $this->assertFalse($this->cache->has("user:{$this->request->email}"));

        $this->action->handle($this->request);

        $this->assertDatabaseHas('users', ['email' => $this->request->email]);
        $this->assertTrue($this->cache->has("user:{$this->request->email}"));
    }

    #[Test]
    public function it_uses_cached_user_if_exists(): void
    {
        $user = $this->cache->rememberForever(
            key     : "user:{$this->request->email}",
            callback: fn(): User => User::query()->create(['email' => $this->request->email])
        );

        $this->assertDatabaseHas('users', $user->getAttributes());

        $this->action->handle($this->request);

        $this->assertDatabaseCount('users', 1);
    }

    #[Test]
    public function it_sets_cooldown(): void
    {
        $this->assertFalse($this->cache->has("signup_cooldown:{$this->request->email}"));

        $this->action->handle($this->request);

        $this->assertTrue($this->cache->has("signup_cooldown:{$this->request->email}"));
    }

    #[Test]
    public function it_creates_sign_up(): void
    {
        $this->assertDatabaseEmpty('sign_ups');

        $this->action->handle($this->request);

        $user = User::query()->firstWhere('email', $this->request->email);

        $this->assertDatabaseHas('sign_ups', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_does_not_create_sign_up(): void
    {
        $this->cache->put(
            key  : "signup_cooldown:{$this->request->email}",
            value: true,
            ttl  : now()->addDay()
        );

        $this->assertDatabaseEmpty('sign_ups');

        $this->action->handle($this->request);

        $this->assertDatabaseEmpty('sign_ups');
    }

    #[Test]
    public function it_sends_notification(): void
    {
        $this->action->handle($this->request);

        $user = User::query()->firstWhere('email', $this->request->email);

        Notification::assertSentTo($user, UserSignedUp::class);
    }

    #[Test]
    public function it_does_not_send_notification_during_cooldown(): void
    {
        $this->cache->put(
            key  : "signup_cooldown:{$this->request->email}",
            value: true,
            ttl  : now()->addDay()
        );

        $this->action->handle($this->request);

        Notification::assertNothingSent();
    }

}
