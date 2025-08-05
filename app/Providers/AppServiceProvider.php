<?php

namespace App\Providers;

use App\HelperFunctions\TaskHelper;
use App\Models\Task;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $languages = [
            "Български" => "BG",
            "čeština" => "CS",
            "Hrvatski" => "HR",
            "dansk" => "DA",
            "Nederlands" => "NL",
            "ελληνικά" => "EL",
            "English" => "EN",
            "eesti" => "ET",
            "Suomi" => "FI",
            "Français" => "FR",
            "Deutsch" => "DE",
            "magyar" => "HU",
            "Íslenska" => "IS",
            "italiano" => "IT",
            "Latviešu" => "LV",
            "lietuvių" => "LT",
            "Malti" => "MT",
            "Norsk" => "NO",
            "polski" => "PL",
            "Português" => "PT",
            "Română" => "RO",
            "slovenčina" => "SK",
            "Slovenščina" => "SL",
            "Español" => "ES",
            "Svenska" => "SV",
            "Türkçe" => "TR"
        ];

        View::share('languages', $languages);

        if (app()->environment('local')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        Queue::before(function (JobProcessing $event) {
            //
        });

        Queue::after(function (JobProcessed $event) {
            $payload = $event->job->payload();
            $data = unserialize($payload['data']['command']);

            $type = $payload['displayName'];
            $user_id = $data->user->id ?? null;
            $index_configuration_id = $data->index->id ?? null;
            $year = $data->year ?? null;

            $task = TaskHelper::getTask([
                'type' => $type,
                'user_id' => $user_id,
                'index_configuration_id' => $index_configuration_id,
                'year' => $year
            ]);

            if ($task) {
                Task::where('type', $task->type)
                    ->where('user_id', $task->user_id)
                    ->where('index_configuration_id', $task->index_configuration_id)
                    ->where('year', $task->year)
                    ->update(
                        [
                            'status_id' => 2
                        ]
                    );
            }
        });

        Queue::failing(function (JobFailed $event) {
            $payload = $event->job->payload();
            $data = unserialize($payload['data']['command']);

            $type = $payload['displayName'];
            $user_id = $data->user->id ?? null;
            $index_configuration_id = $data->index->id ?? null;
            $year = $data->year ?? null;

            $task = TaskHelper::getTask([
                'type' => $type,
                'user_id' => $user_id,
                'index_configuration_id' => $index_configuration_id,
                'year' => $year
            ]);

            if ($task)
            {
                $payload = [
                    'last_exception' => $event->exception->getMessage()
                ];

                Task::where('type', $task->type)
                    ->where('user_id', $task->user_id)
                    ->where('index_configuration_id', $task->index_configuration_id)
                    ->where('year', $task->year)
                    ->update(
                        [
                            'status_id' => 3,
                            'payload' => ($task->payload) ? array_merge($task->payload, $payload) : $payload
                        ]
                    );
            }
        });
    }
}
