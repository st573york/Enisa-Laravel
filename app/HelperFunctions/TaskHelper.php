<?php

namespace App\HelperFunctions;

use App\Models\Task;

class TaskHelper
{
    public static function getTaskType($data)
    {
        $type = $data['type'];
        if (preg_match('/App\\\\/', $type)) {
            $parts = explode('\\', $type);
            $type = $parts[2];
        }

        return $type;
    }

    public static function getTask($data)
    {
        return Task::where('type', self::getTaskType($data))
            ->when(isset($data['user_id']), function ($query) use ($data) {
                $query->where('user_id', $data['user_id']);
            })
            ->when(isset($data['index_configuration_id']), function ($query) use ($data) {
                $query->where('index_configuration_id', $data['index_configuration_id']);
            })
            ->when(isset($data['year']), function ($query) use ($data) {
                $query->where('year', $data['year']);
            })
            ->latest()
            ->first();
    }

    public static function updateOrCreateTask($data)
    {
        $data['type'] = self::getTaskType($data);

        $user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $index_configuration_id = (isset($data['index_configuration_id'])) ? $data['index_configuration_id'] : null;
        $year = (isset($data['year'])) ? $data['year'] : null;

        $task = self::getTask($data);

        if (isset($data['payload'])) {
            $data['payload'] = ($task && $task->payload) ? array_merge($task->payload, $data['payload']) : $data['payload'];
        }

        Task::disableAuditing();
        $task = Task::updateOrCreate(
            [
                'type' => $data['type'],
                'user_id' => $user_id,
                'index_configuration_id' => $index_configuration_id,
                'year' => $year
            ],
            $data
        );
        Task::enableAuditing();

        return $task;
    }

    public static function deleteTask($task)
    {
        Task::disableAuditing();
        Task::find($task->id)->delete();
        Task::enableAuditing();
    }
}
