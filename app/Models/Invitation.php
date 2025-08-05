<?php

namespace App\Models;

use App\HelperFunctions\InvitationHelper;
use App\HelperFunctions\UserPermissions;
use App\Notifications\NotifyUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Invitation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'country_id',
        'invited_by',
        'invited_at',
        'registered_at',
        'expired_at',
        'status_id',
        'hash'
    ];

    public function transformAudit(array $data): array
    {
        if ($data['event'] == 'created') {
            $data['description'] = 'The user has sent an invitation';
        }
        
        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function status()
    {
        return $this->belongsTo(InvitationStatus::class);
    }

    public static function getInvitations()
    {
        return Invitation::select(
            'invitations.*',
            'roles.name AS role',
            'countries.name AS country',
            'users.name AS invited_by',
            'invitation_statuses.name AS status'
        )
            ->leftJoin('users', 'users.id', '=', 'invitations.invited_by')
            ->leftJoin('roles', 'roles.id', '=', 'invitations.role_id')
            ->leftJoin('countries', 'countries.id', '=', 'invitations.country_id')
            ->leftJoin('invitation_statuses', 'invitation_statuses.id', '=', 'invitations.status_id')
            ->when(Auth::user()->isPrimaryPoC(), function ($query) {
                $query->whereIn('invitations.country_id', UserPermissions::getUserCountries());
            })
            ->get();
    }

    public static function getLatestPendingInvitation($email)
    {
        return Invitation::where('email', $email)
            ->whereNotNull('invited_at')
            ->whereNull('registered_at')
            ->where('status_id', 1)
            ->orderBy('invited_at', 'desc')
            ->first();
    }

    public static function notifyExpiredInvitation($user, $invitation)
    {
        $user->notify(new NotifyUser([
            'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
            'subject' => 'User Invitation - Expired',
            'markdown' => 'user-invitation-expired',
            'maildata' => [
                'url' => env('APP_URL') . '/invitation/management',
                'user' => $user->name,
                'name' => $invitation->name
            ]
        ]));
    }

    public static function autoExpireInvitations()
    {
        $invitations = Invitation::where('status_id', 1)
            ->where('expired_at', '<', Carbon::now())
            ->get();
        
        if ($invitations->count())
        {
            $data = [];
            $notifyUsers = new Collection();

            $data['role_id'] = 1;
            $notifyUsers = $notifyUsers->concat(User::getUsersByCountryAndRole($data));
            
            foreach ($invitations as $invitation)
            {
                InvitationHelper::markInvitationExpired($invitation);

                $user = User::find($invitation->invited_by);

                // Notify PPoC?
                if ($user->permissions->first()->role->id == 5) {
                    self::notifyExpiredInvitation($user, $invitation);
                }
                
                // Notify admins
                foreach ($notifyUsers as $notifyUser) {
                    self::notifyExpiredInvitation($notifyUser->user, $invitation);
                }
            }
        }
    }
}
