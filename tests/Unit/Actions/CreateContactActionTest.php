<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateContactAction;
use App\Http\Requests\API\CreateContactRequest;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\ContactSubmitted;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\WithCache;
use Tests\Traits\WithConfig;

class CreateContactActionTest extends TestCase
{
    use WithCache;
    use WithConfig;
    use WithFaker;
    use LazilyRefreshDatabase;

    /**
     * Test Action class instance.
     */
    private CreateContactAction $action;

    /**
     * Test Form Request instance.
     */
    private CreateContactRequest $request;

    /**
     * Set up the test environment.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CreateContactAction::class);

        $this->request = new CreateContactRequest([
            'name'    => $this->faker->name,
            'email'   => $this->faker->email,
            'message' => $this->faker->text,
        ]);

        $this->request->setContainer($this->app)->validateResolved();

        Admin::factory()->support()->create();
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
    public function it_creates_sign_up(): void
    {
        $this->assertDatabaseEmpty('messages');

        $this->action->handle($this->request);

        $user = User::query()->firstWhere('email', $this->request->email);

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function it_sends_notification(): void
    {
        Notification::fake();

        $this->action->handle($this->request);

        Notification::assertSentTo(Admin::support(), ContactSubmitted::class);
    }
}
