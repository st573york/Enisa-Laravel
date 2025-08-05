window.skipFadeOut = false;

let tooltipTimer = false;
let isTooltipOpen = false;
let tooltipDelay = 100;

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  },
  statusCode: {
    401: function () { // Unauthenticated - get request
      window.location.href = '/';
    },
    419: function () { // Unauthenticated - post request - csrf token mismatch
      window.location.href = '/';
    }
  }
});

// Scroll to the bottom of the page
window.scrollToBottom = function () {
  window.scrollTo({
      top: document.documentElement.scrollHeight, // Scroll to the bottom
      behavior: 'smooth' // Smooth scrolling animation
  });
}

// Scroll to the top of the page
window.scrollToTop = function () {
  window.scrollTo({
      top: 0, // Scroll to the top
      behavior: 'smooth' // Smooth scrolling animation
  });
}

// Manage button visibility during scrolling
function manageScrollButtons()
{
  const btnTop = document.querySelector('.btn-top');
  const btnBottom = document.querySelector('.btn-bottom');
  let scrollTimeout;

  window.addEventListener('scroll', () => {
      // Show buttons during scrolling
      if (btnTop) {
          btnTop.classList.remove('d-none');
      }
      if (btnBottom) {
          btnBottom.classList.remove('d-none');
      }

      // Clear the previous timeout
      clearTimeout(scrollTimeout);

      // Set a timeout to detect the end of scrolling
      scrollTimeout = setTimeout(() => {
          if (window.scrollY === 0 && btnTop) {
              btnTop.classList.add('d-none'); // Hide btnTop when at the top
          }
          if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight && btnBottom) {
              btnBottom.classList.add('d-none'); // Hide btnBottom when at the bottom
          }
      }, 50); // Delay to detect scroll end
  });
}

// Initialize scroll button behavior
manageScrollButtons();

$(document).ready(function () {
  $.fn.DataTable.ext.errMode = 'none';

  $('.dataTables_filter').each(function() {
    let label = document.querySelector(`#${this.id} label`);
    label.style.display = 'flex';
    label.style.alignItems = 'center';

    $(this).find('input').before('<span class="info-icon-black" data-bs-toggle="tooltip" data-bs-placement="right" style="padding: 7px 0 0 0;" title="Use quotes &quot;&quot; to search exact phrases"></span>');
  });

  if (!skipFadeOut) {
    $('.loader').fadeOut();
  }
  pollExportFile();
});

$(document).on('ajaxStop', function () {
  if (!skipFadeOut) {
    $('.loader').fadeOut();
  }
});

$(document).on('mouseover', '[data-bs-toggle="tooltip"]', function (e) {
  $('.tooltip').tooltip('hide');

  $(e.currentTarget).tooltip({
    'trigger': 'manual',
    'placement': (e.currentTarget.getAttribute('data-bs-placement')) ? e.currentTarget.getAttribute('data-bs-placement') : 'top',
    'html': true
  }).tooltip('show');
});

$(document).on('mouseleave', '[data-bs-toggle="tooltip"]', function (e) {
  if (!isTooltipOpen) {
    tooltipTimer = setTimeout(function () {
      $(e.currentTarget).tooltip('hide');
    }, tooltipDelay);
  }
});

$(document).on('mouseover', '.tooltip', function () {
  clearTooltipTimeout();

  isTooltipOpen = true;
});

$(document).on('mouseleave', '.tooltip', function (e) {
  tooltipTimer = setTimeout(function () {
    $(e.currentTarget).tooltip('hide');
  }, tooltipDelay);

  isTooltipOpen = false;
});

$(document).on('show.bs.modal', '.modal', function () {
  $('.tooltip').tooltip('hide');
});

$(document).on('click', '#globan .wt-link', function () {
  ariaToggle('#globan .wt-link', '#globan .globan-dropdown');

  $('.wt-link span').toggleClass('dark-text');
});

$(document).on('click', '#language-switcher .icon.wrapper', function () {
  $('#languange-group').toggleClass('d-flex');
});

$(document).on('click', '.language-list li', function () {
  $('#language-switcher .ri-span').text($(this).find('.country-code').text());
}); 

$(document).on('click', '#tab-svg', function () {
  $('.indicators.tabbed').toggleClass('positioned');
});

$(document).on('click', function (e) {
  if ($(e.target).closest('.filters-tab').length === 0) {
    $('.filters-tab.indicators.tabbed').removeClass('positioned');
  }
});

// All checkbox - thead
$(document).on('click', '.enisa-table-group #item-select-all', function () {
  $('.enisa-table-group tbody .form-check-input:not(:disabled):not(.switch)').prop('checked', $(this).is(':checked'));
});

// All checkbox - thead
$(document).on('update_select_all', '.enisa-table-group #item-select-all', function () {
  $(this).prop('checked', (
    getDataTableCheckboxesEnabledByPage() &&
    getDataTableCheckboxesCheckedByPage() == getDataTableCheckboxesEnabledByPage())
  );
});

// Each checkbox - tbody
$(document).on('click', '.enisa-table-group tbody .form-check-input:not(.switch)', function () {
  $('.enisa-table-group #item-select-all').prop('checked', (
    getDataTableCheckboxesEnabledByPage() &&
    getDataTableCheckboxesCheckedByPage() == getDataTableCheckboxesEnabledByPage())
  );
});

$(document).on('scroll', function () {
  let display = (window.scrollY > 50) ? 'block' : 'none';

  $('.btn-top').css('display', display);
});

$(document).on('click', '.btn-top', function () {
  scrollToTop();
});

$(document).on('click', '.btn-bottom', function () {
  scrollToBottom();
});

$(document).on('change', '#index-year-select, #questionnaire-year-select', function () {
  let year_select = $('option:selected', this).attr('data-year');
  
  localStorage.setItem('index-year', year_select);
  document.cookie = 'index-year=' + year_select + '; path=/; SameSite=Lax; Secure';

  var changeEvent = new Event('yearChange');
  window.dispatchEvent(changeEvent);
});

// Clean the input file to correctly reset file upload
$(document).on('click', '#formFile', function () {
  $('#formFile').val('');
});

function clearTooltipTimeout() {
  if (tooltipTimer) {
    window.clearTimeout(tooltipTimer);

    tooltipTimer = false;
  }
}

function ariaToggle(expanded, hidden) {
  var aria = $(expanded).attr('aria-expanded');

  if (aria == 'true') {
    aria_expanded = 'false';
    aria_hidden = 'true';
  }
  else {
    aria_expanded = 'true';
    aria_hidden = 'false';
  }

  $(expanded).attr('aria-expanded', aria_expanded);
  $(hidden).attr('aria-hidden', aria_hidden);

  $('select').select2();
}

window.getIndexYear = function () {
  let year = localStorage.getItem('index-year');
  let year_select = $('#index-year-select').val();
  
  if (!year) {
    localStorage.setItem('index-year', year_select);
  }

  if (document.cookie.indexOf('index-year=') === -1) {
    document.cookie = 'index-year=' + year_select + '; path=/; SameSite=Lax; Secure';
  }

  // If the selected year is not in the list, get the first one
  if ($('#index-year-select option[value="' + year + '"]').length == 0) {
    year = $('#index-year-select').find('option:first').val();
  }

  return year;
}

window.exportData = function (obj) {
  $('.loader').fadeIn();
  let id = $('.loaded-index').val();

  if (obj.requestLocation == 'survey') {
    id = $('.loaded-questionnaire').val();
  }

  $.ajax({
    'url': '/export/data/create/' + id,
    'type': 'post',
    'data': {
      'countries': obj.countries,
      'sources': obj.sources,
      'requestLocation': obj.requestLocation
    },
    success: function () {
      localStorage.setItem('pending-export-file', 1);
      localStorage.setItem('pending-export-task', 'ExportData');
      localStorage.setItem('pending-element-id', obj.element);

      updateDownloadButton('downloadInProgress');

      pollExportFile();

      $('.loader').fadeOut();
    },
    error: function(req) {
      $('.loader').fadeOut();

      setAlert({
        'status': 'error',
        'msg': req.responseJSON.error
      });
    }
  });
}

window.pollExportFile = function () {
  let downloadPoll;

  if (localStorage.getItem('pending-export-file') == 1) {
    downloadPoll = setInterval(function () {
      getExportFile(downloadPoll);
    }, 5000);
  }
  else {
    clearInterval(downloadPoll);
  }
}

window.getExportFile = function (downloadPoll) {
  if (localStorage.getItem('pending-export-file') == 1)
  {
    skipClearErrors = true;

    $.ajax({
      'url': '/export/data/download',
      'data': { 'task': localStorage.getItem('pending-export-task') },
      xhr: function() {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 2)
            {
                if (xhr.status == 200) {
                    xhr.responseType = 'blob';
                }
                else {
                    xhr.responseType = 'text';
                }
            }
        };

        return xhr;
      },
      success: function (blob, status, xhr) {
        // check for a filename
        var filename = '';
        var disposition = xhr.getResponseHeader('Content-Disposition');
        if (disposition && disposition.indexOf('attachment') !== -1)
        {
          var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
          var matches = filenameRegex.exec(disposition);
          if (matches != null && matches[1])
          {
            filename = matches[1].replace(/['"]/g, '');
            const patternToReplace = '\\d+-';
            filename = filename.replace(new RegExp(patternToReplace), '');
          }
        }

        if (typeof window.navigator.msSaveBlob !== 'undefined') {
          // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
          window.navigator.msSaveBlob(blob, filename);
        }
        else
        {
          var URL = window.URL || window.webkitURL;
          var downloadUrl = URL.createObjectURL(blob);

          if (filename)
          {
            // use HTML5 a[download] attribute to specify filename
            var a = document.createElement('a');

            // safari doesn't support this yet
            if (typeof a.download === 'undefined') {
              window.location.href = downloadUrl;
            }
            else
            {
              a.href = downloadUrl;
              a.download = filename;
              document.body.appendChild(a);
              a.click();
            }
          }
          else {
            window.location.href = downloadUrl;
          }

          setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
        }

        localStorage.removeItem('pending-export-file');
        localStorage.removeItem('pending-export-task');

        clearInterval(downloadPoll);

        var downloadEvent = new Event('downloadCompleted');
        window.dispatchEvent(downloadEvent);
      }
    }).fail(function (req) {
      console.log(req.responseJSON.error);

      if (req.status == 404)
      {
        var downloadEvent = new Event('downloadInProgress');
        window.dispatchEvent(downloadEvent);
      }
      else
      {
        localStorage.removeItem('pending-export-file');
        localStorage.removeItem('pending-export-task');

        clearInterval(downloadPoll);

        var downloadEvent = new Event('downloadFailed');
        window.dispatchEvent(downloadEvent);
      }
    });
  }

  skipClearErrors = false;
}

window.updateDownloadButton = function (state) {
  let download_section = $('.download-section[item-id="' + localStorage.getItem('pending-element-id') + '"]');

  if (!download_section.length) {
    return;
  }

  if (state == 'downloadInProgress')
  {
    if (download_section.hasClass('button-spinner-wrapper'))
    {
      download_section.find('#button-spinner').parent().removeClass('d-none');
      download_section.find('.item-download').addClass('d-none');
    }
    else
    {
      download_section.find('button').prop('disabled', true);
      download_section.find('.in-progress, #button-spinner').removeClass('d-none');
      download_section.find('.start').addClass('d-none');
    }

    $('.download').prop('disabled', true);
    $('.item-download').addClass('icon-xls-download-deactivated');
  }
  else if (state == 'downloadCompleted' ||
           state == 'downloadFailed')
  {
    if (download_section.hasClass('button-spinner-wrapper'))
    {
      download_section.find('#button-spinner').parent().addClass('d-none');
      download_section.find('.item-download').removeClass('d-none');
    }
    else
    {
      download_section.find('button').prop('disabled', false);
      download_section.find('.start').removeClass('d-none');
      download_section.find('.in-progress, #button-spinner').addClass('d-none');
    }

    $('.download:not(.cannot-download)').prop('disabled', false);
    $('.item-download:not(.cannot-download)').removeClass('icon-xls-download-deactivated');

    localStorage.removeItem('pending-element-id');
  }
}

window.getIndexCountry = function () {
  return localStorage.getItem('index-country');
}

window.getDataTableCheckboxesAllPages = function () {
  let table = $('.enisa-table-group').DataTable();
  let rows = table.$('.form-check-input:not(:disabled):not(.switch)', { 'page': 'all' });

  return rows.length;
}

window.getDataTableCheckboxesCheckedAllPages = function () {
  let table = $('.enisa-table-group').DataTable();
  let rows = table.$('.form-check-input:checked:not(:disabled):not(.switch)', { 'page': 'all' });

  return rows.length;
}

window.convertToLocalTimestamp = function () {
  $('.local-timestamp').each(function() {
    let timestamp = $(this).text();

    if (timestamp)
    {
      let [year, month, day] = timestamp.split(' ')[0].split('-');
      let [hours, minutes, seconds] = timestamp.split(' ')[1].split(':');

      let formatted_timestamp = `${year}-${month}-${day}T${hours}:${minutes}:${seconds}Z`;
        
      let utc_date = new Date(formatted_timestamp);
      let local_time = utc_date.toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      }).replace(/\//g, '-').replace(/,/g, '');

      $(this).text(local_time);
    }
  });
}

function getDataTableCheckboxesCheckedByPage() {
  return $('.enisa-table-group tbody .form-check-input:checked:not(:disabled):not(.switch)').length;
}

function getDataTableCheckboxesEnabledByPage() {
  return $('.enisa-table-group tbody .form-check-input:not(:disabled):not(.switch)').length;
}

window.addEventListener('downloadInProgress', function () {
  updateDownloadButton('downloadInProgress');
});

window.addEventListener('downloadCompleted', function () {
  updateDownloadButton('downloadCompleted');
});

window.addEventListener('downloadFailed', function () {
  updateDownloadButton('downloadFailed');
});

