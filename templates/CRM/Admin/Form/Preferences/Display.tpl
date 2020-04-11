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
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout">
    <tr class="crm-preferences-display-form-block-contact_view_options">
      <td class="label">{$form.contact_view_options.label}</td>
      <td><ul class="crm-checkbox-list"><li>{$form.contact_view_options.html}</li></ul></td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {capture assign=crmURL}{crmURL p='civicrm/admin/setting/component' q='action=add&reset=1'}{/capture}
        {ts 1=$crmURL}Select the <strong>tabs</strong>
          that should be displayed when viewing a contact record. EXAMPLE: If your organization does not keep track of
          'Relationships', then un-check this option to simplify the screen display. Tabs for Contributions, Pledges,
          Memberships, Events, Grants and Cases are also hidden if the corresponding component is not enabled. Go to
          <a href="%1">Administer > System Settings > Enable Components</a>
          to modify the components which are available for your site.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-contact_smart_group_display">
      <td class="label">{$form.contact_smart_group_display.label}</td>
      <td>{$form.contact_smart_group_display.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {$settings_fields.contact_smart_group_display.description}
      </td>
    </tr>
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
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}Select the sections that should be included when adding or editing a contact record. EXAMPLE: If your organization does not record Gender and Birth Date for individuals, then simplify the form by un-checking this option. Drag interface allows you to change the order of the panes displayed on contact add/edit screen.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-advanced_search_options">
      <td class="label">{$form.advanced_search_options.label}</td>
      <td><ul class="crm-checkbox-list"><li>{$form.advanced_search_options.html}</li></ul></td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}Select the sections that should be included in the Basic and Advanced Search forms. EXAMPLE: If you don't track Relationships - then you do not need this section included in the advanced search form. Simplify the form by un-checking this option.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-contact_ajax_check_similar">
      <td class="label">{$form.contact_ajax_check_similar.label}</td>
      <td>{$form.contact_ajax_check_similar.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      {capture assign=dedupeRules}href="{crmURL p='civicrm/contact/deduperules' q='reset=1'}"{/capture}
      <td class="description">{ts 1=$dedupeRules}When enabled, checks for possible matches on the "New Contact" form using the Supervised <a %1>matching rule specified in your system</a>.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-activity_assignee_notification">
      <td class="label"></td>
      <td>{$form.activity_assignee_notification.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}When enabled, contacts who are assigned activities will automatically receive an email notification with a copy of the activity.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-activity_types">
      <td class="label">{$form.do_not_notify_assignees_for.label}</td>
      <td>{$form.do_not_notify_assignees_for.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-activity_types">
      <td>&nbsp;</td>
      <td class="description">
        {ts}These activity types will be excluded from automated email notifications to assignees.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-activity_assignee_notification_ics">
      <td class="label"></td>
      <td>{$form.activity_assignee_notification_ics.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{ts}When enabled, the assignee notification sent out above will also include an ical meeting invite.{/ts}
      </td>
    </tr>

    <tr class="crm-preferences-display-form-block-preserve_activity_tab_filter">
      <td class="label"></td>
      <td>{$form.preserve_activity_tab_filter.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{$settings_fields.preserve_activity_tab_filter.description}</td>
    </tr>

    <tr class="crm-preferences-display-form-block-user_dashboard_options">
      <td class="label">{$form.user_dashboard_options.label}</td>
      <td>
        <ul class="crm-checkbox-list"><li>
          {$form.user_dashboard_options.html}
          <span style="position: absolute; right: 5px; bottom: 3px;"> {help id="id-invoices_id"}</span>
        </li></ul>
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {$settings_fields.user_dashboard_options.description}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-editor_id">
      <td class="label">{$form.editor_id.label} {help id="editor_id"}</td>
      <td>
        {$form.editor_id.html}
        &nbsp;
        <span class="crm-button crm-icon-button" style="display:inline-block;vertical-align:middle;float:none!important;">
          <i class="crm-i fa-wrench" style="margin: 0 -18px 0 2px;"></i>
          {$form.ckeditor_config.html}
        </span>
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-ajaxPopupsEnabled">
      <td class="label">{$form.ajaxPopupsEnabled.label}</td>
      <td>{$form.ajaxPopupsEnabled.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}If you disable this option, the CiviCRM interface will be limited to traditional browsing. Opening a form will refresh the page rather than opening a popup dialog.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-display_name_format">
      <td class="label">{$form.display_name_format.label}</td>
      <td>{$form.display_name_format.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{$settings_fields.display_name_format.description}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-sort_name_format">
      <td class="label">{$form.sort_name_format.label}</td>
      <td>{$form.sort_name_format.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{$settings_fields.sort_name_format.description}</td>
    </tr>
    <tr class="crm-preferences-display-form-block_menubar_position">
      <td class="label">{$form.menubar_position.label}</td>
      <td>
        {$form.menubar_position.html}
        <div class="description">{ts}Default position for the CiviCRM menubar.{/ts}</div>
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block_menubar_color">
      <td class="label">{$form.menubar_color.label}</td>
      <td>
        {$form.menubar_color.html}
      </td>
    </tr>

    {if $config->userSystem->is_drupal EQ '1'}
      <tr class="crm-preferences-display-form-block-theme">
        <td class="label">{ts}Theme{/ts} {help id="theme"}</td>
        <td>{$form.theme_backend.html}</td>
      </tr>
    {else}
      <tr class="crm-preferences-display-form-block-theme_backend">
        <td class="label">{$form.theme_backend.label} {help id="theme_backend"}</td>
        <td>{$form.theme_backend.html}</td>
      </tr>
      <tr class="crm-preferences-display-form-block-theme_frontend">
        <td class="label">{$form.theme_frontend.label} {help id="theme_frontend"}</td>
        <td>{$form.theme_frontend.html}</td>
      </tr>
      {/if}
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $form.contact_edit_options.html}
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

        function showCKEditorConfig() {
          $('.crm-preferences-display-form-block-editor_id .crm-button').toggle($(this).val() == 'CKEditor');
        }
        $('select[name=editor_id]').each(showCKEditorConfig).change(showCKEditorConfig);
      });
    </script>
  {/literal}
{/if}
