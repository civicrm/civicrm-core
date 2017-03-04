// https://civicrm.org/licensing
(function($, _) {
  "use strict";
  /* jshint validthis: true */

  $.fn.crmAjaxTable = function() {

    // Strip the ids from ajax urls to make pageLength storage more generic
    function simplifyUrl(ajax) {
      // Datatables ajax prop could be a url string or an object containing the url
      var url = typeof ajax === 'object' ? ajax.url : ajax;
      return typeof url === 'string' ? url.replace(/[&?]\w*id=\d+/g, '') : null;
    }

    return $(this).each(function() {
      // Recall pageLength for this table
      var url = simplifyUrl($(this).data('ajax'));
      if (url && window.localStorage && localStorage['dataTablePageLength:' + url]) {
        $(this).data('pageLength', localStorage['dataTablePageLength:' + url]);
      }
      // Declare the defaults for DataTables
      var defaults = {
        "processing": true,
        "serverSide": true,
        "order": [],
        "dom": '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
        "pageLength": 25,
        "pagingType": "full_numbers",
        "drawCallback": function(settings) {
          //Add data attributes to cells
          $('thead th', settings.nTable).each( function( index ) {
            $.each(this.attributes, function() {
              if(this.name.match("^cell-")) {
                var cellAttr = this.name.substring(5);
                var cellValue = this.value;
                $('tbody tr', settings.nTable).each( function() {
                  $('td:eq('+ index +')', this).attr( cellAttr, cellValue );
                });
              }
            });
          });
          //Reload table after draw
          $(settings.nTable).trigger('crmLoad');
        }
      };
      //Include any table specific data
      var settings = $.extend(true, defaults, $(this).data('table'));
      // Remember pageLength
      $(this).on('length.dt', function(e, settings, len) {
        if (settings.ajax && window.localStorage) {
          localStorage['dataTablePageLength:' + simplifyUrl(settings.ajax)] = len;
        }
      });
      //Make the DataTables call
      $(this).DataTable(settings);
    });
  };

})(CRM.$, CRM._);
