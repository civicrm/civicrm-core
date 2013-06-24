// http://civicrm.org/licensing
//http://wiki.civicrm.org/confluence/display/CRMDOC43/Javascript+Reference
/*jslint indent: 2 */
/*global CRM, cj */
cj(function ($) {
  'use strict';

  function getChart() {
    var year, charttype, date, currentYear, chartUrl;
    year        = $('#select_year').val();
    charttype   = $('#chart_type').val();
    date        = new Date();
    currentYear = date.getFullYear();
    if (!charttype) {
      charttype = 'bvg';
    }
    if (!year) {
      year = currentYear;
    }
    chartUrl = CRM.url("civicrm/ajax/chart", {snippet : 4});
    chartUrl    += "&year=" + year + "&type=" + charttype;
    $.ajax({
      url     : chartUrl,
      success  : function(html) {
        $("#chartData").html(html);
      }
    });
  }

  function buildTabularView() {
    var tableUrl = CRM.url("civicrm/contribute/ajax/tableview", {showtable: 1, snippet: 4});
    $.ajax({
      url      : tableUrl,
      success  : function(html) {
        $("#tableData").html(html);
      }
    });
  }

  getChart();
  $('#chart_view').click(function() {
    if ($('#chart_view').hasClass('ui-state-default')) {
      $('#chart_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
      $('#table_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
      getChart();
      $('#tableData').children().html('');
    }
  });
  $('#table_view').click(function() {
    if ($('#table_view').hasClass('ui-state-default')) {
      $('#table_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
      $('#chart_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
      buildTabularView();
      $('#chartData').children().html('');
    }
  });
});

