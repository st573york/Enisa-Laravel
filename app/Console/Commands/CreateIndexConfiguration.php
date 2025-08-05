<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IndexConfiguration;
use App\Models\User;

class CreateIndexConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:create {--N|name= : The index name} {--D|description= : The index description} {--Y|year= : The index year} {--P|published= : The index published}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an index configuration for given name, description, year and published';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->option('name');
        $description = $this->option('description');
        $published = (!is_null($this->option('published'))) ? $this->option('published') : 1;
        $year = (!is_null($this->option('year'))) ? $this->option('year') : intval(date('Y'));
        $contents = IndexConfiguration::generateIndexConfigurationTemplate($year);

        $user = User::with('permissions')->whereHas('permissions', function ($query) {
            $query->where('role_id', 1);
        })->first();

        IndexConfiguration::create([
            'name' => $name,
            'description' => $description,
            'draft' => !$published,
            'json_data' => $contents,
            'eu_published' => true,
            'ms_published' => true,
            'collection_started' => date('Y-m-d H:i:s'),
            'collection_deadline' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $user->id,
            'year' => $year
        ]);

        return Command::SUCCESS;
    }
}
