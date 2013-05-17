{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
{* this template is used for editing Site Preferences  *}
<div class="crm-block crm-form-block crm-preferences-display-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout">
    <tr class="crm-preferences-display-form-block-contact_view_options">
      <td class="label">{$form.contact_view_options.label}</td>
      <td>{$form.contact_view_options.html}</td>
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
        {ts}Controls display of the smart groups that a contact is part of in each contact's "Groups" tab.
          "Show on Demand" provides the best performance, and is recommended for most sites.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-contact_edit_options">
      <td class="label">{$form.contact_edit_options.label}</td>
      <td>
        <table style="width:80%">
          <tr>
            <td style="width:40%">
              <span class="label"><strong>{ts}Contact Details{/ts}</strong></span>
              <ul id="contactEditBlocks">
                {foreach from=$contactBlocks item="title" key="opId"}
                  <li id="preference-{$opId}-contactedit" class="ui-state-default ui-corner-all"
                      style="padding-left:1px;">
                    <span class='ui-icon ui-icon-arrowthick-2-n-s' style="float:left;"></span>
                    <span>{$form.contact_edit_options.$opId.html}</span>
                  </li>
                {/foreach}
              </ul>
            </td>
            <td>
              <span class="label"><strong>{ts}Other Panes{/ts}</strong></span>
              <ul id="contactEditOptions">
                {foreach from=$editOptions item="title" key="opId"}
                  <li id="preference-{$opId}-contactedit" class="ui-state-default ui-corner-all"
                      style="padding-left:1px;">
                    <span class='ui-icon ui-icon-arrowthick-2-n-s' style="float:left;"></span>
                    <span>{$form.contact_edit_options.$opId.html}</span>
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
        {ts}Select the sections that should be included when adding or editing a contact record.
          EXAMPLE: If your organization does not record Gender and Birth Date for individuals, then simplify the form
          by un-checking this option. Drag interface allows you to change the order of the panes displayed on contact
          add/edit screen.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-advanced_search_options">
      <td class="label">{$form.advanced_search_options.label}</td>
      <td>{$form.advanced_search_options.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td
        class="description">{ts}Select the sections that should be included in the Basic and Advanced Search forms.
          EXAMPLE: If you don't track Relationships - then you do not need this section included in the advanced search
          form. Simplify the form by un-checking this option.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-contact_ajax_check_similar">
      <td class="label"></td>
      <td>{$form.contact_ajax_check_similar.html} {$form.contact_ajax_check_similar.label}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{ts}When enabled, checks for contacts with similar names as the user types values into
          the contact form name fields.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-activity_assignee_notification">
      <td class="label"></td>
      <td>{$form.activity_assignee_notification.html} {$form.activity_assignee_notification.label}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}When enabled, contacts who are assigned activities will automatically receive an email notification
          with a copy of the activity.{/ts}
      </td>
    </tr>

    <tr class="crm-preferences-display-form-block-activity_assignee_notification_ics">
      <td class="label"></td>
      <td>{$form.activity_assignee_notification_ics.html} {$form.activity_assignee_notification_ics.label}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{ts}When enabled, the assignee notification sent out above will also include
          an ical meeting invite.{/ts}
      </td>
    </tr>

    <tr class="crm-preferences-display-form-block-user_dashboard_options">
      <td class="label">{$form.user_dashboard_options.label}</td>
      <td>{$form.user_dashboard_options.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}Select the sections that should be included in the Contact Dashboard. EXAMPLE: If you don't want
          constituents to view their own contribution history, un-check that option.{/ts}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-editor_id">
      <td class="label">{$form.editor_id.label}</td>
      <td>{$form.editor_id.html}</td>
    </tr>
    {if $form.wysiwyg_input_format.html}
      <tr id="crm-preferences-display-form-block-wysiwyg_input_format" style="display:none;">
        <td class="label">{$form.wysiwyg_input_format.label}</td>
        <td>
          {$form.wysiwyg_input_format.html}{literal}
            <script type="text/javascript">cj(document).ready(function () {
                if (cj('#editor_id').val() == 4) {
                  cj('#crm-preferences-display-form-block-wysiwyg_input_format').show();
                }
              });</script>
          {/literal}
          <br/>
          <span class="description">
            {ts}You will need to enable and configure several modules if you want to allow users to upload images
              while using a Drupal Default Editor.{/ts}
            {docURL page="Configuring CiviCRM to Use the Default Drupal Editor" resource="wiki"}
          </span>
        </td>
      </tr>
    {/if}
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">
        {ts}Select the HTML WYSIWYG Editor provided for fields that allow HTML formatting. Select 'Textarea'
          if you don't want to provide a WYSIWYG Editor (users will type text and / or HTML code into plain text
          fields).{/ts} {help id="id-editor_id"}
      </td>
    </tr>
    <tr class="crm-preferences-display-form-block-display_name_format">
      <td class="label">{$form.display_name_format.label}</td>
      <td>{$form.display_name_format.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{ts}Display name format for individual contact display names.{/ts}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-sort_name_format">
      <td class="label">{$form.sort_name_format.label}</td>
      <td>{$form.sort_name_format.html}</td>
    </tr>
    <tr class="crm-preferences-display-form-block-description">
      <td>&nbsp;</td>
      <td class="description">{ts}Sort name format for individual contact display names.{/ts}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{if $form.contact_edit_options.html}
  {literal}
    <script type="text/javascript">
      cj(function () {
        cj("#contactEditBlocks").sortable({
          placeholder: 'ui-state-highlight',
          update: getSorting
        });
        cj("#contactEditOptions").sortable({
          placeholder: 'ui-state-highlight',
          update: getSorting
        });
      });

      function getSorting(e, ui) {
        var params = new Array();
        var y = 0;
        var items = cj("#contactEditBlocks li");
        if (items.length > 0) {
          for (var y = 0; y < items.length; y++) {
            var idState = items[y].id.split('-');
            params[y + 1] = idState[1];
          }
        }

        items = cj("#contactEditOptions li");
        if (items.length > 0) {
          for (var x = 0; x < items.length; x++) {
            var idState = items[x].id.split('-');
            params[x + y + 1] = idState[1];
          }
        }
        cj('#contact_edit_preferences').val(params.toString());
      }
    </script>
  {/literal}
{/if}
