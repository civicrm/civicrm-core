{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* handle common enable/disable actions *}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $a, $row, info, enabled, fieldLabel;

    function successMsg() {
      {/literal} {* client-side variable substitutions in smarty are AWKWARD! *}
      var msg = enabled ? '{ts escape="js" 1="%1"}%1 Disabled{/ts}' : '{ts escape="js" 1="%1"}%1 Enabled{/ts}'{literal};
      return ts(msg, {1: fieldLabel});
    }

    function refresh() {
      // the opposite of the current status based on row class
      var newStatus = $row.hasClass('disabled');
      $a.trigger('crmPopupFormSuccess', {
        'entity': info.entity,
        'id': info.id,
        'enabled': newStatus
      });
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
    }

    // Because this is an inline script it may get added to the document more than once, so remove handler before adding
    $('body')
      .off('.crmEnableDisable')
      .on('click.crmEnableDisable', '.action-item.crm-enable-disable', function(e) {
        e.preventDefault();
        $a = $(this);
        CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmEditable.js').done(enableDisable);
      });
  });
</script>
{/literal}
