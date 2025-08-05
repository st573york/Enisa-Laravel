<?php

namespace App\Http\Livewire;

use App\HelperFunctions\TaskHelper;
use App\Models\IndexConfiguration;
use App\Models\Questionnaire;
use Livewire\Component;

class DataCollection extends Component
{
    public $index;

    public function render()
    {
        $published_indexes = IndexConfiguration::getPublishedConfigurations();
        $latest_index_data = IndexConfiguration::getLatestPublishedConfiguration();
        $questionnaire = Questionnaire::getPublishedQuestionnaires($this->index);
        $task = TaskHelper::getTask([
            'type' => 'IndexCalculation',
            'index_configuration_id' => $this->index->id
        ]);

        return view('livewire.data-collection', [
            'published_indexes' => $published_indexes,
            'loaded_index_data' => $this->index,
            'latest_index_data' => $latest_index_data,
            'questionnaire' => $questionnaire->first(),
            'task' => $task
        ]);
    }
}
