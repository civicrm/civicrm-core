// http://civicrm.org/licensing
/*jslint indent: 2 */
/*global CRM, cj */
(function($) {
  'use strict';

  var load = {
    chart_view: function()  {
      var chartUrl = CRM.url("civicrm/ajax/chart", {
        'snippet': 4,
        'year': $('#select_year').val() || new Date().getFullYear(),
        'type': $('#chart_type').val() || 'bvg'
      });
      $("#chartData").load(chartUrl, function() {
        $("select", "#chartData").change(load.chart_view);
      });
    },
    table_view: function() {
      var tableUrl = CRM.url("civicrm/contribute/ajax/tableview", {showtable: 1, snippet: 4});
      $("#chartData").load(tableUrl);
    }
  };

  function refresh() {
    $('#chart_view, #table_view').click(function () {
      if ($(this).hasClass('ui-state-default')) {
        $('.ui-tabs-selected', '#mainTabContainer').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
        $(this).removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
        load[this.id]();
      }
    });

    // Initialize chart or table based on url hash
    if (window.location.hash === '#table_layout') {
      $('#table_view').click();
    }
    else {
      load.chart_view();
    }
  }

  $(function () {
    $('#crm-main-content-wrapper').on('crmLoad', function (e) {
      if ($(e.target).is(this)) {
        refresh();
      }
    });
    refresh();
  });
})(CRM.$);

