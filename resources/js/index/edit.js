let index_tree_loaded = false;
let index_json_loaded = false;

$(document).ready(function () {
    getIndexTree();
    getIndexJson(null);
});

$(document).on('draw.dt', function () {
    $('.tooltip').tooltip('hide');
});

$(document).on('click', '#save-changes', function () {
    $('.loader').fadeIn();

    let formData = $('form').serializeArray();
    formData.push({'name': 'draft', 'value': !$('#blockSwitch').is(':checked')});
    formData.push({'name': 'eu_published', 'value': $('#euSwitch').is(':checked')});
    formData.push({'name': 'ms_published', 'value': $('#msSwitch').is(':checked')});

    $.ajax({
        'url': '/index/edit/' + indexId,
        'type': 'POST',
        'data': getJsonData(formData),
        success: function () {
            location.reload();
        },
        error: function (req) {
            $('.loader').fadeOut();

            if (req.status == 400) {            
                showInputErrors(req.responseJSON);                
            }      
            else if (req.status == 405) 
            {        
                setAlert({
                    'status': 'error',
                    'msg': req.responseJSON.error
                });
            }             
          }
    });
});

$(document).on('click', '#delete-index', function () {
    let data = "<input hidden id=\"item-route\"/>" +
               "<div dusk=\"delete_index_modal_text\" class=\"warning-message\">Index '" + indexName + "' will be deleted. Are you sure?</div>";

    let obj = {
        'modal': 'warning',
        'id': indexId,
        'action': 'delete',
        'route': '/index/delete/' + indexId,
        'title': 'Delete Index',
        'html': data,
        'btn': 'Delete'
    };
    setModal(obj);

    pageModal.show();
});

$(document).on('click', '#process-data', function() {
    $('.loader').fadeIn();
    let route = $('.modal #item-route').val();

    $.ajax({
        'url': route,
        'type': 'post',
        'contentType': false,
        'processData': false,
        success: function() {
            window.location.href = '/index/management';
        },
        error: function(req) {
            $('.loader').fadeOut();

            if (req.status == 405)
            {
                pageModal.hide();

                setAlert({
                    'status': 'error',
                    'msg': req.responseJSON.error
                });
            }
        }
    });
});

$(document).on('change', '#indexYear', function () {
    getIndexJson($(this).val());
});

$(document).on('click', '.index-edit:not(.active, .current)', function () {
    areaId = null;
    subareaId = null;

    $('#configure-index-tree').find('.current').removeClass('current');
    $('#configure-index-tree .index-edit').addClass('current');
    
    $('#subarea-table, #indicator-table').closest('.table-section').addClass('hidden');
    $('#area-table').closest('.table-section').removeClass('hidden');

    nodeData('#area-table');
})

$(document).on('click', '.area-edit:not(.active, .current)', function () {
    areaId = $(this).attr('data-id');
    subareaId = null;

    let area_name = ($(this).text()) ? $(this).text() : $('#area-table span.area-edit[data-id="' + areaId + '"]').text();    
    $('.area-name').text(area_name).parent().attr('data-id', areaId);

    $('#configure-index-tree').find('.current').removeClass('current');
    $('#configure-index-tree .area-edit[data-id=' + areaId + ']').addClass('current');

    $('#area-table, #indicator-table').closest('.table-section').addClass('hidden');
    $('#subarea-table').closest('.table-section').removeClass('hidden');

    nodeData('#subarea-table');
});

$(document).on('click', '.subarea-edit:not(.active, .current)', function () {
    if ($(this).hasClass('tree-node')) 
    {
        areaId = $(this).attr('parent-id');

        $('.area-name').text($('#configure-index-tree .area-edit[data-id=' + areaId + ']').text()).parent().attr('data-id', areaId);
    }
    
    subareaId = $(this).attr('data-id');

    let subarea_name = ($(this).text()) ? $(this).text() : $('#subarea-table span.subarea-edit[data-id="' + subareaId + '"]').text();
    $('.subarea-name').text(subarea_name);

    $('#configure-index-tree').find('.current').removeClass('current');
    $('#configure-index-tree .subarea-edit[data-id=' + subareaId + ']').addClass('current');
    
    $('#area-table, #subarea-table').closest('.table-section').addClass('hidden');
    $('#indicator-table').closest('.table-section').removeClass('hidden');

    nodeData('#indicator-table');
});

$(document).on('click', '#configure-index-tree .tree-arrow', function () {
    var arrow = $(this);
    arrow.parent().children('ul').toggleClass('collapsed');

    if (arrow.parent().children('ul').hasClass('collapsed')) {
        $(this).removeClass('open');
    } 
    else {
        $(this).addClass('open');
    }
});

function getIndexTree() 
{
    $('.loader').fadeIn();

    $.ajax({
        'url': '/index/tree/' + indexId,
        'type': 'POST',
        'data': {'indexJson': indexJson},
        success: function (data) {
            $('.conf-tree-col').html(data);

            index_tree_loaded = true;

            var loadEvent = new Event('loadEvent');
            window.dispatchEvent(loadEvent);
        }
    });
}

function getIndexJson(indexYear)
{
    $('.loader').fadeIn();
    
    $.ajax({
        'url': '/index/json/' + indexId,
        'data': {'indexYear': indexYear},
        success: function (data) {
            indexJson = data.indexJson;
            if (data.indexJson.contents == undefined || 
                indexYear != null)
            {
                indexJson = {'contents': []};
                $('#configure-index-tree ul').empty();

                $('.index-edit').trigger('click');
            }

            areas = data.areas;
            subareas = data.subareas;
            indicators = data.indicators;

            nodeData('#area-table');
            getNodeCount();
            fillEmptyWeights();

            index_json_loaded = true;

            var loadEvent = new Event('loadEvent');
            window.dispatchEvent(loadEvent);
        }
    });
}

function fillEmptyWeights() 
{
    if (indexJson != undefined && indexJson.contents != undefined) 
    {
        for (const [areaKey, area] of Object.entries(indexJson.contents)) 
        {
            if (indexJson.contents[areaKey].area.weight == undefined) {
                indexJson.contents[areaKey].area.weight = 0;
            }
            if (area.area.subareas != undefined) 
            {
                for (const [subareaKey, subarea] of Object.entries(area.area.subareas)) 
                {
                    if (indexJson.contents[areaKey].area.subareas[subareaKey].weight == undefined) {
                        indexJson.contents[areaKey].area.subareas[subareaKey].weight = 0;
                    }
                    if (subarea.indicators != undefined) 
                    {
                        for (const [indicatorKey, indicator] of Object.entries(subarea.indicators)) 
                        {
                            if (indexJson.contents[areaKey].area.subareas[subareaKey].indicators[indicatorKey].weight == undefined) {
                                indexJson.contents[areaKey].area.subareas[subareaKey].indicators[indicatorKey].weight = 0;
                            }
                        }
                    }
                }
            }
        }
    }
}

function getNodeCount() 
{
    usedAreaCount = 0;
    usedSubareaCount = 0;
    usedIndicatorCount = 0;

    if (indexJson != undefined && indexJson.contents != undefined) 
    {
        for (const [key, area] of Object.entries(indexJson.contents)) 
        {
            usedAreaCount++;
            if (area.area.subareas != undefined) 
            {
                for (const [key, subarea] of Object.entries(area.area.subareas)) 
                {
                    usedSubareaCount++;
                    if (subarea.indicators != undefined) 
                    {
                        for (const [key, indicators] of Object.entries(subarea.indicators)) {
                            usedIndicatorCount++;
                        }
                    }

                }
            }
        }
    }
}

function getJsonData(data) 
{
    var indexed_obj = {};
    
    $.map(data, function (obj) {
        indexed_obj[obj['name']] = obj['value'];
    });

    return indexed_obj;
}

window.addEventListener('loadEvent', function() {
    if (index_tree_loaded &&
        index_json_loaded)
    {
        $('.loader').fadeOut();
    }
});

