<?php

namespace App\Models;

use App\Notifications\NotifyUser;
use App\HelperFunctions\GeneralHelper;
use App\HelperFunctions\UserPermissions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected static $usersNotified = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'blocked',
        'country_code',
        'last_login_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    /**
     * Attributes to exclude from the Audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'password',
        'remember_token'
    ];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        if ($data['event'] == 'logged in') {
            $data['description'] = 'The user has logged in';
        }

        if ($data['event'] == 'logged out') {
            $data['description'] = 'The user has logged out';
        }
        
        // Item has been renamed or deleted?
        if (Arr::has($data, 'old_values.name')) {
            $data['auditable_name'] = $data['old_values']['name'];
            $data['description'] = "The user's information has been updated";
        }
        // Item has been created?
        else {
            $user = User::find($this->getAttribute('id'));
            if ($user) {
                $data['auditable_name'] = $user->name;
            }
        }

        if (Arr::has($data, 'new_values.blocked')) {
            $data['new_values']['status'] = ($data['new_values']['blocked']) ? 'Blocked' : 'Enabled';
            $data['description'] = 'The user has been ' . strtolower($data['new_values']['status']);
        }

        if (Arr::has($data, 'new_values.country_code')) {
            $data['new_values']['country'] = Country::where('code', $this->getAttribute('country_code'))->value('name');
            $data['description'] = "The user's country has been changed to " . $data['new_values']['country'];
        }

        unset($data['new_values']['blocked'],
              $data['new_values']['country_code']);

        return $data;
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function user_questionnaires()
    {
        return $this->hasMany(QuestionnaireUser::class);
    }

    public function questionnaire_submissions()
    {
        return $this->hasMany(QuestionnaireCountry::class,'id','submitted_by');
    }

    public function questionnaires()
    {
        return $this->hasMany(Questionnaire::class);
    }

    public static function canDeleteUser()
    {
        if (Auth::user()->isAdmin()) {
            return true;
        }

        return false;
    }

    public static function getUsersByCountryAndRole($data)
    {
        return Permission::with('user', 'country')->whereHas('country', function ($query) use ($data) {
            if (isset($data['country_code']))
            {
                if (is_array($data['country_code'])) {
                    $query->whereIn('code', $data['country_code']);
                }
                elseif (!is_array($data['country_code'])) {
                    $query->where('code', $data['country_code']);
                }
            }
        })
        ->when(is_array($data['role_id']), function ($query) use ($data) {
            $query->whereIn('role_id', $data['role_id']);
        })
        ->when(!is_array($data['role_id']), function ($query) use ($data) {
            $query->where('role_id', $data['role_id']);
        })
        ->get();
    }

    public static function getListOfUsersByCountryAndRole($roles, $country_code)
    {
        $data = [];
        $data['country_code'] = $country_code;
        $data['role_id'] = $roles;
        $users = self::getUsersByCountryAndRole($data);
        
        $obj = [];
        $ids = [];
        foreach ($users as $user)
        {
            array_push($obj, $user->user);
            array_push($ids, $user->user->id);
        }

        return [$obj, $ids];
    }

    public static function getUsers()
    {
        return User::select(
            'users.*',
            'permissions.role_id',
            'roles.name AS role_name',
            'roles.order AS role_order',
            DB::raw('
                (
                    CASE
                        WHEN permissions.country_id IS NOT NULL THEN countries.name
                        WHEN users.country_code IS NOT NULL AND users.country_code <> "" THEN (SELECT name FROM countries WHERE code = users.country_code)
                        ELSE \'-\'
                    END
                ) AS country')
        )
            ->leftJoin('permissions', 'permissions.user_id', '=', 'users.id')
            ->leftJoin('roles', 'roles.id', '=', 'permissions.role_id')
            ->leftJoin('countries', 'countries.id', '=', 'permissions.country_id')
            ->when(Auth::user()->isPoC(), function ($q1) {
                $q1->where(function($q2) {
                    $q2->whereIn('users.country_code', UserPermissions::getUserCountries('code'))
                       ->whereNull('permissions.country_id');
                })
                ->orWhereIn('permissions.country_id', UserPermissions::getUserCountries())
                ->where(function($q3) {
                    $q3->whereIn('roles.name', UserPermissions::getUserRoles('name'))
                        ->orWhere('users.id', Auth::user()->id);
                });
            })
            ->get();
    }

    public static function getCountryPrimaryPoC($country)
    {
        return Permission::where('country_id', $country->id)->where('role_id', 5)->first();
    }

    public static function updateOrCreateUser($data)
    {
        $user_id = (isset($data['id'])) ? $data['id'] : null;

        User::disableAuditing();
        $user = User::updateOrCreate(
            ['id' => $user_id],
            $data
        );
        User::enableAuditing();

        if (!$user_id) {
            Audit::setCustomAuditEvent(
                User::where('email', $data['email'])->first(),
                ['event' => 'registered']
            );
        }

        if (!$user_id)
        {
            $data['role_id'] = 1;
            $enisaAdmins = self::getUsersByCountryAndRole($data);

            foreach ($enisaAdmins as $enisaAdmin) {
                $enisaAdmin->user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'EU-CSI New User Registration',
                    'markdown' => 'user-registration',
                    'maildata' => [
                        'email' => $data['email']
                    ]
                ]));
            }
        }

        return $user;
    }

    public static function updateUserPermissions($user, $inputs)
    {
        $dbRoleId = ($user->permissions->first()) ? $user->permissions->first()->role['id'] : null;
        $dbCountryId = ($user->permissions->first()) ? $user->permissions->first()->country['id'] : null;
        $dbBlocked = $user->blocked;
        
        $role = Role::where('name', $inputs['role'])->first();
        $country = Country::where('name', $inputs['country'])->first();
        $blocked = $inputs['blocked'];

        $data = [];
        $data['user_id'] = $user->id;
        $data['name'] = $role->name;
        $data['country_id'] = $country->id;
        $data['role_id'] = $role->id;

        if ($role->id == 5) {
            self::updateCountryPrimaryPoCToPoC($country);
        }
        
        Permission::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        if ($dbRoleId != $role->id ||
            $dbCountryId != $country->id ||
            $dbBlocked != $blocked)
        {
            $notifyUsers = new Collection();

            $data['role_id'] = 1;
            $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));
            
            $data['country_code'] = $country->code;
            $data['role_id'] = 2;
            $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));
            
            // Notify all country PoCs and enisa admins
            foreach ($notifyUsers as $notifyUser) {
                $notifyUser->user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'User Permissions',
                    'markdown' => 'user-permissions',
                    'maildata' => [
                        'url' => env('APP_URL') . '/user/management',
                        'name' => $user->name,
                        'email' => $user->email,
                        'country' => $country->name,
                        'role' => $role->name,
                        'status' => ($blocked) ? '<span style="color: #D2224D;">blocked</span>' : 'enabled',
                        'author_name' => Auth::user()->name,
                        'author_email' => Auth::user()->email
                    ]
                ]));

                array_push(self::$usersNotified, $notifyUser->user->name);
            }
        }
    }

    public static function updateUserStatus($user, $inputs = array())
    {
        $dbBlocked = $user->blocked;
        $user->blocked = (isset($inputs['blocked'])) ? $inputs['blocked'] : !$user->blocked;
        $user->save();
        
        if ((isset($inputs['blocked']) && $dbBlocked != $inputs['blocked']) ||
            !isset($inputs['blocked']))
        {
            $notifyUsers = new Collection();
            
            $data = [];
            $data['role_id'] = 1;
            $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));

            $dbCountryCode = ($user->permissions->first()) ? $user->permissions->first()->country['code'] : null;
            if ($dbCountryCode)
            {
                $data['country_code'] = $dbCountryCode;
                $data['role_id'] = 2;
                $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));
            }

            $notifyData = [
                'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                'subject' => 'EU-CSI User Access',
                'markdown' => 'user-access',
                'maildata' => [
                    'url' => env('APP_URL'),
                    'status' => ($user->blocked) ? '<span style="color: #D2224D;">blocked</span>' : 'enabled',
                    'author_name' => Auth::user()->name,
                    'author_email' => Auth::user()->email
                ]
            ];

            // Notify all country PoCs and enisa admins
            foreach ($notifyUsers as $notifyUser)
            {
                if (!in_array($notifyUser->user->name, self::$usersNotified))
                {
                    $notifyData['maildata']['user'] = 'User access for <strong>' . $user->email . '</strong>';
                    $notifyUser->user->notify(new NotifyUser($notifyData));
                    
                    array_push(self::$usersNotified, $notifyUser->user->name);
                }
            }

            // Notify user
            if (!in_array($user->name, self::$usersNotified))
            {
                $notifyData['maildata']['user'] = 'Your access';
                $user->notify(new NotifyUser($notifyData));
            }
        }
    }

    public static function updateCountryPrimaryPoCToPoC($country)
    {
        Permission::where('country_id', $country->id)->where('role_id', 5)->update(['role_id' => 2]);
    }

    public static function deleteUser($user)
    {
        $ret = true;

        $ret &= $user->permissions()->where('user_id', $user->id)->delete();
        $ret &= $user->delete();

        return $ret;
    }

    public static function restoreUser($user)
    {
        User::disableAuditing();
        $user->permissions()->where('user_id', $user->id)->restore();
        $user->restore();
        User::enableAuditing();
    }
    
    public static function autoBlockUsers()
    {
        $users = User::where('blocked', 0)
            ->where(DB::raw('DATE(last_login_at) + INTERVAL ' . env('USER_INACTIVE') . ' DAY'), '<=', Carbon::now())
            ->where('inactive_notified', 0)
            ->whereNull('inactive_deadline')
            ->get();
        
        foreach ($users as $user)
        {
            $user->inactive_notified = 1;
            $user->inactive_deadline = Carbon::now()->addDays(env('USER_INACTIVE_DEADLINE'));
            $user->save();
            
            $user->notify(new NotifyUser([
                'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                'subject' => 'User Inactive - Reminder',
                'markdown' => 'user-inactive-reminder',
                'maildata' => [
                    'url' => env('APP_URL'),
                    'user' => $user->name,
                    'last_login' => GeneralHelper::dateFormat($user->last_login_at, 'd-m-Y'),
                    'inactive_deadline' => GeneralHelper::dateFormat($user->inactive_deadline, 'd-m-Y')
                ]
            ]));
        }

        $users = User::where('blocked', 0)
            ->where('inactive_notified', 1)
            ->whereNotNull('inactive_deadline')
            ->whereDate('inactive_deadline', '<', Carbon::now())
            ->get();
        
        foreach ($users as $user)
        {
            Log::debug(__FUNCTION__ . ' -> User: ' . $user->name . ' has been auto blocked by the system.');

            $user->blocked = 1;
            $user->save();

            $data = [];
            $notifyUsers = new Collection();

            $data['role_id'] = 1;
            $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));
            
            $data['country_code'] = ($user->permissions->first()) ? $user->permissions->first()->country['code'] : null;
            $data['role_id'] = 2;
            $notifyUsers = $notifyUsers->concat(self::getUsersByCountryAndRole($data));

            foreach ($notifyUsers as $notifyUser)
            {
                // Exclude inactive user from email notification
                if ($user->name == $notifyUser->user->name) {
                    continue;
                }

                $notifyUser->user->notify(new NotifyUser([
                    'from' => env('MAIL_FROM_ADDRESS', config('constants.MAIL_FROM_ADDRESS')),
                    'subject' => 'User Inactive - Auto Block',
                    'markdown' => 'user-inactive-autoblock',
                    'maildata' => [
                        'url' => env('APP_URL') . '/user/management',
                        'user' => $notifyUser->user->name,
                        'inactive_days' => env('USER_INACTIVE'),
                        'inactive_user' => $user->name,
                        'inactive_email' => $user->email,
                        'inactive_last_login' => GeneralHelper::dateFormat($user->last_login_at, 'd-m-Y')
                    ]
                ]));
            }
        }
    }

    public function isAdmin()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->role->name == 'admin') {
                return true;
            }
        }

        return false;
    }

    public function isPrimaryPoC()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->role->name == 'Primary PoC') {
                return true;
            }
        }

        return false;
    }

    public function isPoC()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->role->name == 'Primary PoC' ||
                $permission->role->name == 'PoC')
            {
                return true;
            }
        }

        return false;
    }

    public function isOperator()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->role->name == 'operator') {
                return true;
            }
        }

        return false;
    }

    public function isViewer()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->role->name == 'viewer') {
                return true;
            }
        }

        return false;
    }

    public function isEnisa()
    {
        foreach ($this->permissions as $permission)
        {
            if ($permission->country->name == config('constants.USER_GROUP')) {
                return true;
            }
        }

        return false;
    }
}
