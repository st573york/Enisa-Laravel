<?php

namespace Tests\Browser\Pages\Survey;

use Tests\Browser\Components\Accordion;
use Tests\Browser\Components\Alert;
use Tests\Browser\Components\Loader;
use Tests\Browser\Components\Button;
use Tests\Browser\Components\Checkbox;
use Tests\Browser\Components\InputField;
use Tests\Browser\Components\RadioButton;
use Tests\Browser\Components\TinyMCE;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class View extends Page
{
    const WAIT_FOR_SECONDS = 10;
    const SURVEY_START_OR_RESUME = '@survey_start_or_resume';
    const SURVEY_NAVIGATION = '@survey_navigation';
    const SURVEY_NAVIGATION_DROPDOWN_MENU = '@survey_navigation_dropdown_menu';
    const SURVEY_UNSAVED_CHANGES_MODAL = '@survey_unsaved_changes_modal';
    const SURVEY_UNSAVED_CHANGES_MODAL_CLOSE = '@survey_unsaved_changes_modal_close';
    const SURVEY_UNSAVED_CHANGES_MODAL_DISCARD = '@survey_unsaved_changes_modal_discard';
    const SURVEY_UNSAVED_CHANGES_MODAL_SAVE = '@survey_unsaved_changes_modal_save';
    const SURVEY_REVIEW_AND_SUBMIT = '@survey_review_and_submit';
    const SURVEY_REVIEW_AND_SUBMIT_MODAL = '@survey_review_and_submit_modal';
    const SURVEY_REVIEW_AND_SUBMIT_MODAL_CLOSE = '@survey_review_and_submit_modal_close';
    const SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT = '@survey_review_and_submit_modal_submit';

    const YOU = '(you)';
    const FORM_INDICATOR = '#form-indicator-';
    const ACCEPT_REQUEST_CHANGES = 'accept_request_changes';
    const REQUEST_CHANGES = 'request_changes';
    const REQUESTED_CHANGES = 'requested_changes';
    const APPROVED = 'approved';
    const FINAL_APPROVED = 'final_approved';

    protected $id;
    protected $title;
    protected $indicators;
    protected $user;
    protected $author;
    protected $indicator_number;

    public function __construct($id, $title, $indicators, $user, $author = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->indicators = $indicators;
        $this->user = $user;
        $this->author = (!is_null($author)) ? $author : $user;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/questionnaire/view/' . $this->id;
    }

    /**
     * Set the author user.
     */
    public function setAuthorUser($author): void
    {
        $this->author = $author;
    }

    /**
     * Set the indicator number.
     */
    public function setIndicatorNumber($browser): void
    {
        $indicator_number = $browser->script("return $(\".wizard-fieldset.show\").find('.form-indicator-id').text();");
        $this->indicator_number = $indicator_number[0];
    }

    /**
     * Update the survey inputs by indicator.
     */
    public function updateSurveyInputsByIndicator($order, $indicator_id, $indicator_inputs): array
    {
        $inputs = [];
        
        foreach ($indicator_inputs as $key => $value)
        {
            if (!in_array($key, ['questions', 'comments', 'rating'])) {
                $inputs[$key] = $value;
            }
        }

        if (isset($indicator_inputs['questions']))
        {
            $configuration_json_file = storage_path() . '/app/test_files/dusk-survey-indicator-' . $order . '.json';
            $configuration_json = json_decode(file_get_contents($configuration_json_file), true);

            $qcount = 0;

            foreach ($configuration_json['form'] as $form)
            {
                foreach ($form['contents'] as $question)
                {
                    if (!isset($indicator_inputs['questions'][++$qcount])) {
                        continue;
                    }

                    $qnumber = $indicator_id . '.' . $qcount;

                    $firstKey = array_key_first($indicator_inputs['questions'][$qcount]);
                    if ($firstKey == 'accordion') {
                        $inputs['questions'][$qnumber]['accordion'] = true;
                    }

                    $inputs['questions'][$qnumber]['type'] = $question['type'];
                    $inputs['questions'][$qnumber]['compatible'] = (isset($indicator_inputs['questions'][$qcount]['compatible'])) ? $indicator_inputs['questions'][$qcount]['compatible'] : $question['compatible'];
                    
                    if (isset($indicator_inputs['questions'][$qcount]['validation'])) {
                        $inputs['questions'][$qnumber]['validation'] = $indicator_inputs['questions'][$qcount]['validation'];
                    }

                    if (isset($indicator_inputs['questions'][$qcount]['choice']))
                    {
                        $value = $indicator_inputs['questions'][$qcount]['choice'];
                        $id = self::FORM_INDICATOR . $indicator_id . '-choice-' . $form['order'] . '-' . $question['order'] . '-' . $value;

                        $inputs['questions'][$qnumber]['choice'] = [
                            'id' => $id,
                            'value' => $value
                        ];
                    }

                    if (isset($indicator_inputs['questions'][$qcount]['answers']))
                    {
                        $answers = [];
                        foreach ($indicator_inputs['questions'][$qcount]['answers'] as $value)
                        {
                            $type = '';
                            foreach ($question['options'] as $option)
                            {
                                if ($option['value'] == $value)
                                {
                                    $type = (isset($option['type'])) ? $option['type'] : $type;

                                    break;
                                }
                            }

                            array_push($answers, [
                                'id' => self::FORM_INDICATOR . $indicator_id . '-answers-' . $form['order'] . '-' . $question['order'] . '-' . $value,
                                'value' => $value,
                                'type' => $type
                            ]);
                        }

                        $inputs['questions'][$qnumber]['answers'] = $answers;
                    }

                    if (isset($indicator_inputs['questions'][$qcount]['reference'])) {
                        $inputs['questions'][$qnumber]['reference'] = [
                            'id' => self::FORM_INDICATOR . $indicator_id . '-reference-source-' . $form['order'] . '-' . $question['order'],
                            'value' => $indicator_inputs['questions'][$qcount]['reference']
                        ];
                    }

                    $lastKey = array_key_last($indicator_inputs['questions'][$qcount]);
                    if ($lastKey == 'accordion') {
                        $inputs['questions'][$qnumber]['accordion'] = true;
                    }
                }
            }
        }

        if (isset($indicator_inputs['comments'])) {
            $inputs['comments'] = [
                'id' => self::FORM_INDICATOR . $indicator_id . '-comments',
                'value' => $indicator_inputs['comments']
            ];
        }
        
        if (isset($indicator_inputs['rating'])) {
            $inputs['rating'] = [
                'id' => self::FORM_INDICATOR . $indicator_id . '-rating-' . $indicator_inputs['rating'],
                'value' => $indicator_inputs['rating']
            ];
        }

        return $inputs;
    }

    /**
     * Assert the survey view page.
     */
    public function assert(Browser $browser, $start_or_resume = 'Start'): void
    {
        $browser->on(new Loader(self::WAIT_FOR_SECONDS));
        $this->assertPath($browser);
        $browser->assertSee($this->title)
                ->within('@survey_participant_info', function ($browser) {
                    $browser->assertSee('Participant Information')
                            ->assertDisabled('@survey_participant_name')
                            ->assertDisabled('@survey_participant_email')
                            ->assertDisabled('@survey_participant_country');
                })
                ->within('@survey_background_and_scope', function ($browser) use ($start_or_resume) {
                    $browser->assertSee('Background and scope')
                            ->assertSeeIn(self::SURVEY_START_OR_RESUME, $start_or_resume)
                            ->assertEnabled(self::SURVEY_START_OR_RESUME);
                });
    }

    public function assertPath(Browser $browser): void
    {
        $browser->waitForLocation($this->url(), self::WAIT_FOR_SECONDS);
    }

    public function assertSurveyNavigationStatus(Browser $browser, $indicator_id, $indicator_title, $indicator_inputs): void
    {
        $browser->within(self::SURVEY_NAVIGATION_DROPDOWN_MENU, function ($stepsTab) use ($indicator_id, $indicator_title, $indicator_inputs) {
            $stepsTab->waitForTextIn('@survey_step_choice_indicator_' . $indicator_id, $indicator_title);
            $stepsTab->assertScript("$(\"[dusk='" . substr('@survey_step_choice_indicator_' . $indicator_id, 1) . "']\").parent().hasClass('" . $indicator_inputs['status'] . "');", true);
            if ($this->user->name == $indicator_inputs['assignee']) {
                $stepsTab->assertScript("$(\"[dusk='" . substr('@survey_step_choice_indicator_' . $indicator_id, 1) . "']\").hasClass('assigned');", true);
            }
            else {
                $stepsTab->assertScript("$(\"[dusk='" . substr('@survey_step_choice_indicator_' . $indicator_id, 1) . "']\").hasClass('not_assigned');", true);
            }
        });
    }

    public function assertSurveyRequestedChangesHistory(Browser $browser, $indicator_id, $requested_changes): void
    {
        $browser->within('@survey_requested_changes_history_dropdown_menu_' . $indicator_id, function ($history) use ($requested_changes) {
            $history->assertSee($requested_changes);
        });
    }

    public function assertIndicatorRequestChangesAlert(Browser $browser): void
    {
        $browser->on(new Alert('Requested changes have NOT been sent to the assignee yet. Browse to Survey Dashboard to submit the requested changes for ALL indicators.'));
    }

    public function assertIndicatorState(Browser $browser, $indicator_id, $indicator_data): void
    {
        $author_name = ($this->user->permissions->first()->role_id != 1 && $this->author->permissions->first()->role_id == 1)
            ? config('constants.USER_GROUP') : $this->author->name;

        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id, $indicator_data, $author_name) {
            // Accept/Request changes
            if ($indicator_data['state'] == 'accept_request_changes')
            {
                $browser->assertSeeIn('@survey_indicator_accept_' . $indicator_id, 'Accept')
                        ->assertEnabled('@survey_indicator_accept_' . $indicator_id)
                        ->assertSeeIn('@survey_indicator_request_changes_' . $indicator_id, 'Request changes')
                        ->assertEnabled('@survey_indicator_request_changes_' . $indicator_id);
            }
            // Request changes
            elseif ($indicator_data['state'] == 'request_changes')
            {
                $browser->assertSeeIn('@survey_indicator_request_changes_title_' . $indicator_id, 'Request changes.')
                        ->assertInputValue('@survey_indicator_request_changes_deadline_' . $indicator_id, $indicator_data['deadline']);
            }
            // Requested changes
            elseif ($indicator_data['state'] == 'requested_changes')
            {
                $browser->assertSeeIn('@survey_indicator_requested_changes_title_' . $indicator_id, 'Changes have been requested.')
                        ->assertSeeIn('@survey_indicator_requested_changes_author_' . $indicator_id, $author_name . (($this->user->name == $this->author->name) ? ' ' . self::YOU : ''))
                        ->assertSeeIn('@survey_indicator_requested_changes_deadline_' . $indicator_id, $indicator_data['deadline'])
                        ->assertScript("tinymce.get('request-requested-changes-" . $indicator_id . "').getContent({format: 'text'});", $indicator_data['changes']);
                if (isset($indicator_data['discard']) &&
                    $indicator_data['discard'] == 'visible')
                {
                    $browser->assertSeeIn('@survey_indicator_requested_changes_discard_' . $indicator_id, 'Discard')
                            ->assertEnabled('@survey_indicator_requested_changes_discard_' . $indicator_id);
                }
                else {
                    $browser->assertMissing('@survey_indicator_requested_changes_discard_' . $indicator_id);
                }
                if (isset($indicator_data['edit']) &&
                    $indicator_data['edit'] == 'visible')
                {
                    $browser->assertSeeIn('@survey_indicator_requested_changes_edit_' . $indicator_id, 'Edit')
                            ->assertEnabled('@survey_indicator_requested_changes_edit_' . $indicator_id);
                }
                else {
                    $browser->assertMissing('@survey_indicator_requested_changes_edit_' . $indicator_id);
                }
            }
            // Approved
            elseif (in_array($indicator_data['state'], ['approved', 'final_approved'])) {
                $browser->assertSeeIn('@survey_indicator_approved_title_' . $indicator_id, 'Indicator has been accepted.')
                        ->assertSeeIn('@survey_indicator_approved_author_' . $indicator_id, $author_name . (($this->user->name == $this->author->name) ? ' ' . self::YOU : ''));
            }
        });
    }

    public function assertIndicatorAssignee(Browser $browser, $indicator_id, $indicator_assignee): void
    {
        $browser->assertSeeIn('@survey_indicator_assigned_to_' . $indicator_id, $indicator_assignee . (($this->user->name == $indicator_assignee) ? ' ' . self::YOU : ''));
        if ($this->user->name == $indicator_assignee) {
            $browser->assertScript("$(\"[dusk='" . substr('@survey_indicator_assigned_to_' . $indicator_id, 1) . "']\").hasClass('assigned');", true);
        }
        else {
            $browser->assertScript("$(\"[dusk='" . substr('@survey_indicator_assigned_to_' . $indicator_id, 1) . "']\").hasClass('not_assigned');", true);
        }
    }

    public function assertIndicatorNumberAndTitle(Browser $browser, $indicator_id, $indicator_title): void
    {
        $browser->within('@survey_indicator_number_and_title_' . $indicator_id, function ($browser) use ($indicator_title) {
            $browser->assertSee($this->indicator_number . '. ' . $indicator_title);
        });
    }

    public function assertIndicatorInputsAfterSelect(Browser $browser, $selector, $exclude_answer = null): void
    {
        $not = '';
        if (!is_null($exclude_answer))
        {
            $checked = $browser->script("return $(\"" . $exclude_answer . "\").is(':checked');");

            if ($checked[0]) {
                $not .= ':not(' . $exclude_answer . ')';
            }
        }

        $all_exclude = $browser->script("return $(\"[dusk='" . $selector . "'] input" . $not . "\").length;");

        $browser->assertScript("$(\"[dusk='" . $selector . "'] input:checked\").length;", (empty($not) ? 0 : 1))
                ->assertScript("$(\"[dusk='" . $selector . "'] input:disabled\").length;", ((empty($not) && !is_null($exclude_answer)) ? 0 : $all_exclude[0]));
    }

    public function assertIndicatorInputsAfterAction(Browser $browser, $order, $indicator_id, $indicator_inputs, $action): void
    {
        $indicator_inputs = $this->updateSurveyInputsByIndicator($order, $indicator_id, $indicator_inputs);
        
        $browser->within('@survey_indicator_' . $indicator_id, function ($browser) use ($indicator_inputs, $action, $indicator_id) {
            $assigned_or_not = ($this->user->name == $indicator_inputs['assignee']) ? 'assigned' : 'not_assigned';
            $assignee_info = ($assigned_or_not == 'assigned') ? '(you)' : '';

            foreach ($indicator_inputs as $key => $inputs)
            {
                if ($key == 'questions')
                {
                    foreach ($inputs as $qnumber => $question)
                    {
                        $is_collapsed = $browser->script("return $(\"[dusk='survey_accordion_button_" . $qnumber . "']\").hasClass('collapsed');");
                        if ($is_collapsed[0]) {
                            $browser->on(new Accordion('@survey_accordion_button_' . $qnumber, '@survey_accordion_collapse_' . $qnumber))
                                    ->scrollAndClickAccordion()
                                    ->waitFor('@survey_accordion_collapse_' . $qnumber);
                        }

                        $browser->within('@survey_indicator_question_' . $qnumber, function ($browser) use ($indicator_inputs, $action, $qnumber, $question, $assigned_or_not, $assignee_info) {
                            if ($action == 'pre-fill')
                            {
                                if ($question['compatible']) {
                                    $browser->assertSeeIn('@survey_indicator_answers_loaded', 'Answered by: ' . $indicator_inputs['assignee'] . ' ' . $assignee_info . ' - on ' . $indicator_inputs['last_saved']);
                                }
                                else {
                                    $browser->assertSeeIn('@survey_indicator_answers_not_loaded', 'Unable to load previous answers. The possible answers have changed since last year.');
                                }
                            }
                            else {
                                $browser->assertMissing('@survey_indicator_answers_loaded');
                            }

                            $choice = $question['choice']['value'];

                            $browser->scrollIntoView('@survey_indicator_choice_' . $qnumber)
                                    ->within('@survey_indicator_choice_' . $qnumber, function ($browser) use ($question, $choice, $assigned_or_not) {
                                        $browser->assertEnabled($question['choice']['id'])
                                                ->assertRadioSelected($question['choice']['id'], $choice)
                                                ->assertScript("$(\"" . $question['choice']['id'] . "\").hasClass('" . $assigned_or_not . "');", true);
                            });
                            $browser->scrollIntoView('@survey_indicator_answers_' . $qnumber)
                                    ->within('@survey_indicator_answers_' . $qnumber, function ($browser) use ($action, $qnumber, $question, $choice, $assigned_or_not) {
                                        $not = '';

                                        if ($question['type'] == 'multiple-choice')
                                        {
                                            if (empty($question['answers'])) {
                                                $browser->assertScript("$(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input:checked\").length;", 0);
                                            }
                                            else
                                            {
                                                foreach ($question['answers'] as $answer)
                                                {
                                                    $browser->assertEnabled($answer['id'])
                                                            ->assertChecked($answer['id'])
                                                            ->assertScript("$(\"" . $answer['id'] . "\").hasClass('" . $assigned_or_not . "');", true);

                                                    $not .= ($choice != '3') ? ':not(' . $answer['id'] . ')' : '';
                                                }

                                                $browser->assertScript("$(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input:checked\").length;", count($question['answers']));
                                            }
                                        }
                                        elseif ($question['type'] == 'single-choice')
                                        {
                                            if (empty($question['answers'])) {
                                                $browser->assertScript("$(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input:checked\").length;", 0);
                                            }
                                            else
                                            {
                                                $browser->assertEnabled($question['answers'][0]['id'])
                                                        ->assertRadioSelected($question['answers'][0]['id'], $question['answers'][0]['value'])
                                                        ->assertScript("$(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input:checked\").length;", 1)
                                                        ->assertScript("$(\"" . $question['answers'][0]['id'] . "\").hasClass('" . $assigned_or_not . "');", true);

                                                $not .= ($choice != '3') ? ':not(' . $question['answers'][0]['id'] . ')' : '';
                                            }
                                        }

                                        if ($action == 'submit')
                                        {
                                            $all_exclude = $browser->script("return $(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input" . $not . "\").length;");
                                            $browser->assertScript("$(\"[dusk='survey_indicator_answers_" . $qnumber . "'] input:disabled\").length;", $all_exclude[0]);
                                        }
                            });
                            $browser->scrollIntoView('@survey_indicator_reference_source_' . $qnumber)
                                    ->within('@survey_indicator_reference_source_' . $qnumber, function ($browser) use ($action, $question, $choice, $assigned_or_not) {
                                        if ($action == 'submit') {
                                            $browser->assertDisabled($question['reference']['id']);
                                        }
                                        else
                                        {
                                            if ($choice != '3') {
                                                $browser->assertEnabled($question['reference']['id']);
                                            }
                                            else {
                                                $browser->assertMissing($question['reference']['id']);
                                            }
                                        }
                                        $browser->assertInputValue($question['reference']['id'], $question['reference']['value'])
                                                ->assertScript("$(\"" . $question['reference']['id'] . "\").hasClass('" . $assigned_or_not . "');", true);
                            });
                        });
                    }
                }
                elseif ($key == 'rating')
                {
                    $browser->scrollIntoView('@survey_indicator_rating_wrapper_' . $indicator_id)
                            ->within('@survey_indicator_rating_wrapper_' . $indicator_id, function ($browser) use ($indicator_inputs, $action, $indicator_id, $inputs, $assigned_or_not, $assignee_info) {
                                if ($action == 'pre-fill') {
                                    $browser->assertSeeIn('@survey_indicator_rating_loaded', 'Rating provided by: ' . $indicator_inputs['assignee'] . ' ' . $assignee_info . ' - on ' . $indicator_inputs['last_saved']);
                                }
                                else {
                                    $browser->assertMissing('@survey_indicator_rating_loaded');
                                }

                                if ($inputs['value'] == 0) {
                                    $browser->assertScript("$(\"[dusk='survey_indicator_rating_" . $indicator_id . "'] input:checked\").length;", 0);
                                }
                                else
                                {
                                    $browser->assertScript("$(\"" . $inputs['id'] . "\").is(':checked');", true)
                                            ->assertScript("$(\"[dusk='survey_indicator_rating_" . $indicator_id . "'] input:checked\").length;", 1)
                                            ->assertScript("$(\"[dusk='survey_indicator_rating_" . $indicator_id . "']\").hasClass('" . $assigned_or_not . "');", true);
                                }

                                if ($action == 'submit')
                                {
                                    $all_exclude = $browser->script("return $(\"[dusk='survey_indicator_rating_" . $indicator_id . "'] input:not(" . $inputs['id'] . ")\").length;");
                                    $browser->assertScript("$(\"[dusk='survey_indicator_rating_" . $indicator_id . "'] input:disabled\").length;", $all_exclude[0]);
                                }
                    });
                }
                elseif ($key == 'comments')
                {
                    $browser->scrollIntoView('@survey_indicator_comments_wrapper_' . $indicator_id)
                            ->within('@survey_indicator_comments_wrapper_' . $indicator_id, function ($browser) use ($indicator_inputs, $action, $inputs, $assigned_or_not, $assignee_info) {
                                if ($action == 'pre-fill') {
                                    $browser->assertSeeIn('@survey_indicator_comments_loaded', 'Comments provided by: ' . $indicator_inputs['assignee'] . ' ' . $assignee_info . ' - on ' . $indicator_inputs['last_saved']);
                                }
                                else {
                                    $browser->assertMissing('@survey_indicator_comments_loaded');
                                }
                                
                                if ($action == 'submit') {
                                    $browser->assertDisabled($inputs['id']);
                                }
                                else {
                                    $browser->assertEnabled($inputs['id']);
                                }
                                $browser->assertInputValue($inputs['id'], $inputs['value'])
                                        ->assertScript("$(\"" . $inputs['id'] . "\").hasClass('" . $assigned_or_not . "');", true);
                    });
                }
            }
        });
    }

    public function assertUnsavedChangesModal(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_UNSAVED_CHANGES_MODAL, function ($modal) {
            $modal->assertSeeIn('@survey_unsaved_changes_modal_title', 'You have unsaved changes!')
                  ->assertSeeIn('@survey_unsaved_changes_modal_message', 'This page has unsaved changes that will be lost. Save or discard the answers before leaving this page.')
                  ->assertSeeIn(self::SURVEY_UNSAVED_CHANGES_MODAL_CLOSE, 'Close')
                  ->assertEnabled(self::SURVEY_UNSAVED_CHANGES_MODAL_CLOSE)
                  ->assertSeeIn(self::SURVEY_UNSAVED_CHANGES_MODAL_DISCARD, 'Discard changes')
                  ->assertEnabled(self::SURVEY_UNSAVED_CHANGES_MODAL_DISCARD)
                  ->assertSeeIn(self::SURVEY_UNSAVED_CHANGES_MODAL_SAVE, 'Save changes')
                  ->assertEnabled(self::SURVEY_UNSAVED_CHANGES_MODAL_SAVE);
        });
    }

    public function assertReviewAndSubmitModal(Browser $browser, $data): void
    {
        $browser->whenAvailable(self::SURVEY_REVIEW_AND_SUBMIT_MODAL, function ($modal) use ($data) {
            $modal->assertSeeIn('@survey_review_and_submit_modal_title', $this->title);
            foreach ($data['sections'] as $section => $section_data)
            {
                $modal->within($section, function ($modal) use ($section_data) {
                    if (isset($section_data['message'])) {
                        $modal->assertSee($section_data['message']);
                    }
                    if (isset($section_data['indicators']))
                    {
                        foreach ($section_data['indicators'] as $indicator) {
                            $modal->assertSee($this->indicators[$indicator]);
                        }
                    }
                });
            }
            $modal->assertSeeIn(self::SURVEY_REVIEW_AND_SUBMIT_MODAL_CLOSE, 'Close')
                  ->assertEnabled(self::SURVEY_REVIEW_AND_SUBMIT_MODAL_CLOSE)
                  ->assertSeeIn(self::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT, 'Submit');
            if ($data['submit'] == 'enabled') {
                $modal->assertEnabled(self::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            }
            elseif ($data['submit'] == 'disabled') {
                $modal->assertDisabled(self::SURVEY_REVIEW_AND_SUBMIT_MODAL_SUBMIT);
            }
        });
    }

    public function assertIndicatorInvalidAnswer(Browser $browser, $selector, $message): void
    {
        $browser->assertVisible($selector)
                ->assertSeeIn($selector, $message);
    }

    public function startOrResume(Browser $browser): void
    {
        $browser->on(new Button(self::SURVEY_START_OR_RESUME))
                ->scrollAndClickButton('bottom');
    }

    public function loadResetAnswers(Browser $browser, $indicator_id, $text, $expected_text): void
    {
        $browser->on(new Button('@survey_indicator_load_reset_answers_' . $indicator_id, $text))
                ->scrollAndClickButton('top')
                ->waitForTextIn('@survey_indicator_load_reset_answers_' . $indicator_id, $expected_text, self::WAIT_FOR_SECONDS);
        if (preg_match('/Pre-fill/', $text)) {
            $browser->assertVisible('@survey_indicator_last_saved_' . $indicator_id);
        }
    }

    public function fillInOnlineIndicator(Browser $browser, $order, $indicator_id, $indicator_inputs): void
    {
        $this->setIndicatorNumber($browser);

        $indicator_assignee = $indicator_inputs['assignee'];
        $indicator_title = $this->indicators[$order - 1];

        $indicator_inputs = $this->updateSurveyInputsByIndicator($order, $indicator_id, $indicator_inputs);

        // Indicator assignee
        $this->assertIndicatorAssignee($browser, $indicator_id, $indicator_assignee);
        // Indicator number and title
        $this->assertIndicatorNumberAndTitle($browser, $indicator_id, $indicator_title);
        
        // Last saved
        if (isset($indicator_inputs['last_saved']))
        {
            if ($indicator_inputs['last_saved']) {
                $browser->assertVisible('@survey_indicator_last_saved_' . $indicator_id);
            }
            else {
                $browser->assertMissing('@survey_indicator_last_saved_' . $indicator_id);
            }
        }
        
        // Indicator
        $browser->within('@survey_indicator_' . $indicator_id, function ($browser) use ($indicator_id, $indicator_inputs) {
            // Questions
            if (isset($indicator_inputs['questions']))
            {
                foreach ($indicator_inputs['questions'] as $qnumber => $qdata)
                {
                    // Accordion - first
                    $firstKey = array_key_first($qdata);
                    if ($firstKey == 'accordion')
                    {
                        $browser->on(new Accordion('@survey_accordion_button_' . $qnumber, '@survey_accordion_collapse_' . $qnumber))
                                ->scrollAndClickAccordion()
                                ->waitFor('@survey_accordion_collapse_' . $qnumber);
                    }

                    $browser->within('@survey_indicator_question_' . $qnumber, function ($browser) use ($qnumber, $qdata) {
                        // Question
                        $parts = explode('.', $qnumber);
                        $browser->assertSeeIn('@survey_indicator_question_number_' . $qnumber, $this->indicator_number . '.' . $parts[1]);
                        
                        // Validation
                        if (isset($qdata['validation']['answers']['required']) &&
                            $qdata['validation']['answers']['required'])
                        {
                            $this->assertIndicatorInvalidAnswer($browser, '@survey_indicator_invalid_answers_' . $qnumber, 'The answers field is required.');
                        }
                        if (isset($qdata['validation']['reference']['required']) &&
                            $qdata['validation']['reference']['required'])
                        {
                            $this->assertIndicatorInvalidAnswer($browser, '@survey_indicator_invalid_reference_source_' . $qnumber, 'The reference source field is required.');
                        }
                        
                        // Choice
                        if (isset($qdata['choice']))
                        {
                            $checked = $browser->script("return $(\"" . $qdata['choice']['id'] . "\").is(':checked');");
                            if (!$checked[0])
                            {
                                $browser->on(new RadioButton($qdata['choice']['id'], $qdata['choice']['value']))
                                        ->scrollAndClickRadioButton();
                                if ($qdata['choice']['value'] == '3')
                                {
                                    $browser->waitUntilMissing($qdata['reference']['id']);
                                    $this->assertIndicatorInputsAfterSelect($browser, 'survey_indicator_answers_' . $qnumber);
                                }
                                $browser->assertMissing('@survey_indicator_answers_loaded');
                            }
                        }

                        // Answers
                        if (isset($qdata['answers']))
                        {
                            if ($qdata['type'] == 'multiple-choice')
                            {
                                foreach ($qdata['answers'] as $answer)
                                {
                                    $browser->on(new Checkbox($answer['id']))
                                            ->scrollAndClickCheckbox();

                                    if ($answer['type'] == 'master')
                                    {
                                        $this->assertIndicatorInputsAfterSelect($browser, 'survey_indicator_answers_' . $qnumber, $answer['id']);

                                        break;
                                    }
                                }
                            }
                            elseif ($qdata['type'] == 'single-choice') {
                                $browser->on(new RadioButton($qdata['answers'][0]['id'], $qdata['answers'][0]['value']))
                                        ->scrollAndClickRadioButton();
                            }
                            $browser->assertMissing('@survey_indicator_answers_loaded');
                        }

                        // Reference
                        if (isset($qdata['reference']) &&
                            (!isset($qdata['choice']) ||
                             (isset($qdata['choice']) &&
                              $qdata['choice']['value'] != '3')))
                        {
                            $browser->on(new InputField($qdata['reference']['id'], $qdata['reference']['value']))
                                    ->scrollAndTypeInputField();
                            $browser->assertMissing('@survey_indicator_answers_loaded');
                        }
                    });

                    // Accordion - last
                    $lastKey = array_key_last($qdata);
                    if ($lastKey == 'accordion')
                    {
                        $browser->on(new Accordion('@survey_accordion_button_' . $qnumber))
                                ->scrollAndClickAccordion()
                                ->waitUntilMissing('@survey_accordion_collapse_' . $qnumber);
                    }
                }
            }

            if (isset($indicator_inputs['validation']['rating']['required']) &&
                $indicator_inputs['validation']['rating']['required'])
            {
                $this->assertIndicatorInvalidAnswer($browser, '@survey_indicator_invalid_rating_' . $indicator_id, 'The rating field is required.');
            }

            // Rating
            if (isset($indicator_inputs['rating']))
            {
                $browser->scrollIntoView('label[for="' . substr($indicator_inputs['rating']['id'], 1) . '"]')
                        ->click('label[for="' . substr($indicator_inputs['rating']['id'], 1) . '"]');
                $browser->assertMissing('@survey_indicator_rating_loaded');
            }

            // Comments
            if (isset($indicator_inputs['comments']))
            {
                $browser->on(new InputField($indicator_inputs['comments']['id'], $indicator_inputs['comments']['value']))
                        ->scrollAndTypeInputField();
                $browser->assertMissing('@survey_indicator_comments_loaded');
            }
        });

        // Save button missing?
        if ($this->user->name != $indicator_assignee) {
            $browser->assertMissing('@survey_indicator_save_' . $indicator_id);
        }
    }

    public function clickSurveyNavigation(Browser $browser): void
    {
        $browser->on(new Button(self::SURVEY_NAVIGATION, 'Survey navigation'))
                ->scrollAndClickButton('top');
    }

    public function clickSurveyNavigationGoTo(Browser $browser, $indicator): void
    {
        $browser->within(self::SURVEY_NAVIGATION_DROPDOWN_MENU, function ($stepsTab) use ($indicator) {
            $stepsTab->on(new Button('@survey_step_choice_indicator_' . $indicator))
                     ->scrollAndClickButton();
        });
    }

    public function clickSurveyRequestedChangesHistory(Browser $browser, $indicator_id): void
    {
        $browser->on(new Button('@survey_requested_changes_history_' . $indicator_id, 'Requested changes history'))
                ->scrollAndClickButton('top');
    }

    public function clickAccept(Browser $browser, $indicator_id, $indicator_data = [], $check_state_after_accept = true): void
    {
        $this->author = $this->user;
        $indicator_data['state'] = self::ACCEPT_REQUEST_CHANGES;
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id) {
            $browser->on(new Button('@survey_indicator_accept_' . $indicator_id))
                    ->scrollAndClickButton('top')
                    ->waitUntilMissing('@survey_indicator_accept_' . $indicator_id, self::WAIT_FOR_SECONDS);
        });

        if ($check_state_after_accept)
        {
            $indicator_data['state'] = self::APPROVED;
            $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        }

        if (isset($indicator_data['changes']))
        {
            $this->clickSurveyRequestedChangesHistory($browser, $indicator_id);
            $this->assertSurveyRequestedChangesHistory($browser, $indicator_id, $indicator_data['changes']);
        }
    }

    public function clickRequestChanges(Browser $browser, $indicator_id, $indicator_data): void
    {
        $indicator_data['state'] = self::ACCEPT_REQUEST_CHANGES;
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id) {
            $browser->on(new Button('@survey_indicator_request_changes_' . $indicator_id))
                    ->scrollAndClickButton('top')
                    ->waitUntilMissing('@survey_indicator_request_changes_' . $indicator_id, self::WAIT_FOR_SECONDS);
        });
        $indicator_data['state'] = self::REQUEST_CHANGES;
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);

        if (isset($indicator_data['changes'])) {
            $browser->on(new TinyMCE('request-requested-changes-' . $indicator_id, $indicator_data['changes']))
                    ->typeTinyMCE();
        }
    }

    public function clickEditRequestedChanges(Browser $browser, $indicator_id, $indicator_data): void
    {
        $indicator_data['state'] = self::REQUESTED_CHANGES;
        $indicator_data['discard'] = $indicator_data['edit'] = 'visible';
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id) {
            $browser->on(new Button('@survey_indicator_requested_changes_edit_' . $indicator_id))
                    ->scrollAndClickButton('top')
                    ->waitUntilMissing('@survey_indicator_requested_changes_edit_' . $indicator_id, self::WAIT_FOR_SECONDS);
        });
        $indicator_data['state'] = self::REQUEST_CHANGES;
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);

        if (isset($indicator_data['edit_changes'])) {
            $browser->on(new TinyMCE('request-requested-changes-' . $indicator_id, $indicator_data['edit_changes']))
                    ->typeTinyMCE();
        }
    }

    public function clickDiscardRequestedChanges(Browser $browser, $indicator_id, $indicator_data): void
    {
        $indicator_data['state'] = self::REQUESTED_CHANGES;
        $indicator_data['discard'] = $indicator_data['edit'] = 'visible';
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id) {
            $browser->on(new Button('@survey_indicator_requested_changes_discard_' . $indicator_id))
                    ->scrollAndClickButton('top')
                    ->waitUntilMissing('@survey_indicator_requested_changes_discard_' . $indicator_id, self::WAIT_FOR_SECONDS);
        });
        $indicator_data['state'] = $indicator_data['previous_state'];
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
    }

    public function clickSaveRequestChanges(Browser $browser, $indicator_id, $indicator_data): void
    {
        $this->author = $this->user;
        $indicator_data['state'] = self::REQUEST_CHANGES;
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $browser->within('@survey_indicator_state_' . $indicator_id, function ($browser) use ($indicator_id) {
            $browser->on(new Button('@survey_indicator_request_changes_save_' . $indicator_id, 'Save'))
                    ->clickButton()
                    ->waitUntilMissing('@survey_indicator_request_changes_save_' . $indicator_id, self::WAIT_FOR_SECONDS);
        });
        $indicator_data['state'] = self::REQUESTED_CHANGES;
        $indicator_data['discard'] = $indicator_data['edit'] = 'visible';
        $this->assertIndicatorState($browser, $indicator_id, $indicator_data);
        $this->assertIndicatorRequestChangesAlert($browser);

        if (isset($indicator_data['history_changes']))
        {
            $this->clickSurveyRequestedChangesHistory($browser, $indicator_id);
            $this->assertSurveyRequestedChangesHistory($browser, $indicator_id, $indicator_data['history_changes']);
        }
    }

    public function clickAndAssertSurveyNavigation(Browser $browser, $indicator_id, $indicator_title, $indicator_inputs): void
    {
        $this->clickSurveyNavigation($browser);
        $this->assertSurveyNavigationStatus($browser, $indicator_id, $indicator_title, $indicator_inputs);
        $this->clickSurveyNavigation($browser);
    }

    public function clickPrevious(Browser $browser, $indicator_id): void
    {
        $browser->on(new Button('@survey_indicator_previous_' . $indicator_id, 'Previous'))
                ->scrollAndClickButton('bottom');
    }

    public function clickSave(Browser $browser, $indicator_id): void
    {
        $browser->on(new Button('@survey_indicator_save_' . $indicator_id, 'Save'))
                ->scrollAndClickButton('bottom')
                ->waitUntilDisabled('@survey_indicator_save_' . $indicator_id, self::WAIT_FOR_SECONDS);
        $browser->assertVisible('@survey_indicator_last_saved_' . $indicator_id);
    }

    public function clickNext(Browser $browser, $indicator_id): void
    {
        $browser->on(new Button('@survey_indicator_next_' . $indicator_id, 'Next'))
                ->scrollAndClickButton('bottom');
    }

    public function clickSaveUnsavedChanges(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_UNSAVED_CHANGES_MODAL, function ($modal) {
            $modal->on(new Button(self::SURVEY_UNSAVED_CHANGES_MODAL_SAVE))
                  ->clickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitUntilMissing(self::SURVEY_UNSAVED_CHANGES_MODAL, self::WAIT_FOR_SECONDS);
    }

    public function clickDiscardUnsavedChanges(Browser $browser): void
    {
        $browser->whenAvailable(self::SURVEY_UNSAVED_CHANGES_MODAL, function ($modal) {
            $modal->on(new Button(self::SURVEY_UNSAVED_CHANGES_MODAL_DISCARD))
                  ->clickButton();
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitUntilMissing(self::SURVEY_UNSAVED_CHANGES_MODAL, self::WAIT_FOR_SECONDS);
    }

    public function clickReviewAndSubmit(Browser $browser): void
    {
        $browser->within('.wizard-fieldset.show', function ($browser) {
            $browser->on(new Button(self::SURVEY_REVIEW_AND_SUBMIT, 'Review & Submit'))
                    ->scrollAndClickButton('bottom');
        });
    }

    public function clickReviewAndSubmitGoTo(Browser $browser, $section, $goTo): void
    {
        $browser->whenAvailable(self::SURVEY_REVIEW_AND_SUBMIT_MODAL, function ($modal) use ($section, $goTo) {
            $modal->within($section, function ($modal) use ($goTo) {
                $modal->on(new Button($goTo))
                      ->scrollAndClickButton();
            });
        });
        $browser->on(new Loader(self::WAIT_FOR_SECONDS))
                ->waitUntilMissing(self::SURVEY_REVIEW_AND_SUBMIT_MODAL, self::WAIT_FOR_SECONDS);
    }

    public function clickReviewAndSubmitButton(Browser $browser, $button): void
    {
        $browser->whenAvailable(self::SURVEY_REVIEW_AND_SUBMIT_MODAL, function ($modal) use ($button) {
            $modal->on(new Button($button))
                  ->clickButton();
        });
        if (!preg_match('/close/', $button)) {
            $browser->on(new Loader(30));
        }
        $browser->waitUntilMissing(self::SURVEY_REVIEW_AND_SUBMIT_MODAL, self::WAIT_FOR_SECONDS);
    }

    public function clickBackToPage(Browser $browser, $text): void
    {
        $browser->on(new Button('@back_to_page', $text))
                ->scrollAndClickButton('bottom');
    }

    public function clickBreadcrumb(Browser $browser, $breadcrumb, $text): void
    {
        $browser->on(new Button($breadcrumb, $text))
                ->scrollAndClickButton('top');
    }
}
