<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Task extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'payload' => 'array',
    ];
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public function transformAudit(array $data): array
    {
        $task = Task::find($this->getAttribute('id'));
        $data['auditable_name'] = $task->payload['auditable_name'];

        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class);
    }
}
