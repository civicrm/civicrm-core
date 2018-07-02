{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">

CRM.$(function($) {
  // The set of options we can use to initialize jQuery.dashboard().
  var options = {

    widgetsByColumn: {/literal}{$contactDashlets|@json_encode}{literal},

    // These define the urls and data objects used for all of the ajax requests to the server.
    ajaxCallbacks: {

      // jQuery.dashboard() POSTs the widget-to-column settings here.
      // The 'columns' property of data is reserved for the widget-to-columns settings:
      //    An array (keyed by zero-indexed column ID), of arrays (keyed by widget ID)
      //    of ints; 1 if the widget is minimized.  0 if not.
      saveColumns: {
        url: {/literal}'{crmURL p='civicrm/ajax/dashboard' h=0 }'{literal},
        data: {
          // columns: array(0 => array(widgetId => isMinimized, ...), ...),
          op: 'save_columns', key: {/literal}"{crmKey name='civicrm/ajax/dashboard'}"{literal}
        }
      },

      // jQuery.dashboard() GETs a widget's settings object and POST's a users submitted
      // settings back to the server.  The return, in both cases, is an associative
      // array with the new settings markup and other info:
      //
      // Required properties:
      //  * markup: HTML string.  The inner HTML of the settings form.  jQuery.dashboard()
      //    provides the Save and Cancel buttons and wrapping <form> element.  Can include
      //    <input>s of any standard type and <select>s, nested in <div>s etc.
      //
      // Server-side executable script callbacks (See documentation for
      // ajaxCallbacks.getWidgets):
      //  * initScript:  Called when widget settings are initialising.
      //  * script:  Called when switching into settings mode.  Executed every time
      //    the widget goes into settings-edit mode.
      //
      // The 'id' property of data is reserved for the widget ID.
      // The 'settings' property of data is reserved for the user-submitted settings.
      //    An array (keyed by the name="" attributes of <input>s), of <input> values.
      widgetSettings: {
        url: {/literal}'{crmURL p='civicrm/ajax/dashboard' h=0 }'{literal},
        data: {
          // id: widgetId,
          // settings: array(name => value, ...),
          op: 'widget_settings', key: {/literal}"{crmKey name='civicrm/ajax/dashboard'}"{literal}
        }
      }
    }

  };

  var dashboard = $('#civicrm-dashboard')
    .on('mouseover', '.widget-header', function() {
      $(this).closest('.widget-wrapper').addClass('db-hover-handle');
    })
    .on('mouseout', '.widget-header', function() {
      $(this).closest('.widget-wrapper').removeClass('db-hover-handle');
    })
    .dashboard(options);


  $('.crm-hover-button.show-refresh').click(function(e) {
    e.preventDefault();
    $.each(dashboard.widgets, function(id, widget) {
      widget.reloadContent();
    });
  });

});

</script>
{/literal}
