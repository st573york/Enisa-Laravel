import './wizard';

window.isDirty = false;
window.go_to_data = {
  'save': null,
  'discard': null
};

let loadedInputs = '';
let scroll_to_error = false;
let questionnaire_answers_copy = {};
let indicator_answers_copy = {};
let indicators_list_unique = [];
let assigned_indicators_unique = [];
let incomplete_indicators_unique = [];
let incomplete_indicators_assigned_unique = [];
let indicator_questions_not_answered_unique = [];
let unsubmitted_indicators_unique = [];
let requested_approval_indicators_unique = [];
let current_state = null;
let requested_changes_indicator_submitted_content = '';

$(document).ready(function () {  
  findAssignedIndicators();
  findIncompleteIndicators();
  findQuestionsAnswered();
  if (is_primary_poc)
  {
    findUnsubmittedIndicators();
    findRequestedApprovalIndicators();
  }
  updateStepStatus();

  if (!view_all) 
  {
    $('.wizard-fieldset.assigned').each(function (i, el) {      
      let state = $(el).attr('data-state');
            
      if ($.inArray(parseInt(state), [4, 7, 8]) == -1)
      {
        $(el).find('.form-indicators .input-choice input:checked').each(function (i, e) {
          toggleAnswers(e);
          toggleReference(e);

          let choice = $(e).val();          
          if (choice != 3)
          {
            let master = $(e).closest('.input-choice').siblings('.actual-answers').find('.form-input.master');
            if (master.length) {
              toggleOptions(master);
            }
          }
        });        
      }
    });

    if (requested_indicator)
    {
      let indicator_id = $('.wizard-fieldset[data-id="' + requested_indicator + '"]').prop('id');
      if (indicator_id) 
      { 
        jumpToPage(indicator_id.replace('page-', ''));
        findCurrentStep();  
      }
    }

    resetDirty();

    if (requested_action == 'load_answers')
    {
      isDirty = true;

      $('.wizard-fieldset.show').find('.step-save').prop('disabled', !isDirty);
    }

    if (requested_action == 'review') {
      showReviewModal();
    }
  }
  
  if (requested_action == 'save')
  {
    let elem = $('.wizard-fieldset.show').find('.step-save');

    $(window).scrollTop(elem.offset().top);
  }
  else if ($.inArray(requested_action, ['requested_changes', 'discard_requested_changes', 'approve', 'final_approve', 'unapprove']) == -1) {
    scrollToWizard();
  }

  $('.loader').fadeOut();  
});

$(window).on('beforeunload', function() {
  if (isDirty &&
      !go_to_data['save'] &&
      !go_to_data['discard']) 
  {  
    return 'This page has unsaved changes that will be lost. Save or discard the answers before leaving this page.';
  }
});

$(document).on('hide.bs.modal', '#dirtyModal', function() {  
  resetGoToData();
});

$(document).on('shown.bs.collapse', '.accordion-collapse', function() {  
  if (scroll_to_error) {
    scrollToError();
  }
});

$(document).ajaxStop(function () {
  findIncompleteIndicators();
  findQuestionsAnswered();
  if (is_primary_poc)
  {
    findUnsubmittedIndicators();
    findRequestedApprovalIndicators();
  }
  updateStepStatus();
});

$(document).on('click', 'a:not([href^="#"])', function(e) {   
  if (isDirty) 
  {
    go_to_data['save'] = go_to_data['discard'] = {
      'url': this.href
    };
    
    e.preventDefault();
    
    dirtyModal.show();
  }
});

$(document).on('input', '.wizard-fieldset.show', function() {  
  isDirty = (getSerializedInputs() !== loadedInputs && action != 'preview') ? true : false;

  $(this).find('.step-save').prop('disabled', !isDirty);
});

$(document).on('click', '.btn-approve', function () {
  approveIndicator();
});

$(document).on('click', '.btn-request-changes', function () {
  if (!isIndicatorAssigneeActive()) {
    return;
  }

  updateIndicatorStateSection('5', true, true);
});

$(document).on('click', '.btn-cancel-request-changes', function () {
  updateIndicatorStateSection(current_state, true, false);
});

$(document).on('click', '.btn-send-request-changes', function () {
  requestChangesIndicator();
});

$(document).on('click', '.btn-discard-requested-changes', function () {
  discardRequestedChangesIndicator();
});

$(document).on('click', '.btn-edit-requested-changes', function () {
  if (!isIndicatorAssigneeActive()) {
    return;
  }

  updateIndicatorStateSection('5', true, true);
});

$(document).on('click', '.btn-unapprove', function () {
  unapproveIndicator();
});

$(document).on('click', '#step-save-goto', function () {
  saveQuestionnaire('save');
});

$(document).on('click', '#step-discard-goto', function () {
  $('.loader').fadeIn();
  
  if (requested_action == 'load_answers') {
    loadResetQuestionnaireIndicatorData('reset');
  }
  else {
    goTo(go_to_data['discard']);
  }
});

$(document).on('click', '#questionnaire-review', function () {
  if (isDirty) 
  {
    go_to_data['save'] = go_to_data['discard'] = {
      'url': '/questionnaire/view',
      'requested_indicator': $('.wizard-fieldset.show').attr('data-id'),
      'requested_action': 'review'
    };
      
    dirtyModal.show();

    return;
  }

  showReviewModal();
});

$(document).on('click', '#questionnaire-submit', function () {
  saveQuestionnaire('submit');
});

$(document).on('click', '.step-save', function () {
  saveQuestionnaire('save');
});

$(document).on('click', '.validate', function () {
  findIncompleteIndicators();
  if (is_primary_poc)
  {
    findUnsubmittedIndicators();
    findRequestedApprovalIndicators();
  }
  updateStepStatus();
});

$(document).on('click', '.step-choice:not(.disabled), .go-to-page', function () {
  let page = $(this).attr('id').replace('step-page-', '').replace('to-page-', '');
  let indicator = $(this).attr('data-id');
  let indicator_assigned = ($.inArray(parseInt(indicator), assigned_indicators_unique) != -1) ? true : false;

  if (isDirty) 
  {
    go_to_data['save'] = go_to_data['discard'] = {
      'url': '/questionnaire/view',
      'requested_indicator': indicator
    };
      
    dirtyModal.show();

    return;
  }

  if($(this).hasClass('step-choice') || 
     ($(this).hasClass('go-to-page') && !indicator_assigned))
  {
    clearPageErrors();
    clearInputErrors();
  }
  
  jumpToPage(page);
  findCurrentStep();

  resetDirty();
  
  if ($(this).hasClass('go-to-page'))
  {
    pageModal.hide();

    if (indicator_assigned)
    {
      validateQuestionnaireIndicator();

      return;
    }
  }
  
  scrollToWizard();  
});

$(document).on('click', '.step-change', function () {  
  if (isDirty) {   
    return;
  }
    
  findCurrentStep();

  resetDirty();

  scrollToWizard();
});

$(document).on('input', '.input-choice input, .actual-answers input', function () {
  $(this).closest('.form-indicators').siblings('.form-indicator-question-answer').addClass('d-none');
  $(this).closest('.form-indicators').siblings('.form-indicator-question-load-reset').remove();
});

$(document).on('input', '.form-references textarea', function () {
  $(this).closest('.form-references').siblings('.form-indicator-question-answer').addClass('d-none');
  $(this).closest('.form-references').siblings('.form-indicator-question-load-reset').remove();
});

$(document).on('input', '.form-comments textarea', function () {
  $(this).closest('.form-comments').find('.form-indicator-question-load-reset').remove();
});

$(document).on('input', '.form-rating input', function () {
  $(this).closest('.form-rating').siblings('.form-indicator-question-load-reset').remove();
});

$(document).on('click', '.clear-answers', function (e) {
  e.preventDefault();

  let default_choice = $(this).parent().siblings('.form-check').find('input[type="radio"][value="1"], input[type="radio"][value="2"]');

  default_choice.prop('checked', true);
  $(this).closest('.input-choice').siblings('.actual-answers').find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
  $(this).closest('.input-choice').siblings('.actual-answers').find('input[type="text"]').val('');
  $(this).closest('.form-indicators').siblings('.form-indicator-question-answer').addClass('d-none');
  $(this).closest('.form-indicators').siblings('.form-indicator-question-load-reset').remove();

  toggleAnswers(default_choice);
  toggleReference(default_choice);
  
  // Update isDirty
  $('.wizard-fieldset.show').trigger('input');
});

function goTo(go_to)
{
  if (go_to.url.indexOf('/') === -1)
  {    
    jumpToPage(go_to);
    findCurrentStep();
    
    scrollToWizard();
  }
  else if (go_to.url.indexOf('/questionnaire/view') >= 0) {
    viewSurvey({
      'questionnaire_country_id': questionnaire_country_id,
      'requested_indicator': go_to.requested_indicator,
      'requested_action': go_to.requested_action
    });
  }
  else {
    window.location.href = go_to.url;
  }
}

function getSerializedInputs()
{
	return $('.wizard-fieldset.show').find('.form-indicators input, select, textarea, .rating input').serialize();
}

function getModalErrorData(id)
{
  return {
    'indicatorPage': $('.step-choice[data-id="' + id + '"]').attr('id').replace('step-page-', ''),
    'indicatorId': $('.step-choice[data-id="' + id + '"]').attr('data-id'),
    'indicatorNumber': $('.step-choice[data-id="' + id + '"]').attr('dusk').replace('survey_step_choice_indicator_', ''),
    'indicatorText': $('.step-choice[data-id="' + id + '"]').text()
  };
}

function resetDirty()
{
  loadedInputs = getSerializedInputs(); 

  $('.wizard-fieldset.show').trigger('input');
}

function resetGoToData()
{
  go_to_data = {
    'save': null,
    'discard': null
  };
}

function scrollToWizard() 
{
  $('html, body').animate({
    scrollTop: $('.questionnaire-title').offset().top - 10
  }, 0);
}

function isAccordionOpen() 
{
  if (!$('.wizard-fieldset.show .is-invalid:first').closest('.accordion-collapse').hasClass('show') &&
      !$('.wizard-fieldset.show .is-invalid:first').closest('.rating').length)
  {
    scroll_to_error = true;
    
    $('.wizard-fieldset.show .is-invalid:first').closest('.accordion-collapse').prev('.accordion-item').find('.accordion-button').trigger('click');

    return false;
  }

  return true;
}

function scrollToError() 
{
  let elem = null;
  if ($('.wizard-fieldset.show .is-invalid:first').closest('.form-indicators, .form-references').length) {
    elem = $('.wizard-fieldset.show .is-invalid:first').closest('.form-indicators, .form-references').siblings('.form-question-text');
  }
  else if ($('.wizard-fieldset.show .is-invalid:first').closest('.rating').length) {
    elem = $('.wizard-fieldset.show .is-invalid:first').closest('.rating').siblings('label');
  }

  $('html, body').animate({
    scrollTop: elem.offset().top - 10
  }, 0);

  scroll_to_error = false;
}

function isIndicatorAssigneeActive()
{
  let warning = '';

  if (is_admin)
  {
    if (!is_primary_poc_active) {
      warning = 'Request changes are not allowed as Primary PoC is inactive.';
    }
  }
  else if (is_primary_poc)
  {
    if ($('.wizard-fieldset.show .form-indicator-assignee').text().indexOf('inactive') >= 0) {
      warning = 'Request changes are not allowed as indicator assignee is inactive. Please re-assign indicator to an active user.';
    }
  }

  if (warning) 
  {
    setAlert({
      'status': 'warning',
      'msg': warning,
    });

    return false;
  }

  return true;
}

function findActiveIndicator()
{
  return ($('.wizard-fieldset.show').attr('data-type').includes('form-indicator')) ? $('.wizard-fieldset.show').attr('data-id') : null;
}

function findAssignedIndicators()
{  
  let assigned_indicators = [];
  
  $('.wizard-fieldset.assigned').each(function (i, el) {
    assigned_indicators.push(parseInt($(el).attr('data-id')));    
  });
  
  assigned_indicators_unique = [...new Set(assigned_indicators)];
}

function findAnswers(el)
{
  if ($(el).hasClass('multiple-choice')) 
  {
    let values = [];    

    $(el)
      .find('.form-input:checked')
      .each(function (i, e) {
        values.push($(e).val());
      });  
      
    if (values.length) {
      return values;
    }
  }
  else if ($(el).hasClass('single-choice')) 
  {          
    let value = $(el).find('.form-input:checked').val();

    if (value) {
      return value;
    }
  } 
  else if ($(el).hasClass('free-text')) 
  {          
    let value = $(el).find('.form-input').val();

    if (value) {
      return $.trim(value);
    }
  }

  return undefined;
}

function findIncompleteIndicators() 
{
  let incomplete_indicators = [];
  let incomplete_indicators_assigned = [];
  let indicator_questions_not_answered = [];
  
  $('.form-indicators').each(function (i, e) {    
    let indicator_id = $(e).attr('id').replace('form-indicator-', '');
    let question_id = $(e).closest('.question-body').attr('id').split('survey_indicator_question_')[1];
    let answer_choice = $(e).find('.input-choice').find('.form-input:checked').val();
    
    if ($(e).find('.input-choice').hasClass('required') &&
        answer_choice === undefined)
    {
      incomplete_indicators.push(parseInt(indicator_id));

      return;
    }

    if ($(e).find('.actual-answers').hasClass('required') &&
        $(e).siblings('.form-references').hasClass('required') &&
        answer_choice != 3)
    {
      let answers_provided = true;
      let reference_year_provided = true;
      let reference_source_provided = true;

      $(e)
        .children('.actual-answers')
        .each(function (idx, el) {      
          let answers = findAnswers(el);
          if (answers === undefined)
          {
            incomplete_indicators.push(parseInt(indicator_id));

            answers_provided = false;

            return;
          }
        });

      let reference_year = $(e).siblings('.form-references').find('select').val();
      if (!reference_year)
      {
        incomplete_indicators.push(parseInt(indicator_id));
  
        reference_year_provided = false;
      }

      let reference_source = $(e).siblings('.form-references').find('textarea').val();
      if (!$.trim(reference_source).length)
      {
        incomplete_indicators.push(parseInt(indicator_id));

        reference_source_provided = false;
      }

      if (!answers_provided &&
          !reference_year_provided &&
          !reference_source_provided)
      {
        indicator_questions_not_answered.push(parseInt(question_id));
      }
    }

    if (!$('.form-indicator-' + indicator_id + '-rating:checked').length) {
      incomplete_indicators.push(parseInt(indicator_id));
    }

    if ($.inArray(parseInt(indicator_id), assigned_indicators_unique) != -1 &&
        $.inArray(parseInt(indicator_id), incomplete_indicators) != -1)
    {
      incomplete_indicators_assigned.push(parseInt(indicator_id));
    }
  });
  
  incomplete_indicators_unique = [...new Set(incomplete_indicators)];
  incomplete_indicators_assigned_unique = [...new Set(incomplete_indicators_assigned)];
  indicator_questions_not_answered_unique = [...new Set(indicator_questions_not_answered)];
}

function findUnsubmittedIndicators()
{
  let unsubmitted_indicators = [];
  
  $('.form-indicators').each(function (i, e) {    
    let id = $(e).attr('id').replace('form-indicator-', '');

    if ($.inArray(parseInt(id), assigned_indicators_unique) == -1 &&
        $.inArray(parseInt(id), incomplete_indicators_unique) == -1)
    {
      let state = $('.wizard-fieldset[data-id="' + id + '"]').attr('data-state');

      if (state == '3') {
        unsubmitted_indicators.push(parseInt(id));
      }
    }
  });
  
  unsubmitted_indicators_unique = [...new Set(unsubmitted_indicators)];
}

function findRequestedApprovalIndicators()
{
  let requested_approval_indicators = [];
  
  $('.form-indicators').each(function (i, e) {    
    let id = $(e).attr('id').replace('form-indicator-', '');

    if ($.inArray(parseInt(id), assigned_indicators_unique) == -1 &&
        $.inArray(parseInt(id), incomplete_indicators_unique) == -1)
    {
      let state = $('.wizard-fieldset[data-id="' + id + '"]').attr('data-state');

      if ($.inArray(state, ['4', '5', '6']) != -1) {
        requested_approval_indicators.push(parseInt(id));
      }
    }
  });
  
  requested_approval_indicators_unique = [...new Set(requested_approval_indicators)];
}

function findCurrentStep() 
{
  let page_id = $('.wizard-fieldset.show').attr('id').replace('page-', '');

  $('.step-choice').each(function (idx, el) {
    let step_choice_id = $(el).attr('id').replace('step-page-', '');
    
    $(el).toggleClass('current', (step_choice_id == page_id));    
  });
}

function findQuestionsAnswered()
{
  $('.wizard-fieldset').find('.question-body').each(function (i, e) {
    let question_id = $(e).attr('id').split('survey_indicator_question_')[1];
    let question = $(`#survey_indicator_question_${question_id}`);
    let answers_loaded = question.find('.answers-loaded');
    
    if ($.inArray(parseInt(question_id), indicator_questions_not_answered_unique) == -1 &&
        !answers_loaded.length)
    {
      question.find('.form-indicator-question-answer').removeClass('d-none');
    }
  });
}

function findData()
{
  let indicators_list = [];
  let questionnaire_answers = [];
  let indicator_answers = [];

  $('.form-indicators').each(function (i, e) {
    let indicator = {
      id: null,
      type: null,
      inputs: {
        'choice': null,
        'answers': null,
        'reference_year': null,
        'reference_source': null,
        'rating': null
      },
      accordion: null,
      order: null,
      choice: null,
      answers: [],
      reference_year: null,
      reference_source: null,
      comments: null,
      rating: null
    };
    
    indicator.id = $(e).attr('id').replace('form-indicator-', '');
    indicator.number = $(e).attr('data-number').replace('form-indicator-', '');
    indicator.type = $(e).attr('data-type').replace('form-indicator-', '');
    indicator.accordion = $(e).closest('.accordion-collapse').attr('data-order');
    indicator.order = $(e).find('.input-choice').attr('data-order');
    indicator.choice = $(e).find('.input-choice').find('.form-input:checked').val();
    indicator.inputs.choice = $(e).find('.input-choice').find('.form-input:first').attr('name');
    indicator.reference_year = $(e).siblings('.form-references').find('select').val();
    indicator.inputs.reference_year = $(e).siblings('.form-references').find('select').attr('name');
    indicator.reference_source = $(e).siblings('.form-references').find('textarea').val();
    indicator.inputs.reference_source = $(e).siblings('.form-references').find('textarea').attr('name');
    indicator.comments = $('#form-indicator-' + indicator.id + '-comments').val();
    indicator.rating = $('.form-indicator-' + indicator.id + '-rating:checked').length > 0 ? $('.form-indicator-' + indicator.id + '-rating:checked').val() : 0;
    indicator.inputs.rating = $('.form-indicator-' + indicator.id + '-rating').attr('name');
    if ($(e).siblings('.form-indicator-question-load-reset').length > 0) {
      indicator.answers_loaded = $(e).siblings('.form-indicator-question-load-reset').find('.answers-loaded').length > 0 ? true : false;
    }
    
    $(e)
      .children('.actual-answers')
      .each(function (idx, el) {
        indicator.inputs.answers = $(el).find('.form-input:first').attr('name');
        
        let answers = findAnswers(el);
        if (answers !== undefined) 
        {
          if ($.isArray(answers)) {
            indicator.answers = answers;
          }
          else {
            indicator.answers.push(answers);
          }
        }
      });
      
      indicators_list.push(indicator.id);
      questionnaire_answers.push(indicator);
      if ($(e).closest('fieldset').hasClass('show')) {
        indicator_answers.push(indicator);
      }
  });

  indicators_list_unique = [...new Set(indicators_list)];
  questionnaire_answers_copy = $.extend(true, {}, questionnaire_answers);
  indicator_answers_copy = $.extend(true, {}, indicator_answers);
}

window.updateIndicatorStateSection = function(state, reset, update) {
  clearPageErrors();
  clearInputErrors();
  
  if (reset) 
  {
    $('.indicator-state').removeClass('show');
    $('.indicator-state').children().children().removeClass('show');
  }

  let active_indicator = findActiveIndicator();
  if (active_indicator)
  {
    $('.requested-changes-history').removeClass('show');
    let history_elem = $('.requested-changes-history[data-id="' + active_indicator + '"]');
    if (history_elem.hasClass('history')) {
      history_elem.addClass('show');
    }

    let state_elem = $('.indicator-state[data-id="' + active_indicator + '"]');
    let wizard_elem = $('.wizard-fieldset[data-id="' + active_indicator + '"]');

    let is_assigned = wizard_elem.hasClass('assigned');
    let requested_changes_indicator_state = state_elem.attr('data-requested-changes-state');
    let editor = tinymce.get('request-requested-changes-' + active_indicator);

    let state_class = '';
    
    current_state = wizard_elem.attr('data-state');
    state = (state) ? state : current_state;

    if ((state == '7' && !is_questionnaire_submitted) || // Approved
        state == '8')                                    // Final approved
    {
      state_elem.find('.approved-title-author').text(
        ($('#approved-title-author-' + active_indicator).text() ? $('#approved-title-author-' + active_indicator).text() : user_logged_in.name + ' (you)')
      );

      if (update) {
        wizard_elem.attr('data-state', state);
      }

      state_class = '.approved-wrap';

      if (state == '8' && is_questionnaire_submitted) {
        state_elem.find(state_class).find('.unapprove').removeClass('d-none');
      }
    }
    else if (state == '4' || // Submitted
             state == '7')   // Approved
    {
      if (((is_poc && !is_primary_poc) ||
           (is_primary_poc && is_questionnaire_completed) ||
           (is_admin && is_questionnaire_submitted)) &&
          !is_assigned)
      {
        state_class = '.request-approval-wrap';
      }
      
      if (update) {
        wizard_elem.attr('data-state', state);
      }
      
      if (requested_changes_indicator_state == 2) {
        updateIndicatorStateSection('6', false, false);
      }
    }
    else if (state == '5') // Request changes
    {
      state_elem.find('.requested-changes-title, .requested-changes-discard-edit').addClass('d-none');
      state_elem.find('.request-changes-title, .request-changes-deadline, .request-changes-actions').removeClass('d-none');

      if (requested_changes_indicator_state == 2)
      {
        requested_changes_indicator_submitted_content = $('#request-requested-changes-' + active_indicator).text();
        editor.setContent('');
      }

      editor.mode.set('design');

      state_class = '.request-requested-changes-wrap';
    }
    else if (state == '6') // Requested changes
    {
      let requested_changes_indicator_author_name = $('#requested-changes-title-author-' + active_indicator).text();
      let requested_changes_indicator_author_role = state_elem.find('.requested-changes-discard-edit').attr('data-role');
      
      state_elem.find('.requested-changes-title').removeClass('d-none');
      state_elem.find('.requested-changes-title-author').text(
        (requested_changes_indicator_author_name.length ? requested_changes_indicator_author_name : user_logged_in.name + ' (you)')
      );
      state_elem.find('.requested-changes-title-deadline').text($('#request-requested-changes-deadline-' + active_indicator).val());
      if (((is_poc && requested_changes_indicator_author_role == 2) ||
           (is_primary_poc && requested_changes_indicator_author_role == 5) ||
           (is_admin && requested_changes_indicator_author_role == 1)) &&
          pending_requested_changes.length &&
          requested_changes_indicator_state == 1)
      {
        if (!is_assigned) {
          state_elem.find('.requested-changes-discard-edit').removeClass('d-none');
        }
        
        setAlert({
          'status': 'warning',
          'msg': 'Requested changes have NOT been sent to the assignee yet.\
            Browse to <a href="/questionnaire/dashboard/management/' + questionnaire_country_id + '">Survey Dashboard</a> to submit the requested changes for ALL indicators.'
        });
      }
      state_elem.find('.request-changes-title, .request-changes-deadline, .request-changes-actions').addClass('d-none');

      if (requested_changes_indicator_submitted_content.length) {
        editor.setContent(requested_changes_indicator_submitted_content);
      }
      editor.mode.set('readonly');

      if (update) {
        wizard_elem.attr('data-state', state);
      }

      state_class = '.request-requested-changes-wrap';
    }

    state_elem.find(state_class).addClass('show')
      .closest('.indicator-state').addClass('show');
  }
}

function updateStepStatus() 
{
  $('.step-choice.indicators').each(function (i, element) {
    let id = $(element).attr('data-id');

    $('.step-choice[data-id="' + id + '"]').parent()
      .toggleClass('incomplete', ($.inArray(parseInt(id), incomplete_indicators_unique) != -1))
      .toggleClass('complete', ($.inArray(parseInt(id), incomplete_indicators_unique) == -1));
  });
}

function approveIndicator()
{
  $('.loader').fadeIn();

  skipFadeOut = true;

  let active_indicator = findActiveIndicator();
  let requested_indicator = parseInt(active_indicator) + (($('#questionnaire-review').is(':visible') || !$('.form-wizard-next-btn').is(':visible')) ? 0 : 1);
  let action = (is_admin) ? 'final_approve' : 'approve';
  
  $.ajax({
    'url': '/questionnaire/indicator/single/update/' + active_indicator,
    'type': 'post',
    'data': {
      'action': action,
      'questionnaire_country_id': questionnaire_country_id
    },
    success: function () {
      viewSurvey({
        'questionnaire_country_id': questionnaire_country_id,
        'requested_indicator': requested_indicator,
        'requested_action': action
      });
    },
    error: function (req) {    
      $('.loader').fadeOut();

      setAlert({
        'status': 'error',
        'msg': req.responseJSON.error
      });              
    } 
  });
}

function requestChangesIndicator()
{
  $('.loader').fadeIn();

  skipFadeOut = true;

  let active_indicator = findActiveIndicator();
  let deadline = $('#request-requested-changes-deadline-' + active_indicator).val();
  let editor = tinymce.get('request-requested-changes-' + active_indicator);
  let text_content = $.trim(editor.getContent({format: 'text'}));
  let raw_content = editor.getContent({format: 'raw'});

  $.ajax({
    'url': '/questionnaire/indicator/request_changes/' + active_indicator,
    'type': 'post',
    'data': {
      'questionnaire_country_id': questionnaire_country_id,
      'deadline': deadline,
      'changes': (text_content) ? encodeURIComponent(raw_content) : text_content
    },    
    success: function () {
      viewSurvey({
        'questionnaire_country_id': questionnaire_country_id,
        'requested_indicator': active_indicator,
        'requested_action': 'requested_changes'
      });
    },
    error: function (req) {
      $('.loader').fadeOut();

      let type = 'error';
      let message = '';

      if (req.status == 400) 
      {
        let messages = [];
        $.each(req.responseJSON, function (key, val) { 
          messages.push(val);
        });
        message = messages.join(' ');         
      }
      else if (req.status == 403 || 
               req.status == 405) 
      {                            
        type = Object.keys(req.responseJSON)[0];
        message = req.responseJSON[ type ];                          
      }             
        
      setAlert({
        'status': type,
        'msg': message
      });
    } 
  });
}

function discardRequestedChangesIndicator()
{
  $('.loader').fadeIn();

  skipFadeOut = true;

  let active_indicator = findActiveIndicator();

  $.ajax({
    'url': '/questionnaire/indicator/discard_requested_changes/' + active_indicator,
    'type': 'post',
    'data': {
      'questionnaire_country_id': questionnaire_country_id
    },    
    success: function () {
      viewSurvey({
        'questionnaire_country_id': questionnaire_country_id,
        'requested_indicator': active_indicator,
        'requested_action': 'discard_requested_changes'
      });
    },
    error: function (req) { 
      $('.loader').fadeOut();

      setAlert({
        'status': 'error',
        'msg': req.responseJSON.error
      });              
    } 
  });
}

function unapproveIndicator()
{
  $('.loader').fadeIn();

  skipFadeOut = true;

  let active_indicator = findActiveIndicator();
  let action = 'unapprove';

  $.ajax({
    'url': '/questionnaire/indicator/single/update/' + active_indicator,
    'type': 'post',
    'data': {
      'action': action,
      'questionnaire_country_id': questionnaire_country_id
    },    
    success: function () {
      viewSurvey({
        'questionnaire_country_id': questionnaire_country_id,
        'requested_indicator': active_indicator,
        'requested_action': action
      });
    },
    error: function (req) {    
      $('.loader').fadeOut();

      setAlert({
        'status': 'error',
        'msg': req.responseJSON.error
      });              
    } 
  });
}

function validateQuestionnaireIndicator()
{
  $('.loader').fadeIn();

  skipFadeOut = false;

  findData();

  $.ajax({
    'url': '/questionnaire/indicator/validate/' + questionnaire_country_id,
    'type': 'post',
    'data': {
      'indicator_answers': JSON.stringify(indicator_answers_copy)
    },    
    success: function () {
      scrollToWizard();
    },
    error: function (req) {        
      showInputErrors(req.responseJSON.errors);

      if (isAccordionOpen()) {
        scrollToError();
      }                                             
    } 
  });
}

window.loadResetQuestionnaireIndicatorData = function(action) {
  $('.loader').fadeIn();

  skipFadeOut = true;
  isDirty = false;

  let active_indicator = findActiveIndicator();

  $.ajax({
    'url': '/questionnaire/data/' + action + '/' + questionnaire_country_id,
    'type': 'post',
    'data': {
      'active_indicator': active_indicator
    },    
    success: function () {
      if (go_to_data['discard']) {
        goTo(go_to_data['discard']);
      }
      else {
        viewSurvey({
          'questionnaire_country_id': questionnaire_country_id,
          'requested_indicator': active_indicator,
          'requested_action': `${action}_answers`
        });
      }
    },
    error: function (req) {     
      $('.loader').fadeOut();

      setAlert({
        'status': 'error',
        'msg': req.responseJSON.error
      });
    } 
  });
}

function saveQuestionnaire(action)
{    
  $('.loader').fadeIn();

  skipFadeOut = true;

  let active_indicator = findActiveIndicator();
  
  findData();
  
  $.ajax({
    'url': '/questionnaire/save/' + questionnaire_country_id,
    'type': 'post',
    'data': { 
      'action': action,
      'indicators_list': indicators_list_unique,
      'active_indicator': active_indicator,
      'questionnaire_answers': JSON.stringify(questionnaire_answers_copy),
      'indicator_answers': JSON.stringify(indicator_answers_copy)
    },
    success: function () {
      resetDirty();

      if (action == 'save')
      {
        if (go_to_data['save'])
        {
          goTo(go_to_data['save']);
    
          dirtyModal.hide();
        }
        else {
          viewSurvey({
            'questionnaire_country_id': questionnaire_country_id,
            'requested_indicator': active_indicator,
            'requested_action': action
          });
        }
      }
      else if (action == 'submit')
      {
        pageModal.hide();

        if (is_primary_poc) {
          viewSurvey({
            'questionnaire_country_id': questionnaire_country_id
          });
        }
        else {
          window.location.href = '/questionnaire/management';
        }
      }
    },
    error: function (req) {
      $('.loader').fadeOut();
      
      dirtyModal.hide();  
      pageModal.hide();

      let type = Object.keys(req.responseJSON)[0];
      
      if (req.status == 400) 
      {
        if (type == 'errors') 
        {          
          showInputErrors(req.responseJSON.errors);

          if (isAccordionOpen()) {
            scrollToError();
          }

          return;
        }
      }      
      else if (req.status == 403 || 
               req.status == 405) 
      {        
        if (req.responseJSON.indicators_assigned) {
          $('.alert-section').removeClass('d-none');
        }
        $('.wizard-fieldset :input').prop('disabled', true);
        $('.step-choice').addClass('disabled');
      }
      
      setAlert({
        'status': type,
        'msg': req.responseJSON[ type ]
      });    
      
      resetDirty();
    }
  });
}

function jumpToPage(page) 
{
  $('.wizard-fieldset').removeClass('show', '400');

  if (page == 0 || 
      page == 1) 
  {
    $('.wizard-fieldset[data-type="info"]').addClass('show', '400');
  }
  else 
  {
    let el = $('.wizard-fieldset#page-' + page);
    el.addClass('show', '400');
  }

  updateIndicatorStateSection(null, true, true);
}

function showReviewModal() 
{  
  let final_submit = ((!incomplete_indicators_assigned_unique.length && !is_primary_poc) ||
                      (!incomplete_indicators_unique.length &&
                       !unsubmitted_indicators_unique.length && 
                       !requested_approval_indicators_unique.length && 
                       is_primary_poc)) ? true : false;
  let data = '';
  
  if (final_submit)
  {
    data += '<div dusk="survey_submit_complete_section">';
    data += "<h6 id='status-complete-message' class='success'>You can now submit the survey to " + (is_primary_poc ? user_group : 'the PoC') + " for review!</h6>";
    data += '</div>';
  }
  else 
  {
    $('.modal-body').addClass('indicators');

    if (!is_primary_poc) {
      incomplete_indicators_unique = incomplete_indicators_assigned_unique;
    }

    if (incomplete_indicators_unique.length)
    {
      data += '<div dusk="survey_submit_incomplete_section">';
      data += "<h6 id='status-incomplete-message' class='ms-span error' style='color: var(--state-error);'>Incomplete sections</h6>";
      data += '<ul>';
      $.each(incomplete_indicators_unique, function (idx, id) { 
        data += '<li class="incomplete">';   
        let indicatorClass = ($.inArray(parseInt(id), assigned_indicators_unique) != -1) ? 'assigned' : 'not_assigned';
        let obj = getModalErrorData(id);
        data += "<div dusk=survey_submit_incomplete_indicator_" + obj.indicatorNumber + " class='go-to-page " + indicatorClass + "' id='to-page-" + obj.indicatorPage + "' data-id=" + obj.indicatorId + ">" + obj.indicatorText + "</div>";
        data += '</li>';        
      });
      data += '</ul>';
      data += '</div>';
    }
    
    if (is_primary_poc)
    {
      if (unsubmitted_indicators_unique.length) 
      {
        data += '<div dusk="survey_submit_unsubmitted_section">';
        data += "<h6 id='status-unsubmitted-message' class='ms-span error'>Unsubmitted sections</h6>";
        data += '<ul>';
        $.each(unsubmitted_indicators_unique, function (idx, id) {             
          data += '<li class="complete">';
          let obj = getModalErrorData(id);
          data += "<div dusk=survey_submit_unsubmitted_indicator_" + obj.indicatorNumber + " class='go-to-page not_assigned' id='to-page-" + obj.indicatorPage + "' data-id=" + obj.indicatorId + ">" + obj.indicatorText + "</div>";
          data += '</li>';          
        });
        data += '</ul>';
        data += '</div>';
      }

      if (requested_approval_indicators_unique.length) 
      { 
        data += '<div dusk="survey_submit_request_acceptance_section">';
        data += "<h6 id='status-request-acceptance-message' class='ms-span error'>Request acceptance sections</h6>";
        data += '<ul>';
        $.each(requested_approval_indicators_unique, function (idx, id) {             
          data += '<li class="complete">';
          let obj = getModalErrorData(id);
          data += "<div dusk=survey_submit_request_acceptance_indicator_" + obj.indicatorNumber + " class='go-to-page not_assigned' id='to-page-" + obj.indicatorPage + "' data-id=" + obj.indicatorId + ">" + obj.indicatorText + "</div>";
          data += '</li>';          
        });
        data += '</ul>';
        data += '</div>';
      }
    }
  }

  $('#questionnaire-submit').prop('disabled', !final_submit);
  
  let obj = {
    'large': !final_submit,
    'action': 'submit',
    'title': $('.questionnaire-title').text(), 
    'html': data,
    'btn': 'Submit'
  };

  setModal(obj);

  pageModal.show();
}