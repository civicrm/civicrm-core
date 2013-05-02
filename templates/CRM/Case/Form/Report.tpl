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
