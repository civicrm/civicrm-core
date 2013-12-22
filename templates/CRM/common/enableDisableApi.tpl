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
    var $row, $table, entity, id, enabled, fieldLabel;

    function refresh() {
      if (false && $.fn.DataTable.fnIsDataTable($table[0])) { // fixme why doesn't this work?
        $table.dataTable().fnDraw();
      } else {
        // Refresh an existing ajax container or create a new one
        $row.closest('.crm-ajax-container, #crm-main-content-wrapper').crmSnippet().crmSnippet('refresh');
      }
      var msg = enabled ? {/literal}'{ts escape="js" 1="<em>%1</em>"}%1 Disabled{/ts}' : '{ts escape="js" 1="<em>%1</em>"}%1 Enabled{/ts}'{literal};
      CRM.alert('', ts(msg, fieldLabel), 'success');
    }

    function save() {
      $table = $row.closest('table');
      $table.block();
      CRM.api(entity, 'setvalue', {id: id, field: 'is_active', value: enabled ? 0 : 1}, {success: refresh});
      if (enabled) {
        $(this).dialog('close');
      }
    }

    function confirmation() {
      var conf = $(this);
      $.getJSON(CRM.url('civicrm/ajax/statusmsg', {entity: entity, id: id}), function(response) {
        conf.html(response.content);
        if (!response.illegal) {
          conf.dialog('option', 'buttons', [
            {text: {/literal}'{ts escape="js"}Disable{/ts}'{literal}, click: save},
            {text: {/literal}'{ts escape="js"}Cancel{/ts}'{literal}, click: function() {$(this).dialog('close');}}
          ]);
        }
      });
    }

    function getLabel() {
      var label = {/literal}'{ts escape="js"}Record{/ts}'{literal};
      var labelField = $('.crmf-label, .crmf-title, [data-field=label], [data-field=title]', $row);
      if (labelField.length) {
        label = labelField.first().text();
      }
      // Format as object the way ts() wants it
      fieldLabel = {1: label};
    }

    function enableDisable() {
      var $a = $(this);
      $row = $a.closest('.crm-entity');
      getLabel();
      // FIXME: abstract and reuse code from $.crmEditable for fetching entity/id instead of reinventing it here
      entity = $row.data('entity');
      id = $row.data('id');
      if (!entity || !id) {
        entity = $row[0].id.split('-')[0];
        id = $row[0].id.split('-')[1];
      }
      enabled = !$row.hasClass('disabled');
      if (enabled) {
        CRM.confirm({}, {{/literal}
          {* client-side variable substitutions in smarty are AWKWARD! *}
          title: ts('{ts escape="js" 1='%1'}Disable %1{/ts}', fieldLabel),
          message: '<div class="crm-loading-element">{ts escape="js"}Loading{/ts}...</div>',
          width: 300,
          open: confirmation
        {literal}});
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
