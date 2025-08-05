<?php

namespace App\Http\Livewire;

use App\HelperFunctions\TaskHelper;
use App\Models\Questionnaire;
use Livewire\Component;

class QuestionnaireIndicatorValues extends Component
{
    public $questionnaire;

    public function render()
    {
        $published_questionnaires = Questionnaire::getPublishedQuestionnaires();
        $latest_questionnaire_data = Questionnaire::getLatestPublishedQuestionnaire();
        $task = TaskHelper::getTask([
            'type' => 'IndicatorValuesCalculation',
            'index_configuration_id' => $this->questionnaire->configuration->id
        ]);
        
        return view('livewire.questionnaire-indicator-values', [
            'published_questionnaires' => $published_questionnaires,
            'loaded_questionnaire_data' => $this->questionnaire,
            'latest_questionnaire_data' => $latest_questionnaire_data,
            'task' => $task
        ]);
    }
}
