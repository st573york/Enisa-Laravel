<?php

namespace App\Listeners;

use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;

class LastLogin
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        User::disableAuditing();

        // Update user last login date/time
        $event->user->update(['last_login_at' => Carbon::now()->format('Y-m-d H:i:s')]);

        User::enableAuditing();
        
        Audit::setCustomAuditEvent(
            User::find($event->user->id),
            ['event' => 'logged in' . ($event->user->blocked ? ' (blocked)' : '')]
        );
    }
}
