<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\User;
use App\Models\UserCopilotHistory;
use App\Service\UserCopilotHistoryService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Tests\TestCase;

class UserCopilotHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting histories returns only user's histories
     */
    public function test_get_user_histories_returns_only_user_histories(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserCopilotHistory::factory()->create(['user_id' => $user1->id]);
        UserCopilotHistory::factory()->create(['user_id' => $user1->id]);
        UserCopilotHistory::factory()->create(['user_id' => $user2->id]);

        $user1Histories = UserCopilotHistoryService::getUserHistories($user1->id);
        $user2Histories = UserCopilotHistoryService::getUserHistories($user2->id);

        $this->assertCount(2, $user1Histories);
        $this->assertCount(1, $user2Histories);
    }

    /**
     * Test getting histories with messages loaded
     */
    public function test_get_user_histories_loads_messages(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        Message::factory()->create(['history_id' => $history->id]);
        Message::factory()->create(['history_id' => $history->id]);

        $histories = UserCopilotHistoryService::getUserHistories($user->id);

        $this->assertCount(1, $histories);
        $this->assertTrue($histories[0]->relationLoaded('messages'));
        $this->assertCount(2, $histories[0]->messages);
    }

    /**
     * Test messages are ordered by created_at ascending
     */
    public function test_get_user_histories_messages_ordered_by_created_at(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $message1 = Message::factory()->create(['history_id' => $history->id]);
        $message2 = Message::factory()->create(['history_id' => $history->id]);
        $message3 = Message::factory()->create(['history_id' => $history->id]);

        $histories = UserCopilotHistoryService::getUserHistories($user->id);
        $messages = $histories[0]->messages;

        $this->assertEquals($message1->id, $messages[0]->id);
        $this->assertEquals($message2->id, $messages[1]->id);
        $this->assertEquals($message3->id, $messages[2]->id);
    }

    /**
     * Test getting empty histories for user with no histories
     */
    public function test_get_user_histories_with_no_histories(): void
    {
        $user = User::factory()->create();

        $histories = UserCopilotHistoryService::getUserHistories($user->id);

        $this->assertCount(0, $histories);
    }

    /**
     * Test getting copilot history details successfully
     */
    public function test_get_user_copilot_history_details_successfully(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        Message::factory()->create(['history_id' => $history->id]);
        Message::factory()->create(['history_id' => $history->id]);

        $result = UserCopilotHistoryService::getUserCopilotHistoryDetials($user->id, $history);

        $this->assertEquals($history->id, $result->id);
        $this->assertTrue($result->relationLoaded('messages'));
        $this->assertCount(2, $result->messages);
    }

    /**
     * Test getting history details for unauthorized user
     */
    public function test_get_user_copilot_history_details_unauthorized(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user1->id]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('History not found');

        UserCopilotHistoryService::getUserCopilotHistoryDetials($user2->id, $history);
    }

    /**
     * Test getting history details loads messages ordered
     */
    public function test_get_user_copilot_history_details_loads_ordered_messages(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $message1 = Message::factory()->create(['history_id' => $history->id]);
        $message2 = Message::factory()->create(['history_id' => $history->id]);
        $message3 = Message::factory()->create(['history_id' => $history->id]);

        $result = UserCopilotHistoryService::getUserCopilotHistoryDetials($user->id, $history);

        $this->assertEquals($message1->id, $result->messages[0]->id);
        $this->assertEquals($message2->id, $result->messages[1]->id);
        $this->assertEquals($message3->id, $result->messages[2]->id);
    }

    /**
     * Test getting history details with no messages
     */
    public function test_get_user_copilot_history_details_with_no_messages(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $result = UserCopilotHistoryService::getUserCopilotHistoryDetials($user->id, $history);

        $this->assertCount(0, $result->messages);
    }

    /**
     * Test deleting history successfully
     */
    public function test_delete_history_successfully(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        Message::factory()->create(['history_id' => $history->id]);
        Message::factory()->create(['history_id' => $history->id]);

        UserCopilotHistoryService::deleteHistory($user->id, $history);

        $this->assertDatabaseMissing('user_copilot_histories', ['id' => $history->id]);
        $this->assertDatabaseMissing('messages', ['history_id' => $history->id]);
    }

    /**
     * Test deleting history removes all related messages
     */
    public function test_delete_history_removes_all_messages(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $message1 = Message::factory()->create(['history_id' => $history->id]);
        $message2 = Message::factory()->create(['history_id' => $history->id]);
        $message3 = Message::factory()->create(['history_id' => $history->id]);

        UserCopilotHistoryService::deleteHistory($user->id, $history);

        $this->assertDatabaseMissing('messages', ['id' => $message1->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message2->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message3->id]);
    }

    /**
     * Test deleting history for unauthorized user
     */
    public function test_delete_history_unauthorized(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user1->id]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('History not found');

        UserCopilotHistoryService::deleteHistory($user2->id, $history);
    }

    /**
     * Test deleting history does not delete other user's messages
     */
    public function test_delete_history_does_not_delete_other_histories(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $history1 = UserCopilotHistory::factory()->create(['user_id' => $user1->id]);
        $history2 = UserCopilotHistory::factory()->create(['user_id' => $user2->id]);

        Message::factory()->create(['history_id' => $history1->id]);
        Message::factory()->create(['history_id' => $history2->id]);

        UserCopilotHistoryService::deleteHistory($user1->id, $history1);

        $this->assertDatabaseMissing('user_copilot_histories', ['id' => $history1->id]);
        $this->assertDatabaseHas('user_copilot_histories', ['id' => $history2->id]);
    }

    /**
     * Test getting downloadable content successfully
     */
    public function test_get_downloadable_content_successfully(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $aiResponse = ['response' => 'This is the AI response'];
        $message = Message::factory()->create([
            'history_id' => $history->id,
            'ai_response' => $aiResponse,
            'user_message' => 'Test message',
        ]);

        $result = UserCopilotHistoryService::getDownloadableContent($user->id, $history);

        $this->assertEquals($message->id, $result->id);
        $this->assertEquals($aiResponse, $result->ai_response);
    }

    /**
     * Test getting downloadable content without messages
     */
    public function test_get_downloadable_content_without_messages(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $this->expectException(\Exception::class);

        UserCopilotHistoryService::getDownloadableContent($user->id, $history);
    }

    /**
     * Test getting downloadable content with array ai_response
     */
    public function test_get_downloadable_content_with_array_ai_response(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $aiResponse = [
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'Sample response']],
            ],
        ];

        Message::factory()->create([
            'history_id' => $history->id,
            'ai_response' => $aiResponse,
        ]);

        $result = UserCopilotHistoryService::getDownloadableContent($user->id, $history);

        $this->assertEquals($aiResponse, $result->ai_response);
        $this->assertIsArray($result->ai_response);
    }

    /**
     * Test getting downloadable content returns message object
     */
    public function test_get_downloadable_content_returns_message_object(): void
    {
        $user = User::factory()->create();
        $history = UserCopilotHistory::factory()->create(['user_id' => $user->id]);

        $message = Message::factory()->create([
            'history_id' => $history->id,
            'ai_response' => ['response' => 'Test'],
            'user_message' => 'User query',
            'ai_model' => 'gpt-4',
        ]);

        $result = UserCopilotHistoryService::getDownloadableContent($user->id, $history);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals('User query', $result->user_message);
        $this->assertEquals('gpt-4', $result->ai_model);
    }
}
