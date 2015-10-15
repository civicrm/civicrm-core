{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
    var $a, $row, info, enabled, fieldLabel;

    function successMsg() {
      {/literal} {* client-side variable substitutions in smarty are AWKWARD! *}
      var msg = enabled ? '{ts escape="js" 1="<em>%1</em>"}%1 Disabled{/ts}' : '{ts escape="js" 1="<em>%1</em>"}%1 Enabled{/ts}'{literal};
      return ts(msg, {1: fieldLabel});
    }

    function refresh() {
      $a.trigger('crmPopupFormSuccess');
      CRM.refreshParent($row);
    }

    function save() {
      $row.closest('table').block();
      var params = {id: info.id};
      if (info.action == 'setvalue') {
        params.field = 'is_active';
        params.value = enabled ? 0 : 1;
      } else {
        params.is_active = enabled ? 0 : 1;
      }
      CRM.api3(info.entity, info.action, params, {success: successMsg}).done(refresh);
    }

    function checkResponse(e, response) {
      if (response.illegal) {
        $(this).dialog('option', 'buttons', [
          {text: {/literal}'{ts escape="js"}Close{/ts}'{literal}, click: function() {$(this).dialog('close');}, icons: {primary: 'fa-times'}}
        ]);
      }
    }

    function enableDisable() {
      $a = $(this);
      $row = $a.closest('.crm-entity');
      info = $a.crmEditableEntity();
      fieldLabel = info.label || info.title || info.display_name || info.name || {/literal}'{ts escape="js"}Record{/ts}'{literal};
      enabled = !$row.hasClass('disabled');
      if (enabled) {
        CRM.confirm({
          url: CRM.url('civicrm/ajax/statusmsg', {entity: info.entity, id: info.id}),
          title: ts('{/literal}{ts escape="js" 1='%1'}Disable %1{/ts}{literal}', {1: fieldLabel}),
          options: {{/literal}yes: '{ts escape="js"}Yes{/ts}', no: '{ts escape="js"}No{/ts}'{literal}},
          width: 300,
          height: 'auto'
        })
          .on('crmLoad', checkResponse)
          .on('crmConfirm:yes', save);
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
