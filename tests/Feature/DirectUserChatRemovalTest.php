<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DirectUserChatRemovalTest extends TestCase
{
    public function test_direct_user_chat_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('chat.dev'));
        $this->assertFalse(Route::has('chat.index'));
        $this->assertFalse(Route::has('chat.attachment'));
    }

    public function test_controlled_handover_and_admin_chat_routes_remain_registered(): void
    {
        $this->assertTrue(Route::has('verifier.handover.confirm'));
        $this->assertTrue(Route::has('admin-chat.attachment'));
        $this->assertTrue(Route::has('support.index'));
    }
}
