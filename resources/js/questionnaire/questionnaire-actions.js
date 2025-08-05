$(document).on('input', '.input-choice input', function () {
  toggleAnswers(this);
  toggleReference(this);
});

$(document).on('input', '.actual-answers .form-input.master', function () {
  toggleOptions(this);
});

window.toggleAnswers = function(e) {
  let choice = $(e).val();

  $(e).closest('.form-indicators').find('.actual-answers input').each(function (id, idx) {
    if (choice == 3) 
    {
      if ($(idx).parents().eq(1).hasClass('multiple-choice') || 
          $(idx).parents().eq(1).hasClass('single-choice')) 
      {
        $(idx).prop('checked', false);
      } 
      else {
        $(idx).val('');
      }

      $(idx).prop('disabled', true);
    } 
    else {
      $(idx).prop('disabled', false);
    }
  });
}

window.toggleReference = function(e) {
  let choice = $(e).val();
  let reference = $(e).closest('.form-indicators').siblings('.form-references');

  reference.toggleClass('d-none', (choice == 3));  
  if (choice == 3) {
    reference.find('textarea').val('');
  }
}

window.toggleOptions = function(e) {
  $(e).closest('.form-indicators').find('.actual-answers input').each(function (id, idx) {
    if ($(e).prop('id') != $(idx).prop('id'))
    {
      if ($(e).is(':checked')) {
        $(idx).prop('checked', false);
      }
      $(idx).prop('disabled', $(e).is(':checked'));
    }
  });
}