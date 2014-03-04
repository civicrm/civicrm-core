{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* handle common enable/disable actions *}
{literal}
<script type="text/javascript">
  cj(function($) {
    var $row, $table, info, enabled, fieldLabel;

    function refresh() {
      // Call native refresh method on ajax datatables
      if ($.fn.DataTable.fnIsDataTable($table[0]) && $table.dataTable().fnSettings().sAjaxSource) {
        $.each($.fn.dataTable.fnTables(), function() {
          $(this).dataTable().fnSettings().sAjaxSource && $(this).unblock().dataTable().fnDraw();
        });
      }
      // Otherwise refresh the content area using crmSnippet
      else {
        $row.closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet().crmSnippet('refresh');
      }
      {/literal} {* client-side variable substitutions in smarty are AWKWARD! *}
      var msg = enabled ? '{ts escape="js" 1="<em>%1</em>"}%1 Disabled{/ts}' : '{ts escape="js" 1="<em>%1</em>"}%1 Enabled{/ts}'{literal};
      CRM.alert('', ts(msg, {1: fieldLabel}), 'success');
    }

    function save() {
      $table = $row.closest('table');
      $table.block();
      CRM.api(info.entity, 'setvalue', {id: info.id, field: 'is_active', value: enabled ? 0 : 1}, {success: refresh});
      if (enabled) {
        $(this).dialog('close');
      }
    }

    function confirmation() {
      var conf = $(this);
      $.getJSON(CRM.url('civicrm/ajax/statusmsg', {entity: info.entity, id: info.id}), function(response) {
        conf.html(response.content);
        if (!response.illegal) {
          conf.dialog('option', 'buttons', [
            {text: {/literal}'{ts escape="js"}Disable{/ts}'{literal}, click: save},
            {text: {/literal}'{ts escape="js"}Cancel{/ts}'{literal}, click: function() {$(this).dialog('close');}}
          ]);
        }
      });
    }

    function enableDisable() {
      $row = $(this).closest('.crm-entity');
      info = $(this).crmEditableEntity();
      fieldLabel = info.label || info.title || info.name || {/literal}'{ts escape="js"}Record{/ts}'{literal};
      enabled = !$row.hasClass('disabled');
      if (enabled) {
        CRM.confirm({}, {{/literal}
          message: '<div class="crm-loading-element">{ts escape="js"}Loading{/ts}...</div>',
          {* client-side variable substitutions in smarty are AWKWARD! *}
          title: ts('{ts escape="js" 1='%1'}Disable %1{/ts}{literal}', {1: fieldLabel}),
          width: 300,
          open: confirmation
        });
      } else {
        save();
      }
      return false;
    }

    // Because this is an inline script it may get added to the document more than once, so remove handler before adding
    $('body')
      .off('click', '.action-item.crm-enable-disable')
      .on('click', '.action-item.crm-enable-disable', enableDisable);
  });
</script>
{/literal}
