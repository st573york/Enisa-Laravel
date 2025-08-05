<?php

namespace App\Http\Livewire;

use App\HelperFunctions\TaskHelper;
use App\Models\IndexConfiguration;
use Livewire\Component;

class ExternalDataCollection extends Component
{
    public $index;

    public function render()
    {
        $published_indexes = IndexConfiguration::getPublishedConfigurations();
        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        $task = TaskHelper::getTask([
            'type' => 'ExternalDataCollection',
            'index_configuration_id' => $this->index->id
        ]);
        
        return view('livewire.external-data-collection', [
            'published_indexes' => $published_indexes,
            'loaded_index_data' => $this->index,
            'latest_index_data' => $latest_index_data,
            'task' => $task
        ]);
    }
}
