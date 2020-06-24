{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template to display reports *}
{if $report}
{$report}
{else}
<div id="reportForm" class="crm-block crm-form-block crm-case-report-form-block">
<h3>{ts}Report Parameters{/ts}</h3>
    {strip}
        <table class="form-layout">
        <tr class="crm-case-report-form-block-include_activities">
           <td class="label">
               {$form.include_activities.label}
           </td>
           <td>
               {$form.include_activities.html}
           </td>
        </tr>
        <tr class="crm-case-report-form-block-is_redact">
           <td>
         &nbsp;
           </td>
           <td>
               {$form.is_redact.html}&nbsp;{$form.is_redact.label}
           </td>
        </tr>
        </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    {/strip}
</div>
{/if}
