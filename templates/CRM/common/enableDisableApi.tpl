{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
  CRM.$(function($) {
    var $row, info, enabled, fieldLabel;

    function successMsg() {
      {/literal} {* client-side variable substitutions in smarty are AWKWARD! *}
      var msg = enabled ? '{ts escape="js" 1="<em>%1</em>"}%1 Disabled{/ts}' : '{ts escape="js" 1="<em>%1</em>"}%1 Enabled{/ts}'{literal};
      return ts(msg, {1: fieldLabel});
    }

    function refresh() {
      CRM.refreshParent($row);
    }

    function save() {
      $row.closest('table').block();
      CRM.api3(info.entity, 'setvalue', {id: info.id, field: 'is_active', value: enabled ? 0 : 1}, {success: successMsg}).done(refresh);
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
        CRM.confirm({{/literal}
          message: '<div class="crm-loading-element">{ts escape="js"}Loading{/ts}...</div>',
          {* client-side variable substitutions in smarty are AWKWARD! *}
          title: ts('{ts escape="js" 1='%1'}Disable %1{/ts}{literal}', {1: fieldLabel}),
          width: 300,
          options: null,
          open: confirmation
        });
      } else {
        save();
      }
      return false;
    }

    // Because this is an inline script it may get added to the document more than once, so remove handler before adding
    $('body')
      .off('.crmEnableDisable')
      .on('click.crmEnableDisable', '.action-item.crm-enable-disable', enableDisable);
  });
</script>
{/literal}
