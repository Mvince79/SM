<?php

namespace Tests\Feature;

use App\Events\AdminChatMessageSent;
use App\Events\MessageSent;
use App\Http\Livewire\Chat\ChatBox;
use App\Models\AdminChatAssignment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class AdminChatRealtimeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_admin_can_only_authorize_their_active_assignment_channel(): void
    {
        $adminA = User::factory()->create(['role' => 'ADMIN']);
        $adminB = User::factory()->create(['role' => 'MODERATOR']);
        $requesterA = User::factory()->create();
        $requesterB = User::factory()->create();
        $assignmentA = $this->assignment($adminA, $requesterA);
        $assignmentB = $this->assignment($adminB, $requesterB);

        $authorize = Broadcast::getChannels()->get(
            'admin-chat-assignment.{assignmentId}'
        );

        $this->assertNotNull($authorize);
        $this->assertTrue($authorize($adminA, $assignmentA->id));
        $this->assertFalse($authorize($adminB, $assignmentA->id));
        $this->assertFalse($authorize($requesterA, $assignmentA->id));
        $this->assertTrue($authorize($adminB, $assignmentB->id));
        $this->assertFalse($authorize($adminA, $assignmentB->id));

        $assignmentA->update([
            'status' => AdminChatAssignment::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $this->assertFalse($authorize($adminA, $assignmentA->id));
    }

    public function test_events_for_two_admins_use_distinct_assignment_channels(): void
    {
        $adminA = User::factory()->create(['role' => 'ADMIN']);
        $adminB = User::factory()->create(['role' => 'ADMIN']);
        $requesterA = User::factory()->create();
        $requesterB = User::factory()->create();
        $assignmentA = $this->assignment($adminA, $requesterA);
        $assignmentB = $this->assignment($adminB, $requesterB);
        $messageA = $this->message($requesterA, $adminA, 'Tin nhắn A');
        $messageB = $this->message($requesterB, $adminB, 'Tin nhắn B');

        $eventA = new AdminChatMessageSent($assignmentA, $messageA);
        $eventB = new AdminChatMessageSent($assignmentB, $messageB);

        $this->assertSame(
            'private-admin-chat-assignment.'.$assignmentA->id,
            $eventA->broadcastOn()[0]->name,
        );
        $this->assertSame(
            'private-admin-chat-assignment.'.$assignmentB->id,
            $eventB->broadcastOn()[0]->name,
        );
        $this->assertNotSame(
            $eventA->broadcastOn()[0]->name,
            $eventB->broadcastOn()[0]->name,
        );
        $this->assertSame($assignmentA->id, $eventA->broadcastWith()['assignment_id']);
        $this->assertSame($messageB->id, $eventB->broadcastWith()['message_id']);
        $this->assertArrayNotHasKey('body', $eventA->broadcastWith());
    }

    public function test_admin_page_only_exposes_its_own_assignment_channels(): void
    {
        $adminA = User::factory()->create(['role' => 'ADMIN']);
        $adminB = User::factory()->create(['role' => 'ADMIN']);
        $assignmentA = $this->assignment($adminA, User::factory()->create());
        $assignmentB = $this->assignment($adminB, User::factory()->create());

        $response = $this->actingAs($adminA)->get('/');

        $response->assertOk()
            ->assertSee('window.adminChatAssignmentIds = ['.$assignmentA->id.'];', false)
            ->assertDontSee('window.adminChatAssignmentIds = ['.$assignmentB->id.'];', false);
    }

    public function test_requester_message_dispatches_the_assigned_admin_event(): void
    {
        Event::fake([
            AdminChatMessageSent::class,
            MessageSent::class,
        ]);

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $requester = User::factory()->create();
        $assignment = $this->assignment($admin, $requester);

        Livewire::actingAs($requester)
            ->test(ChatBox::class, [
                'missing' => null,
                'user' => $admin->id,
            ])
            ->set('body', 'Tôi cần hỗ trợ')
            ->call('send')
            ->assertHasNoErrors();

        Event::assertDispatched(
            AdminChatMessageSent::class,
            fn (AdminChatMessageSent $event): bool => $event->assignment->is($assignment)
                && $event->message->from_user_id === $requester->id
                && $event->message->to_user_id === $admin->id,
        );
    }

    public function test_requester_cannot_send_to_an_admin_outside_their_assignment(): void
    {
        Event::fake([
            AdminChatMessageSent::class,
            MessageSent::class,
        ]);

        $assignedAdmin = User::factory()->create(['role' => 'ADMIN']);
        $otherAdmin = User::factory()->create(['role' => 'ADMIN']);
        $requester = User::factory()->create();
        $this->assignment($assignedAdmin, $requester);

        Livewire::actingAs($requester)
            ->test(ChatBox::class, [
                'missing' => null,
                'user' => $otherAdmin->id,
            ])
            ->set('body', 'Tin nhắn gửi nhầm admin')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('messages', 0);
        Event::assertNotDispatched(AdminChatMessageSent::class);
        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_two_regular_users_cannot_continue_a_direct_conversation(): void
    {
        Event::fake([
            AdminChatMessageSent::class,
            MessageSent::class,
        ]);

        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Livewire::actingAs($sender)
            ->test(ChatBox::class, [
                'missing' => null,
                'user' => $receiver->id,
            ])
            ->set('body', 'Tiếp tục chat trực tiếp')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('messages', 0);
        Event::assertNotDispatched(AdminChatMessageSent::class);
        Event::assertNotDispatched(MessageSent::class);
    }

    public function test_user_cannot_message_an_admin_without_an_active_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $requester = User::factory()->create();

        Livewire::actingAs($requester)
            ->test(ChatBox::class, [
                'missing' => null,
                'user' => $admin->id,
            ])
            ->set('body', 'Chưa được phân công')
            ->call('send')
            ->assertHasErrors(['body']);

        $this->assertDatabaseCount('messages', 0);
    }

    private function assignment(User $admin, User $requester): AdminChatAssignment
    {
        return AdminChatAssignment::query()->create([
            'admin_user_id' => $admin->id,
            'requester_user_id' => $requester->id,
            'status' => AdminChatAssignment::STATUS_ACTIVE,
            'assigned_at' => now(),
        ]);
    }

    private function message(User $sender, User $receiver, string $body): Message
    {
        return Message::query()->create([
            'from_user_id' => $sender->id,
            'to_user_id' => $receiver->id,
            'body' => $body,
        ]);
    }
}
