{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id="report-tab-format" class="civireport-criteria" role="tabpanel">
  <table class="form-layout">
    <tr class="crm-report-instanceForm-form-block-title">
      <td class="report-label" width="20%">{$form.title.label} {help id="title" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td >{$form.title.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-description">
      <td class="report-label" width="20%">{$form.description.label}</td>
      <td>{$form.description.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-report_header">
      <td class="report-label" width="20%">{$form.report_header.label}{help id="report_header" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.report_header.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-report_footer">
      <td class="report-label" width="20%">{$form.report_footer.label}</td>
      <td>{$form.report_footer.html}</td>
    </tr>
  </table>
</div>

<div id="report-tab-email" class="civireport-criteria" role="tabpanel">
  <h3 class="email-delivery-settings-title">
    {ts}Email Delivery Settings{/ts}
    {capture assign='helpTitle'}{ts}Email Delivery{/ts}{/capture}
    {help id="email-delivery-settings" title=$helpTitle file="CRM/Report/Form/Tabs/Settings.hlp"}
  </h3>
  <table class="form-layout email-delivery-settings-fields">
    <tr class="crm-report-instanceForm-form-block-email_subject">
      <td class="report-label" width="20%">{$form.email_subject.label}</td>
      <td>{$form.email_subject.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-email_to">
      <td class="report-label">{$form.email_to.label}</td>
      <td>{$form.email_to.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-email_cc">
      <td class="report-label">{$form.email_cc.label}</td>
      <td>{$form.email_cc.html|crmAddClass:huge}</td>
    </tr>
  </table>
</div>

<div id="report-tab-access" class="civireport-criteria" role="tabpanel">
  <table class="form-layout">
    <tr class="crm-report-instanceForm-form-block-is_navigation">
      <td class="report-label">{$form.is_navigation.label}</td>
      <td>{$form.is_navigation.html} {ts}Link to {/ts}  {$form.view_mode.html}<br />
        <span class="description">{ts}All report instances are automatically included in the Report Listing page. Check this box to also add this report to the navigation menu.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-parent_id" id="navigation_menu">
      <td class="report-label">{$form.parent_id.label} {help id="parent_id" file="CRM/Admin/Form/Navigation.hlp"}</td>
      <td>{$form.parent_id.html|crmAddClass:huge}</td>
    </tr>
    {if !empty($form.drilldown_id)}
    <tr class="crm-report-instanceForm-form-block-drilldown">
      <td class="report-label">{$form.drilldown_id.label}</td>
      <td>{$form.drilldown_id.html}</td>
    </tr>
    {/if}
    {if $config->userFramework neq 'Joomla'}
      <tr class="crm-report-instanceForm-form-block-permission">
        <td class="report-label" width="20%">{$form.permission.label} {help id="permission" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
        <td>{$form.permission.html|crmAddClass:huge}</td>
      </tr>
      {if !empty($form.grouprole)}
      <tr class="crm-report-instanceForm-form-block-role">
        <td class="report-label" width="20%">{$form.grouprole.label}</td>
        <td>{$form.grouprole.html|crmAddClass:huge}</td>
      </tr>
      {/if}
    {/if}
    <tr class="crm-report-instanceForm-form-block-add-to-my-reports">
      <td class="report-label">{$form.add_to_my_reports.label} {help id="add_to_my_reports" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.add_to_my_reports.html}
        <span class="description">{ts}If set to Yes, this report will appear in the My Reports section of the reports listing page and will only be visible by you.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-isReserved">
      <td class="report-label">{$form.is_reserved.label} {help id="is_reserved" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.is_reserved.html}
        <span class="description">{ts}If reserved, only users with 'administer reserved reports' permission can modify this report instance.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-addToDashboard">
      <td class="report-label">{$form.addToDashboard.label} {help id="addToDashboard" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.addToDashboard.html}
        <span class="description">{ts}Users with appropriate permissions can add this report to their dashboard.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-limitUser">
      <td class="report-label">{$form.row_count.label} {help id="row_count" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.row_count.html} {ts}rows{/ts}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-cache_minutes">
      <td class="report-label">{$form.cache_minutes.label} {help id="cache_minutes" file="CRM/Report/Form/Tabs/Settings.hlp"}</td>
      <td>{$form.cache_minutes.html} {ts}minutes{/ts}</td>
    </tr>
  </table>
</div>

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_navigation"
    trigger_value       =""
    target_element_id   ="navigation_menu"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    function showHideDashletControls() {
      $('.crm-report-instanceForm-form-block-limitUser, .crm-report-instanceForm-form-block-cache_minutes').toggle($(this).is(':checked'));
    }
    $('#addToDashboard').each(showHideDashletControls).change(showHideDashletControls);
  });
</script>
{/literal}
{if !empty($is_navigation)}
  <script type="text/javascript">
    document.getElementById('is_navigation').checked = true;
    showHideByValue('is_navigation','','navigation_menu','table-row','radio',false);
  </script>
{/if}
