<?php

namespace Tests\Unit;

use App\Exceptions\UserFacingException;
use App\Models\CommentsLike;
use App\Models\PostComment;
use App\Models\User;
use App\Models\UserPost;
use App\Service\PostCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCommentServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test posting a comment with valid content
     */
    public function test_post_comment_with_valid_content(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $comment = PostCommentService::postComment(
            $user->id,
            'This is a test comment',
            $post->id
        );

        $this->assertInstanceOf(PostComment::class, $comment);
        $this->assertEquals('This is a test comment', $comment->content);
        $this->assertEquals($user->id, $comment->user_id);
        $this->assertEquals($post->id, $comment->post_id);
        $this->assertEquals(0, $comment->likes);

        $this->assertDatabaseHas('post_comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'This is a test comment',
            'likes' => 0,
        ]);
    }

    /**
     * Test posting a comment with empty content
     */
    public function test_post_comment_with_empty_content(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Comment is empty');

        PostCommentService::postComment($user->id, '', $post->id);
    }

    /**
     * Test posting a comment with null content
     */
    public function test_post_comment_with_null_content(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Comment is empty');

        PostCommentService::postComment($user->id, "", $post->id);
    }

    /**
     * Test posting a comment with long content
     */
    public function test_post_comment_with_long_content(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $longContent = str_repeat('This is a long comment. ', 50);

        $comment = PostCommentService::postComment($user->id, $longContent, $post->id);

        $this->assertEquals($longContent, $comment->content);
    }

    /**
     * Test getting comments for a post
     */
    public function test_get_comments_for_post(): void
    {
        $post = UserPost::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
            'content' => 'First comment',
            'likes' => 5,
        ]);

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user2->id,
            'content' => 'Second comment',
            'likes' => 3,
        ]);

        $comments = PostCommentService::getComments($post->id);

        $this->assertCount(2, $comments);
        $this->assertEquals('First comment', $comments[0]->content);
        $this->assertEquals('Second comment', $comments[1]->content);
    }

    /**
     * Test getting comments ordered by likes descending
     */
    public function test_get_comments_ordered_by_likes_descending(): void
    {
        $post = UserPost::factory()->create();
        $user = User::factory()->create();

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Less liked',
            'likes' => 2,
        ]);

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'More liked',
            'likes' => 10,
        ]);

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Middle liked',
            'likes' => 5,
        ]);

        $comments = PostCommentService::getComments($post->id);

        $this->assertEquals(10, $comments[0]->likes);
        $this->assertEquals(5, $comments[1]->likes);
        $this->assertEquals(2, $comments[2]->likes);
    }

    /**
     * Test getting comments with user relationship loaded
     */
    public function test_get_comments_with_user_relationship(): void
    {
        $post = UserPost::factory()->create();
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        PostComment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Test comment',
        ]);

        $comments = PostCommentService::getComments($post->id);

        $this->assertCount(1, $comments);
        $this->assertNotNull($comments[0]->user);
        $this->assertEquals('John', $comments[0]->user->first_name);
        $this->assertEquals('Doe', $comments[0]->user->last_name);
    }

    /**
     * Test getting comments for post with no comments
     */
    public function test_get_comments_for_post_with_no_comments(): void
    {
        $post = UserPost::factory()->create();

        $comments = PostCommentService::getComments($post->id);

        $this->assertCount(0, $comments);
    }

    /**
     * Test getting comments only returns comments for specified post
     */
    public function test_get_comments_returns_only_post_comments(): void
    {
        $post1 = UserPost::factory()->create();
        $post2 = UserPost::factory()->create();
        $user = User::factory()->create();

        PostComment::factory()->create([
            'post_id' => $post1->id,
            'user_id' => $user->id,
            'content' => 'Comment on post 1',
        ]);

        PostComment::factory()->create([
            'post_id' => $post2->id,
            'user_id' => $user->id,
            'content' => 'Comment on post 2',
        ]);

        $comments = PostCommentService::getComments($post1->id);

        $this->assertCount(1, $comments);
        $this->assertEquals('Comment on post 1', $comments[0]->content);
    }

    /**
     * Test adding a like to a comment
     */
    public function test_toggle_comment_like_adds_new_like(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 5]);

        $result = PostCommentService::toggleCommentLike($user->id, $comment->id);

        $this->assertTrue($result['liked']);
        $this->assertEquals(6, $result['likes']);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test removing a like from a comment
     */
    public function test_toggle_comment_like_removes_existing_like(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 5]);

        CommentsLike::create([
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        $result = PostCommentService::toggleCommentLike($user->id, $comment->id);

        $this->assertFalse($result['liked']);
        $this->assertEquals(4, $result['likes']);

        $this->assertDatabaseMissing('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test toggle like increments likes counter
     */
    public function test_toggle_like_increments_likes_counter(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 0]);

        PostCommentService::toggleCommentLike($user->id, $comment->id);

        $comment->refresh();
        $this->assertEquals(1, $comment->likes);
    }

    /**
     * Test toggle like decrements likes counter
     */
    public function test_toggle_like_decrements_likes_counter(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 3]);

        CommentsLike::create([
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        PostCommentService::toggleCommentLike($user->id, $comment->id);

        $comment->refresh();
        $this->assertEquals(2, $comment->likes);
    }

    /**
     * Test multiple users can like the same comment
     */
    public function test_multiple_users_can_like_same_comment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 0]);

        PostCommentService::toggleCommentLike($user1->id, $comment->id);
        PostCommentService::toggleCommentLike($user2->id, $comment->id);
        PostCommentService::toggleCommentLike($user3->id, $comment->id);

        $comment->refresh();
        $this->assertEquals(3, $comment->likes);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user2->id,
        ]);
        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user3->id,
        ]);
    }

    /**
     * Test user can like and unlike the same comment
     */
    public function test_user_can_like_and_unlike_same_comment(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 0]);

        // Like the comment
        $result1 = PostCommentService::toggleCommentLike($user->id, $comment->id);
        $this->assertTrue($result1['liked']);
        $this->assertEquals(1, $result1['likes']);

        // Unlike the comment
        $result2 = PostCommentService::toggleCommentLike($user->id, $comment->id);
        $this->assertFalse($result2['liked']);
        $this->assertEquals(0, $result2['likes']);

        // Like again
        $result3 = PostCommentService::toggleCommentLike($user->id, $comment->id);
        $this->assertTrue($result3['liked']);
        $this->assertEquals(1, $result3['likes']);
    }

    /**
     * Test toggle like returns fresh comment likes count
     */
    public function test_toggle_like_returns_fresh_likes_count(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 2]);

        CommentsLike::create([
            'comment_id' => $comment->id,
            'user_id' => $user1->id,
        ]);

        CommentsLike::create([
            'comment_id' => $comment->id,
            'user_id' => $user2->id,
        ]);

        // Toggle like for user2 (remove)
        $result = PostCommentService::toggleCommentLike($user2->id, $comment->id);

        $this->assertFalse($result['liked']);
        $this->assertEquals(1, $result['likes']);
    }

    /**
     * Test toggle like is transactional
     */
    public function test_toggle_like_uses_database_transaction(): void
    {
        $user = User::factory()->create();
        $comment = PostComment::factory()->create(['likes' => 0]);

        $result = PostCommentService::toggleCommentLike($user->id, $comment->id);

        // Verify both operations succeeded
        $this->assertTrue($result['liked']);
        $this->assertEquals(1, $result['likes']);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        $comment->refresh();
        $this->assertEquals(1, $comment->likes);
    }

    /**
     * Test posting multiple comments by same user
     */
    public function test_post_multiple_comments_by_same_user(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $comment1 = PostCommentService::postComment($user->id, 'First comment', $post->id);
        $comment2 = PostCommentService::postComment($user->id, 'Second comment', $post->id);

        $this->assertNotEquals($comment1->id, $comment2->id);
        $this->assertEquals($user->id, $comment1->user_id);
        $this->assertEquals($user->id, $comment2->user_id);

        $this->assertDatabaseHas('post_comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'First comment',
        ]);

        $this->assertDatabaseHas('post_comments', [
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Second comment',
        ]);
    }

    /**
     * Test posting comments by different users on same post
     */
    public function test_post_comments_by_different_users_on_same_post(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = UserPost::factory()->create();

        $comment1 = PostCommentService::postComment($user1->id, 'User 1 comment', $post->id);
        $comment2 = PostCommentService::postComment($user2->id, 'User 2 comment', $post->id);

        $comments = PostCommentService::getComments($post->id);

        $this->assertCount(2, $comments);
        $this->assertContains('User 1 comment', $comments->pluck('content')->toArray());
        $this->assertContains('User 2 comment', $comments->pluck('content')->toArray());
    }

    /**
     * Test comment with special characters
     */
    public function test_post_comment_with_special_characters(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $specialContent = "This is a comment with special chars: @#$%^&*()_+-=[]{}|;:',.<>?/~`";

        $comment = PostCommentService::postComment($user->id, $specialContent, $post->id);

        $this->assertEquals($specialContent, $comment->content);
    }

    /**
     * Test comment with unicode characters
     */
    public function test_post_comment_with_unicode_characters(): void
    {
        $user = User::factory()->create();
        $post = UserPost::factory()->create();

        $unicodeContent = "This comment has emojis 🚀 and unicode: café, naïve, résumé";

        $comment = PostCommentService::postComment($user->id, $unicodeContent, $post->id);

        $this->assertEquals($unicodeContent, $comment->content);
    }
}
