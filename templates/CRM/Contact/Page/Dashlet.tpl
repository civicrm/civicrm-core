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
    <div class="crm-submit-buttons">{crmButton p="civicrm/dashboard" q="reset=1" icon="check"}{ts}Done{/ts}{/crmButton}</div>
    <div id="help" style="padding: 1em;">
        {ts}Available dashboard elements - dashlets - are displayed in the dark gray top bar. Drag and drop dashlets onto the left or right columns below to add them to your dashboard. Changes are automatically saved. Click 'Done' to return to the normal dashboard view.{/ts}
        {help id="id-dash_configure" file="CRM/Contact/Page/Dashboard.hlp" admin=$admin}
    </div><br/>
    <div class="dashlets-header">{ts}Available Dashlets{/ts}</div>
    <div id="available-dashlets" class="dash-column">
        {foreach from=$availableDashlets item=row key=dashID}
      <div class="portlet">
        <div class="portlet-header" id="{$dashID}">{$row.label}{if $admin and !$row.is_reserved}&nbsp;<a class="crm-i fa-times delete-dashlet"></a>{/if}</div>
      </div>
        {/foreach}
    </div>
    <br/>
    <div class="clear"></div>
    <div id="dashlets-header-col-0" class="dashlets-header">{ts}Left Column{/ts}</div>
    <div id="dashlets-header-col-1" class="dashlets-header">{ts}Right Column{/ts}</div>
    <div id="existing-dashlets-col-0" class="dash-column">
        {foreach from=$contactDashlets.0 item=row key=dashID}
      <div class="portlet">
        <div class="portlet-header" id="{$dashID}">{$row.label}{if $admin and !$row.is_reserved}&nbsp;<a class="crm-i fa-times delete-dashlet"></a>{/if}</div>
      </div>
        {/foreach}
    </div>

    <div id="existing-dashlets-col-1" class="dash-column">
        {foreach from=$contactDashlets.1 item=row key=dashID}
      <div class="portlet">
        <div class="portlet-header" id="{$dashID}">{$row.label}{if $admin and !$row.is_reserved}&nbsp;<a class="crm-i fa-times delete-dashlet"></a>{/if}</div>
      </div>
        {/foreach}
    </div>

    <div class="clear"></div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
      var currentReSortEvent;
    $(".dash-column").sortable({
      connectWith: '.dash-column',
      update: saveSorting
    });

    $(".portlet").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
      .find(".portlet-header")
        .addClass("ui-widget-header ui-corner-all")
        .end()
      .find(".portlet-content");

    $(".dash-column").disableSelection();

    function saveSorting(e, ui) {
            // this is to prevent double post call
        if (!currentReSortEvent || e.originalEvent != currentReSortEvent) {
                currentReSortEvent = e.originalEvent;

                // Build a list of params to post to the server.
                var params = {};

                // post each columns
                dashletColumns = Array();

                // build post params
                $('div[id^=existing-dashlets-col-]').each( function( i ) {
                    $(this).find('.portlet-header').each( function( j ) {
                        var elementID = this.id;
                        var idState = elementID.split('-');
                        params['columns[' + i + '][' + idState[0] + ']'] = idState[1];
                    });
                });

                // post to server
                var postUrl = {/literal}"{crmURL p='civicrm/ajax/dashboard' h=0 }"{literal};
                params['op'] = 'save_columns';
                params['key'] = {/literal}"{crmKey name='civicrm/ajax/dashboard'}"{literal};
                CRM.status({}, $.post(postUrl, params));
            }
        }

        $('.delete-dashlet').click( function( ) {
          var $dashlet = $(this).closest('.portlet-header');
          CRM.confirm({
            title: {/literal}'{ts escape="js"}Remove Permanently?{/ts}'{literal},
            message: {/literal}'{ts escape="js"}Do you want to remove this dashlet as an "Available Dashlet", AND delete it from all user dashboards?{/ts}'{literal}
          })
            .on('crmConfirm:yes', function() {
              var dashletID = $dashlet.attr('id');
              var idState = dashletID.split('-');

              // Build a list of params to post to the server.
              var params = {dashlet_id: idState[0]};

              // delete dashlet
              var postUrl = {/literal}"{crmURL p='civicrm/ajax/dashboard' h=0 }"{literal};
              params['op'] = 'delete_dashlet';
              params['key'] = {/literal}"{crmKey name='civicrm/ajax/dashboard'}"{literal};
              CRM.status({}, $.post(postUrl, params));
              $dashlet.parent().fadeOut('fast', function() {
                $(this).remove();
              });
            });
        });
  });
</script>
{/literal}
