<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Permission extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'user_id',
        'name',
        'role_id',
        'country_id'
    ];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $user_id = Permission::find($this->getAttribute('id'))->user_id;
        $data['auditable_name'] = User::find($user_id)->name;

        if (Arr::has($data, 'new_values.country_id')) {
            $data['new_values']['country'] = Country::find($this->getAttribute('country_id'))->name;
            $data['description'] = "The user's country has been changed to " . $data['new_values']['country'];
        }

        if (Arr::has($data, 'new_values.role_id')) {
            $data['new_values']['role'] = Role::find($this->getAttribute('role_id'))->name;
            $newRoleDescription = "The user's role has been changed to " . $data['new_values']['role'];
            $data['description'] = Arr::has($data, 'new_values.country_id') ? 
                $data['description'] . '. ' . $newRoleDescription : $newRoleDescription;
        }

        unset($data['new_values']['country_id'],
              $data['new_values']['name'],
              $data['new_values']['role_id']);

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
}
