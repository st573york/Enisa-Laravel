let defaultNodes;
let selectedNodes;
let chartData;
let mapData;
let barChart;
let euAverageName;
let year;
let eu_published;
let ms_published;
let isAdmin;
let isEnisa;
let selectedNode = 'Index';
let rangePlotHtml = '';
let current_tab = 'map-tab';
let cyprusIdx;
let northernCyprusIdx;

$(document).ready(function () {
  defaultNodes = getNodes();
  
  $.ajax({
    url: '/index/configurations/get/' + $('#areas-subareas').val() + '/' + getIndexYear()
  }).done(function (data) {
    mapData = data.mapData;
    chartData = data.chartData;
    euAverageName = data.euAverageName;
    year = data.configuration.year;
    eu_published = data.configuration.eu_published;
    ms_published = data.configuration.ms_published;
    isAdmin = data.isAdmin;
    isEnisa = data.isEnisa;
  
    if (mapData) 
    {
      setCountriesIdx();
      drawMap();
    }
    if (chartData)
    {
      buildBarPlot();
      buildRangePlot();
    }

    if (!ms_published) {
      $('#form-country-0').empty().trigger('change');
    }

    var loadEvent = new Event('comparisonLoaded');
    window.dispatchEvent(loadEvent);
  });
});

$(document).on("change", ".all", function () {
  let selectAll = $(this);
  if (selectAll.prop("checked") == true) {
    selectAll.parent().siblings().children("input").prop("checked", true);
  } else {
    selectAll.parent().siblings().children("input").prop("checked", false);
  }
  checkTree();
});

$(document).on("click", "#indicators-tree .tree-arrow", function () {
  let arrow = $(this);
  arrow.parent().children("ul").toggleClass("collapsed");
  if (arrow.parent().children("ul").hasClass("collapsed")) {
    $(this).removeClass("open");
  } else {
    $(this).addClass("open");
  }
});

function getNodes() {
  nodes = [];
  $(".checkbox-input:not('.all'):checked").each(function (i, input) {
    nodes.push($(input).prop("id"));
  });

  return nodes;
}

function buildRangePlot() {
  if (rangePlotHtml == '') {
    $.ajax({
      url: '/index/render/slider/' + getIndexYear()
    }).done(function (html) {
      rangePlotHtml = html;

      $("#sliderChart").html(rangePlotHtml);
      fixRangePlot();
    });
  }
  else {
    $("#sliderChart").html(rangePlotHtml);
    fixRangePlot();
  }
}

function downloadTreeImage(url) {
  var a = $("<a style='display:none'></a>")
    .attr("href", url)
    .attr("download", "Tree.png")
    .appendTo("body");

  a[0].click();
  a.remove();
}

function buildBarPlot() {
  selectedNodes = getNodes();
  let series = [];
  let indices = [];
  let yAxis = {
    type: 'category',
    data: null
  }
  let indexName;
  let selected = $("#first-select select").val();
  $.each(selected, function (idx, selection) {
    indexName = selection;
    indices.push(indexName);
  });
  indices.push(euAverageName + ' ' + year);

  indices.forEach(function (index) {
    let indexLocation = 0;
    y = [];
    x = [];
    let name;
    selectedNodes.forEach(function (node) {
      let map = node.split("-");
      let value;
      let valueX;
      let valueY;
      while (chartData[0]["global_index_values"][indexLocation] && 
             Object.keys(chartData[0]["global_index_values"][indexLocation])[0] !== index)
      {
        indexLocation++;
      }
      if (node == -1) {
        valueY = "Aggregated Index";
        value = chartData[0]["global_index_values"][indexLocation];
      }
      else {
        if (map.length == 1) {
          valueY = chartData[map[0]]["area"]["name"];
          value = chartData[map[0]]["area"]["values"][indexLocation];
        }
        if (map.length == 2) {
          valueY = chartData[map[0]]["area"]["subareas"][map[1] - 1]["name"];
          value = chartData[map[0]]["area"]["subareas"][map[1] - 1]["values"][indexLocation];
        }
        if (map.length == 3) {
          valueY = chartData[map[0]]["area"]["subareas"][map[1] - 1]["indicators"][map[2] - 1]["name"];
          value = chartData[map[0]]["area"]["subareas"][map[1] - 1]["indicators"][map[2] - 1]["values"][indexLocation];
        }
      }
      
      name = (value) ? Object.keys(value)[0] : null;
      valueX = (value) ? Object.values(value)[0] : null;
      y.unshift(valueY);
      x.unshift(valueX);
    });

    if (name) {
      let serie = {
        name: name,
        type: 'bar',
        data: x,
        itemStyle: {
          color: name.includes(euAverageName) ? "rgb(37,74,165)" : "auto"
        }
      };

      series.push(serie);
    }
  });
  yAxis.data = y;

  var chartDom = document.getElementById('chart');
  var option;
  barChart = echarts.init(chartDom);

  option = {
    toolbox: {
      show: true,
      left: 'right',
      top: 'top',
      feature: {
        saveAsImage: {
          name: "Barchart",
          emphasis: {
            iconStyle: {
              color: '#141414',
              textAlign: 'right'
            }
          },
          icon: "image://data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M20.75 20.75C20.75 20.0625 20.1875 19.5 19.5 19.5L4.5 19.5C3.8125 19.5 3.25 20.0625 3.25 20.75C3.25 21.4375 3.8125 22 4.5 22L19.5 22C20.1875 22 20.75 21.4375 20.75 20.75ZM17.7375 9.5L15.75 9.5L15.75 3.25C15.75 2.5625 15.1875 2 14.5 2L9.5 2C8.8125 2 8.25 2.5625 8.25 3.25L8.25 9.5L6.2625 9.5C5.15 9.5 4.5875 10.85 5.375 11.6375L11.1125 17.375C11.2281 17.4909 11.3655 17.5828 11.5167 17.6455C11.6679 17.7083 11.83 17.7406 11.9938 17.7406C12.1575 17.7406 12.3196 17.7083 12.4708 17.6455C12.622 17.5828 12.7594 17.4909 12.875 17.375L18.6125 11.6375C19.4 10.85 18.85 9.5 17.7375 9.5V9.5Z' fill='black'/%3E%3C/svg%3E"
        }
      }
    },
    color: [
      '#9d9c9c',
      '#61a0a8',
      '#d48265',
      '#91c7ae',
      '#749f83',
      '#ca8622',
      '#bda29a',
      '#6e7074',
      '#546570',
      '#c4ccd3'
    ],
    tooltip: {
      trigger: 'axis',
      axisPointer: {
        type: 'shadow'
      }
    },
    legend: {},
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'value',
      boundaryGap: [0, 0.01]
    },
    yAxis: yAxis,
    series: series
  };

  option && barChart.setOption(option, true);
  barChart.resize();
}

function fixRangePlot() {
  let indices = [];
  let indexName;
  let selected = $("#first-select select").val();
  $.each(selected, function (idx, selection) {

    indexName = selection;
    indices.push(indexName);
  });
  indices.push(euAverageName + ' ' + year);

  indices.forEach(function (index) {
    let indexLocation = 0;
    let indexName = index.replaceAll(" ", ".");
    $("." + indexName).removeClass("hidden");

    defaultNodes.forEach(function (node) {
      let map = node.split("-");
      while (chartData[0]["global_index_values"][indexLocation] && 
             Object.keys(chartData[0]["global_index_values"][indexLocation])[0] !== index)
      {
        indexLocation++;
      }
      if (node == -1) {
        $(".global-values .values").removeClass("hidden");
      } else {
        if (map.length == 1) {
          $(".area-" + map[0]).removeClass("hidden");
          $(".area-" + map[0] + " .values").removeClass("hidden");
        }
        if (map.length == 2) {
          $(".area-" + map[0]).removeClass("hidden");
          $(".area-" + map[0] + " .subarea-" + map[1]).removeClass("hidden");
          $(".area-" + map[0] + " .subarea-" + map[1] + " .values").removeClass(
            "hidden"
          );
        }
        if (map.length == 3) {
          $(".area-" + map[0]).removeClass("hidden");
          $(".area-" + map[0] + " .subarea-" + map[1]).removeClass("hidden");
          $(
            ".area-" + map[0] + " .subarea-" + map[1] + " .indicator-" + map[2]
          ).removeClass("hidden");
          $(
            ".area-" +
            map[0] +
            " .subarea-" +
            map[1] +
            " .indicator-" +
            map[2] +
            " .values"
          ).removeClass("hidden");
        }
      }
    });
  });

  //plotly palette colors
  var key_value_list = document.querySelectorAll(
    "#sliderChart .plotly-color:not(.hidden) .key-value"
  );

  var tree_value_list = document.querySelectorAll(
    "#sliderChart .plotly-color:not(.hidden) .tree-value"
  );

  current_plotly_colors = [];

  for (var j = 0; j < indices.length; j++) {
    current_plotly_colors[j] = plotly_colors[j];
  }
  plotly_palette(current_plotly_colors, key_value_list);
  plotly_palette(current_plotly_colors, tree_value_list);
}

$(document).on("click", "#compare", function () {
  if (current_tab == 'barchart-tab') {
    buildBarPlot();
  }
  if (current_tab == 'tree-tab') {
    buildRangePlot();
  }
});

$(document).on("click", "#reset", function () {
  // Select first child of select2 - Sunburst Tab
  $('#sunburst-form-country-0')
    .val($('#sunburst-form-country-0 option:first-child').val())
    .select2()
    .trigger('change');

  // Select first child of select2 - Tree/Barchart Tabs
  $('#form-country-0')
    .val($('#form-country-0 option:first-child').val())
    .select2()
    .trigger('change');

  // Close open tree arrows - Barchart Tab
  $('#indicators-tree .tree-arrow.open').trigger('click');

  // Check only default checkboxes - Barchart Tab
  $(".checkbox-input").prop("checked", false);
  $(".checkbox-input.default").prop("checked", true);

  // Click view button - Tree/Barchart Tabs
  $('#compare').removeClass('disable-btn').trigger('click');
});

$(document).on("click", ".delete-index-row", function () {
  $(this).parent().parent(".added-index").remove();
});

$(document).on("click", "#sliderChart .tree-arrow", function () {
  let arrow = $(this);
  arrow.parent().children("ul").toggleClass("collapsed");
  if (arrow.parent().children("ul").hasClass("collapsed")) {
    $(this).removeClass("open");
  } else {
    $(this).addClass("open");
  }
});

function plotly_palette(colors, list) {
  var newlist = Array.prototype.slice.call(list);
  colors[colors.length - 1] = '#254AA5'
  colorSameLengthList = [];
  for (var i = 0; i < newlist.length; i++) {
    colorSameLengthList[i] = colors[i % colors.length];
    newlist[i].style.color = colorSameLengthList[i];
  }
}

let plotly_colors = [
  '#2f4554',
  '#61a0a8',
  '#d48265',
  '#91c7ae',
  '#749f83',
  '#ca8622',
  '#bda29a',
  '#6e7074',
  '#546570',
  '#c4ccd3'
];

/*========= Chart Select =========*/
$(document).ready(function () {
  $("#tab-select #map-tab").click(function () {
    current_tab = $(this).attr('id');

    $("#map-wrapper").removeClass("hidden");
    $("#map").removeClass("hidden");
    $("#sunburst").addClass("hidden");
    $(".label-section").addClass("hidden");
    $("#sunburst-form-wrap").addClass("hidden");
    $("#all-forms-wrap").removeClass("hidden");
    $("#chart").addClass("hidden");
    $("#sliderChart").addClass("hidden");
    $("#downloadTreeImage").addClass("hidden");
    $(".indicators").addClass("hidden");
    $(".indicators").removeClass("sunburst-operate");
    $(".width-expand").addClass("w-100");
    $(".indicators").removeClass("tree-operate");
    $(".sunburst-label").addClass("hidden");
    $(".indicators .btn-wrapper").removeClass("hidden");
  });

  $("#tab-select #sunburst-tab").click(function () {
    current_tab = $(this).attr('id');

    $("#sunburst").removeClass("hidden");
    $(".label-section").removeClass("hidden");
    $("#sunburst-form-wrap").removeClass("hidden");
    $("#all-forms-wrap").addClass("hidden");
    $("#map-wrapper").addClass("hidden");
    $("#map").addClass("hidden");
    $("#chart").addClass("hidden");
    $("#sliderChart").addClass("hidden");
    $("#downloadTreeImage").addClass("hidden");
    $(".indicators").removeClass("hidden");
    $(".indicators").addClass("sunburst-operate");
    $(".width-expand").removeClass("w-100");
    $("#indicators-tree").addClass("hidden");
    $(".indicators").removeClass("tree-operate");
    $(".sunburst-label").removeClass("hidden");
    $(".indicators .btn-wrapper").addClass("hidden");

    $('#reset').trigger('click');

    if (!ms_published) {
      $(".indicators").addClass("hidden");
    }
  });

  $("#tab-select #tree-tab").click(function () {
    current_tab = $(this).attr('id');

    $("#sliderChart").removeClass("hidden");
    $("#downloadTreeImage").removeClass("hidden");
    $("#map-wrapper").addClass("hidden");
    $("#map").addClass("hidden");
    $("#sunburst").addClass("hidden");
    $(".label-section").addClass("hidden");
    $("#sunburst-form-wrap").addClass("hidden");
    $("#all-forms-wrap").removeClass("hidden");
    $("#chart").addClass("hidden");
    $(".indicators").removeClass("hidden");
    $(".indicators").removeClass("sunburst-operate");
    $(".indicators").addClass("tree-operate");
    $(".width-expand").removeClass("w-100");

    $("#indicators-tree").addClass("hidden");

    $(".sunburst-label").addClass("hidden");
    $(".indicators .btn-wrapper").removeClass("hidden");

    $('#reset').trigger('click');

    if (!ms_published) {
      $(".indicators").addClass("hidden");
    }
  });

  $("#tab-select #barchart-tab").click(function () {
    current_tab = $(this).attr('id');
    setTimeout(() => {
      checkTree();
      buildBarPlot();
    }, 50);

    $("#chart").removeClass("hidden");
    $("#map-wrapper").addClass("hidden");
    $("#map").addClass("hidden");
    $("#sunburst").addClass("hidden");
    $("#sunburst-form-wrap").addClass("hidden");
    $("#all-forms-wrap").removeClass("hidden");
    $("#sliderChart").addClass("hidden");
    $("#downloadTreeImage").addClass("hidden");
    $(".indicators").removeClass("hidden");
    $(".indicators").removeClass("sunburst-operate");
    $(".indicators").removeClass("tree-operate");

    $("#indicators-tree").removeClass("hidden");
    $(".width-expand").removeClass("w-100");

    $(".sunburst-label").addClass("hidden");
    $(".indicators .btn-wrapper").removeClass("hidden");

    $('#reset').trigger('click');

    if (!ms_published)
    {
      $("#all-forms-wrap").addClass("hidden");
      $("#indicators-tree").removeClass("mt-5");
      $('.index-wrapper').css('max-height', 'none');
    }
  });
});

/*========= Check Tree checkboxes =========*/
window.saveCapture = function () {
  html2canvas($("#sliderChart")[0], { backgroundColor: '#ffffff' }).then(function (canvas) {
    downloadTreeImage(canvas.toDataURL("image/png"));
  })
}

function checkTree() {
  $("#compare").toggleClass("disable-btn", !$("#indicators-tree input").is(":checked"));
}

$(document).ready(function () {
  $("#indicators-tree input").change(function () {
    checkTree();
  });
});

function setCountriesIdx() {
  $.each(mapData, function (idx, i) {
    switch (i.name) {
      case 'Cyprus':
        cyprusIdx = idx;

        break;
      case 'N. Cyprus':
        northernCyprusIdx = idx;

        break;
    }
  });
}

function drawMap() {
  $('#baseline-table tbody').empty().append(
    '<tr>' +
    '<td>' + selectedNode + '</td>' +
    '<td>' + mapData[0].value + '</td>' +
    ((!isAdmin && !isEnisa && ms_published) ? '<td>' + mapData[0].country_value + '</td>' : '') +
    '</tr>'
  );

  $.each(mapData[0].children, function (idx, i) {
    $('#baseline-table tbody').append(
      '<tr>' +
      '<td' + ($('#areas-subareas').val() == 'Index' ? '' : ' class="ps-3"') + '>' + i.name + '</td>' +
      '<td>' + i.value + '</td>' +
      ((!isAdmin && !isEnisa && ms_published) ? '<td>' + i.country_value + '</td>' : '') +
      '</tr>'
    );
  });

  var chartDom = document.getElementById('map');
  var option;
  mapChart = echarts.init(chartDom);
  mapChart.showLoading();

  $.get('/js/index/EUN.json', function (worldJson) {
    mapChart.hideLoading();
    echarts.registerMap('EUN', worldJson, {});

    option = {
      tooltip: {
        trigger: 'item',
        showDelay: 0,
        transitionDuration: 0.2,
        formatter: function (params) {
          if (params.data != undefined && params.data.children.length > 0) {
            let tooltip = '';
            tooltip = '<font size="+1"><b>' + (params.name == 'N. Cyprus' ? 'Cyprus' : params.name) + '</b></font>' + '<br />';
            tooltip += '<b>' + ($('#areas-subareas').val() == 'Index' ? 'Aggregated Index' : selectedNode.trim()) + ':</b> ' + params.data.value + '<br />';
            tooltip += '<div style="margin: 7px 0px 7px 0px; border-top: 1px dashed;"></div>';

            $.each(params.data.children, function (idx, i) {
              tooltip += '<b>' + i['name'] + ':</b> ' + i['value'] + '<br />';
            });
            return tooltip;
          }
          return '';
        }
      },
      visualMap: {
        type: 'piecewise',
        show: (ms_published) ? true : false,
        top: 'top',
        left: 'left',
        splitNumber: 5,
        pieces: [
          { min: 0, max: 19.999 },
          { min: 20.0, max: 39.999 },
          { min: 40.0, max: 59.999 },
          { min: 60.0, max: 79.999 },
          { min: 80.0, max: 100 }
        ],
        inRange: {
          color: (ms_published) ? [
            '#f5f7fd',
            '#d6dff6',
            '#8ea7e6',
            '#3a65d3',
            '#254AA5'
          ] : []
        },
        borderColor: '#141414',
        borderWidth: 0.5,
        borderRadius: 2,
        backgroundColor: '#ffffff'
      },
      toolbox: {
        show: (ms_published) ? true : false,
        left: 'right',
        top: 'top',
        feature: {
          saveAsImage: {
            name: "Map",
            emphasis: {
              iconStyle: {
                color: '#141414',
                textAlign: 'right'
              }
            },
            icon: "image://data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M20.75 20.75C20.75 20.0625 20.1875 19.5 19.5 19.5L4.5 19.5C3.8125 19.5 3.25 20.0625 3.25 20.75C3.25 21.4375 3.8125 22 4.5 22L19.5 22C20.1875 22 20.75 21.4375 20.75 20.75ZM17.7375 9.5L15.75 9.5L15.75 3.25C15.75 2.5625 15.1875 2 14.5 2L9.5 2C8.8125 2 8.25 2.5625 8.25 3.25L8.25 9.5L6.2625 9.5C5.15 9.5 4.5875 10.85 5.375 11.6375L11.1125 17.375C11.2281 17.4909 11.3655 17.5828 11.5167 17.6455C11.6679 17.7083 11.83 17.7406 11.9938 17.7406C12.1575 17.7406 12.3196 17.7083 12.4708 17.6455C12.622 17.5828 12.7594 17.4909 12.875 17.375L18.6125 11.6375C19.4 10.85 18.85 9.5 17.7375 9.5V9.5Z' fill='black'/%3E%3C/svg%3E"
          }
        }
      },
      series: [
        {
          "name": "Cybersecurity Index",
          zoom: 6.9,
          center: [2.97, 52.71],
          type: 'map',
          roam: false,
          map: 'EUN',
          silent: (ms_published) ? false : true,
          label: {
            show: true,
            borderColor: '#141414',
            borderWidth: 0.5,
            borderRadius: 2,
            fontWeight: 350,
            fontSize: 10,
            backgroundColor: '#ffffff',
            padding: 2,
            formatter: function (data) {
              if (data.value) {
                return data.name;
              }
              return '';
            }
          },
          select: {
            disabled: true
          },
          emphasis: {
            label: {
              show: true,
              formatter: function (data) {
                if (data.value) {
                  return data.name;
                }
                return '';
              }
            },
            itemStyle: {
              areaColor: '#141414'
            }
          },
          data: mapData
        }
      ]
    };
    if (!isAdmin && !isEnisa) {
      option.visualMap.show = false;
    }
    option && mapChart.setOption(option, true);
  });

  mapChart.on('selectchanged', function (params) {
    if (params.fromAction == "select") {
      let selectedId = params.selected[0].dataIndex[0];
      // State if legend is selected.     
      if (mapData[selectedId] && mapData[selectedId].children.length > 0) {
        let country = (mapData[selectedId].name == 'N. Cyprus') ? mapData[cyprusIdx].selection_name : mapData[selectedId].selection_name;
        $("#tab-select #sunburst-tab").trigger('click');
        $("#sunburst-form-country-0").val(country);
        drawSunburst();
        $("#sunburst-form-country-0").select2("destroy");
        $("#sunburst-form-country-0").select2();
      }
    }
  });

  mapChart.on('mouseover', function (params) {
    if (params.name == 'Cyprus') {
      mapChart.dispatchAction({
        type: 'highlight',
        dataIndex: northernCyprusIdx
      });
    }
    if (params.name == 'N. Cyprus') {
      mapChart.dispatchAction({
        type: 'highlight',
        dataIndex: cyprusIdx
      });
    }
  });

  mapChart.on('mouseout', function (params) {
    if (params.name == 'Cyprus') {
      mapChart.dispatchAction({
        type: 'downplay',
        dataIndex: northernCyprusIdx
      });
    }
    if (params.name == 'N. Cyprus') {
      mapChart.dispatchAction({
        type: 'downplay',
        dataIndex: cyprusIdx
      });
    }
  });
}

function perc2color(perc) {
  var r, g, b = 0;
  if (perc < 50) {
    r = 255;
    g = Math.round(5.1 * perc);
  }
  else {
    g = 255;
    r = Math.round(510 - 5.10 * perc);
  }
  var h = r * 0x10000 + g * 0x100 + b * 0x1;
  return '#' + ('000000' + h.toString(16)).slice(-6);
}

$(document).on("change", "#sunburst-form-country-0", function () {
  drawSunburst();
});

/* ====== Export Button Dropdown ====== */

/*=====Multiple Button=======*/
$(document).ready(function () {
  $('.multiple-button').click(function () {
    $('.multiple-button').toggleClass('active').toggleClass('rotated');
  });
});

$(document).mouseup(function (e) {
  var container = $(".multiple-button");

  // if the target of the click isn't the container nor a descendant of the container
  if (!container.is(e.target) && container.has(e.target).length === 0) {
    container.removeClass('active').removeClass('rotated');
  }
});

/*====== Select2.js ======*/
$(document).ready(function () {
  $('#form-country-0').select2();
  $('#sunburst-form-country-0').select2();
  $('#areas-subareas').select2({
    templateResult: formatItem
  });

  function formatItem(state) {
    if (!state.id) { return state.text; }
    var $state = $(
      '<span class="' + state.element.className + '">' + state.text + '</span>'
    );
    return $state;
  }
});

$('#areas-subareas').on('change', function () {
  $('.loader').fadeIn();

  selectedNode = $('#areas-subareas :selected').text();
  
  getMapData();
})

function getMapData() {
  $.ajax({
    url: '/index/configurations/get/' + $('#areas-subareas').val() + '/' + getIndexYear()
  }).done(function (data) {
    mapData = data.mapData;

    if (mapData) 
    {
      setCountriesIdx();
      drawMap();
    }

    $('.loader').fadeOut();
  });
}