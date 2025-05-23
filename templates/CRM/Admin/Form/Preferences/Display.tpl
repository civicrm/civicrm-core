{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for editing Site Preferences  *}
<div class="crm-block crm-form-block crm-preferences-display-form-block">
  <table class="form-layout">
    {foreach from=$settings_fields key="setting_name" item="fieldSpec"}
      {assign var=fieldName value=$fieldSpec.name}
      {* some exceptional cases use their own template *}
      {if $fieldSpec.name eq 'contact_view_options'}
        <tr class="crm-preferences-display-form-block-contact_view_options">
          <td class="label">{$form.contact_view_options.label}</td>
          <td>
            <ul class="crm-checkbox-list">
              <li>{$form.contact_view_options.html}</li>
            </ul>
            <div class="description">
              {capture assign=crmURL}{crmURL p='civicrm/admin/setting/component' q='action=add&reset=1'}{/capture}
              {ts 1=$crmURL}Select the <strong>tabs</strong>
              that should be displayed when viewing a contact record. EXAMPLE: If your organization does not keep track of
              'Relationships', then un-check this option to simplify the screen display. Tabs for Contributions, Pledges,
              Memberships, Events, Grants and Cases are also hidden if the corresponding component is not enabled. Go to
              <a href="%1">Administer > System Settings > Enable Components</a>
              to modify the components which are available for your site.{/ts}
            </div>
          </td>
        </tr>
      {elseif $fieldSpec.name eq 'contact_edit_options'}
        <tr class="crm-preferences-display-form-block-contact_edit_options">
          <td class="label">{$form.contact_edit_options.label}</td>
          <td>
            <table style="width:90%">
              <tr>
                <td style="width:30%">
                  <span class="label"><strong>{ts}Individual Name Fields{/ts}</strong></span>
                  <ul id="contactEditNameFields" class="crm-checkbox-list">
                    {foreach from=$nameFields item="title" key="opId"}
                      <li id="preference-{$opId}-contactedit">
                        {$form.contact_edit_options.$opId.html}
                      </li>
                    {/foreach}
                  </ul>
                </td>
                <td style="width:30%">
                  <span class="label"><strong>{ts}Contact Details{/ts}</strong></span>
                  <ul id="contactEditBlocks" class="crm-checkbox-list crm-sortable-list">
                    {foreach from=$contactBlocks item="title" key="opId"}
                      <li id="preference-{$opId}-contactedit">
                        {$form.contact_edit_options.$opId.html}
                      </li>
                    {/foreach}
                  </ul>
                </td>
                <td style="width:30%">
                  <span class="label"><strong>{ts}Other Panes{/ts}</strong></span>
                  <ul id="contactEditOptions"  class="crm-checkbox-list crm-sortable-list">
                    {foreach from=$editOptions item="title" key="opId"}
                      <li id="preference-{$opId}-contactedit">
                        {$form.contact_edit_options.$opId.html}
                      </li>
                    {/foreach}
                  </ul>
                </td>
              </tr>
            </table>
            <div class="description">
              {$fieldSpec.description}
            </div>
          </td>
        </tr>
      {elseif $fieldSpec.name eq 'contact_ajax_check_similar'}
        <tr class="crm-preferences-display-form-block-contact_ajax_check_similar">
          <td class="label">{$form.contact_ajax_check_similar.label}</td>
          <td>

            {$form.contact_ajax_check_similar.html}

            <div class="description">
              {capture assign=dedupeRules}href="{crmURL p='civicrm/contact/deduperules' q='reset=1'}"{/capture}
              {ts 1=$dedupeRules}When enabled, checks for possible matches on the "New Contact" form using the Supervised <a %1>matching rule specified in your system</a>.{/ts}
            </div>
          </td>
        </tr>
      {elseif $fieldSpec.name eq 'user_dashboard_options'}
        <tr class="crm-preferences-display-form-block-user_dashboard_options">
          <td class="label">{$form.user_dashboard_options.label}</td>
          <td>
            <ul class="crm-checkbox-list">
              <li>
                {$form.user_dashboard_options.html}
                <span style="position: absolute; right: 5px; bottom: 3px;"> {help id="id-invoices_id"}</span>
              </li>
            </ul>
            <div class="description">
              {$fieldSpec.description}
            </div>
          </td>
        </tr>
      {else}
        {include file="CRM/Admin/Form/Setting/SettingField.tpl"}
      {/if}
    {/foreach}
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
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
        $('.crm-preferences-display-form-activity_types').toggle($('#activity_assignee_notification_activity_assignee_notification').is(":checked"));
        $('#activity_assignee_notification_activity_assignee_notification').click(function() {
          $('.crm-preferences-display-form-activity_types').toggle($(this).is(":checked"));
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
