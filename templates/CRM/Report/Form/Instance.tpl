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
<h3>{ts}General Settings{/ts}</h3>
<table class="form-layout">
    <tr class="crm-report-instanceForm-form-block-title">
        <td class="report-label" width="20%">{$form.title.label} {help id="id-report_title" file="CRM/Report/Form/Settings.hlp"}</td>
        <td >{$form.title.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-description">
        <td class="report-label" width="20%">{$form.description.label}</td>
        <td>{$form.description.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-report_header">
        <td class="report-label" width="20%">{$form.report_header.label}{help id="id-report_header" file="CRM/Report/Form/Settings.hlp"}</td>
        <td>{$form.report_header.html}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-report_footer">
        <td class="report-label" width="20%">{$form.report_footer.label}</td>
        <td>{$form.report_footer.html}</td>
    </tr>
</table>
<br/>

<h3>{ts}Email Delivery Settings{/ts} {help id="id-email_settings" file="CRM/Report/Form/Settings.hlp"}</h3>
<table class="form-layout">
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
<br/>

<h3>{ts}Other Settings{/ts}</h3>
<table class="form-layout">
    <tr class="crm-report-instanceForm-form-block-is_navigation">
  <td class="report-label">{$form.is_navigation.label}</td>
        <td>{$form.is_navigation.html}<br />
            <span class="description">{ts}All report instances are automatically included in the Report Listing page. Check this box to also add this report to the navigation menu.{/ts}</span>
        </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-parent_id" id="navigation_menu">
  <td class="report-label">{$form.parent_id.label} {help id="id-parent" file="CRM/Admin/Form/Navigation.hlp"}</td>
  <td>{$form.parent_id.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-drilldown">
      <td class="report-label">{$form.drilldown_id.label}</td>
      <td>{$form.drilldown_id.html}</td>
    </tr>
    {if $config->userFramework neq 'Joomla'}
        <tr class="crm-report-instanceForm-form-block-permission">
            <td class="report-label" width="20%">{$form.permission.label} {help id="id-report_perms" file="CRM/Report/Form/Settings.hlp"}</td>
            <td>{$form.permission.html|crmAddClass:huge}</td>
        </tr>
        <tr class="crm-report-instanceForm-form-block-role">
            <td class="report-label" width="20%">{$form.grouprole.label}</td>
            <td>{$form.grouprole.html|crmAddClass:huge}</td>
        </tr>
    {/if}
    <tr class="crm-report-instanceForm-form-block-isReserved">
      <td class="report-label">{$form.is_reserved.label} {help id="id-is_reserved" file="CRM/Report/Form/Settings.hlp"}</td>
      <td>{$form.is_reserved.html}
        <span class="description">{ts}If reserved, only users with 'administer reserved reports' permission can modify this report instance.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-report-instanceForm-form-block-addToDashboard">
      <td class="report-label">{$form.addToDashboard.label} {help id="id-dash_avail" file="CRM/Report/Form/Settings.hlp"}</td>
      <td>{$form.addToDashboard.html}
        <span class="description">{ts}Users with appropriate permissions can add this report to their dashboard.{/ts}</span>
      </td>
    </tr>
</table>

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_navigation"
    trigger_value       =""
    target_element_id   ="navigation_menu"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{if $is_navigation}
 <script type="text/javascript">
     document.getElementById('is_navigation').checked = true;
     showHideByValue('is_navigation','','navigation_menu','table-row','radio',false);
 </script>
{/if}

{literal}
<script type="text/javascript">
  cj(document).ready(function() {
    cj('#title').data('initial_value', cj('#title').val());
  });
  cj( function(){
      var formName = {/literal}"{$form.formName}"{literal};
      cj('#_qf_' + formName + '_submit_save').click (
          function(){
              if ( cj('#is_navigation').attr('checked') && cj('#parent_id').val() == '') {
                  var confirmMsg = {/literal}'{ts escape="js"}You have chosen to include this report in the Navigation Menu without selecting a Parent Menu item from the dropdown. This will add the report to the top level menu bar. Are you sure you want to continue?{/ts}'{literal}
                  return confirm(confirmMsg);
              }
          }
      );
      cj('#_qf_' + formName + '_submit_next').click (
          function(){
              if ( cj('#title').data('initial_value') == cj('#title').val() ) {
                  var confirmMsg = {/literal}'{ts escape="js"}You are saving a copy of this report with the same report title. Are you sure you want to continue?{/ts}'{literal}
                  return confirm(confirmMsg);
              }
          }
      );
  });
</script>
{/literal}
