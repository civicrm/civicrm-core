{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{include file="CRM/Admin/Form/Generic.tpl"}

{if !empty($form.contact_edit_options.html)}
  {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        function getSorting(e, ui) {
          var params = [];
          var y = 0;
          var items = $("#contactEditBlocks li");
          if (items.length > 0) {
            for (var y = 0; y < items.length; y++) {
              var idState = items[y].id.split('-');
              params[y + 1] = idState[1];
            }
          }

          items = $("#contactEditOptions li");
          if (items.length > 0) {
            for (var x = 0; x < items.length; x++) {
              var idState = items[x].id.split('-');
              params[x + y + 1] = idState[1];
            }
          }
          $('#contact_edit_preferences').val(params.toString());
        }

        // show/hide activity types based on checkbox value
        $('.crm-setting-form-block-do_not_notify_assignees_for').toggle($('#activity_assignee_notification_activity_assignee_notification').is(":checked"));
        $('#activity_assignee_notification_activity_assignee_notification').click(function() {
          $('.crm-setting-form-block-do_not_notify_assignees_for').toggle($(this).is(":checked"));
        });

        var invoicesKey = '{/literal}{$invoicesKey}{literal}';
        var invoicing = '{/literal}{$invoicing}{literal}';
        if (!invoicing) {
          $('#user_dashboard_options_' + invoicesKey).attr("disabled", true);
        }

        $("#contactEditBlocks, #contactEditOptions").on('sortupdate', getSorting);
      });
    </script>
  {/literal}
{/if}
