<?php

declare(strict_types=1);

namespace Nejcc\LaravelQuerylayer\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nejcc\LaravelQuerylayer\Tests\Models\Post;
use Nejcc\LaravelQuerylayer\Tests\Models\User;
use Nejcc\LaravelQuerylayer\Tests\Repositories\PostRepository;
use Nejcc\LaravelQuerylayer\Tests\Repositories\UserRepository;

final class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $userRepository;
    protected PostRepository $postRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository();
        $this->postRepository = new PostRepository();
    }
    
    /** @test */
    public function it_can_eager_load_user_posts(): void
    {
        // Create users and posts
        $user1 = $this->userRepository->create([
            'name' => 'User With Posts',
            'email' => 'user1@example.com',
        ]);
        
        $user2 = $this->userRepository->create([
            'name' => 'User Without Posts',
            'email' => 'user2@example.com',
        ]);
        
        // Create posts for user1
        $this->postRepository->create([
            'user_id' => $user1->id,
            'title' => 'First Post',
            'content' => 'Content of first post',
            'is_published' => true,
        ]);
        
        $this->postRepository->create([
            'user_id' => $user1->id,
            'title' => 'Second Post',
            'content' => 'Content of second post',
            'is_published' => false,
        ]);
        
        // Test eager loading with 'with' method
        $userWithPosts = $this->userRepository->with('posts')->find($user1->id);
        
        // Assert the posts are loaded
        $this->assertTrue($userWithPosts->relationLoaded('posts'));
        $this->assertCount(2, $userWithPosts->posts);
        
        // Check first post details
        $this->assertEquals('First Post', $userWithPosts->posts[0]->title);
        
        // Now test that for user2 we get empty posts array
        $userWithoutPosts = $this->userRepository->with('posts')->find($user2->id);
        $this->assertTrue($userWithoutPosts->relationLoaded('posts'));
        $this->assertCount(0, $userWithoutPosts->posts);
    }
    
    /** @test */
    public function it_can_eager_load_post_user(): void
    {
        // Create a user
        $user = $this->userRepository->create([
            'name' => 'Post Owner',
            'email' => 'owner@example.com',
        ]);
        
        // Create a post
        $post = $this->postRepository->create([
            'user_id' => $user->id,
            'title' => 'Post With User',
            'content' => 'This post has a user relation',
            'is_published' => true,
        ]);
        
        // Test eager loading the user with the post
        $postWithUser = $this->postRepository->with('user')->find($post->id);
        
        // Assert the user is loaded
        $this->assertTrue($postWithUser->relationLoaded('user'));
        $this->assertInstanceOf(User::class, $postWithUser->user);
        $this->assertEquals('Post Owner', $postWithUser->user->name);
        $this->assertEquals('owner@example.com', $postWithUser->user->email);
    }
    
    /** @test */
    public function it_can_chain_where_and_with_methods(): void
    {
        // Create users with different roles
        $admin = $this->userRepository->create([
            'name' => 'Admin With Posts',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        
        $user = $this->userRepository->create([
            'name' => 'User With Posts',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);
        
        // Create posts for both users
        $this->postRepository->create([
            'user_id' => $admin->id,
            'title' => 'Admin Post',
            'content' => 'Content of admin post',
            'is_published' => true,
        ]);
        
        $this->postRepository->create([
            'user_id' => $user->id,
            'title' => 'User Post',
            'content' => 'Content of user post',
            'is_published' => true,
        ]);
        
        // Find all admins with their posts
        $admins = $this->userRepository->with('posts')->where(['role' => 'admin']);
        
        // Check results
        $this->assertCount(1, $admins);
        $adminWithPosts = $admins->first();
        $this->assertEquals('admin@example.com', $adminWithPosts->email);
        $this->assertTrue($adminWithPosts->relationLoaded('posts'));
        $this->assertCount(1, $adminWithPosts->posts);
        $this->assertEquals('Admin Post', $adminWithPosts->posts[0]->title);
    }
    
    /** @test */
    public function it_can_eager_load_multiple_relations(): void
    {
        // This is a placeholder test to demonstrate the concept
        // In a real app, you would have multiple relations to load
        
        // Create users and posts
        $user = $this->userRepository->create([
            'name' => 'Multi-Relation User',
            'email' => 'multi@example.com',
        ]);
        
        $this->postRepository->create([
            'user_id' => $user->id,
            'title' => 'Multi-Relation Post',
            'content' => 'Content for testing multiple relations',
            'is_published' => true,
        ]);
        
        // Test multiple eager loading (in this case we only have posts)
        // In a real app, this would load multiple different relations
        $userWithRelations = $this->userRepository->with(['posts'])->find($user->id);
        
        $this->assertTrue($userWithRelations->relationLoaded('posts'));
        $this->assertCount(1, $userWithRelations->posts);
    }
} 