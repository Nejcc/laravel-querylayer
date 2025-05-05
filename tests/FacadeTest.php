<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nejcc\LaravelQuerylayer\LaravelQuerylayerFacade as LaravelQuerylayer;
use Nejcc\LaravelQuerylayer\Tests\Models\User;

final class FacadeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_repository_via_facade(): void
    {
        $repository = LaravelQuerylayer::repository(User::class);

        $user = $repository->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_returns_same_repository_instance_for_same_model(): void
    {
        $repository1 = LaravelQuerylayer::repository(User::class);
        $repository2 = LaravelQuerylayer::repository(User::class);

        $this->assertSame($repository1, $repository2);
    }
}
