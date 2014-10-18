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
{* this template is used for adding/editing/deleting financial type  *}
<div class="crm-block crm-form-block crm-financial_type-form-block">
   {if $action eq 8}
      <div class="messages status">
          <div class="icon inform-icon"></div>
          {ts}WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.{/ts} {ts}Deleting a financial type cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
     <table class="form-layout">
      <tr class="crm-contribution-form-block-name">
     <td class="label">{$form.name.label}</td>
    <td class="html-adjust">{$form.name.html}</td>
       </tr>
       <tr class="crm-contribution-form-block-description">
        <td class="label">{$form.description.label}</td>
    <td class="html-adjust">{$form.description.html}</td>
       </tr>

       <tr class="crm-contribution-form-block-is_deductible">
        <td class="label">{$form.is_deductible.label}</td>
    <td class="html-adjust">{$form.is_deductible.html}<br />
        <span class="description">{ts}Are contributions of this type tax-deductible?{/ts}</span>
    </td>
       </tr>
       <tr class="crm-contribution-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
    <td class="html-adjust">{$form.is_active.html}</td>
       </tr>
      <tr class="crm-contribution-form-block-is_reserved">
        <td class="label">{$form.is_reserved.label}</td>
    <td class="html-adjust">{$form.is_reserved.html}</td>
       </tr>

      </table>
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
   {if $action eq 2 or $action eq 4 } {* Update or View*}
    <div class="crm-submit-buttons">
       <a href="{crmURL p='civicrm/admin/financial/financialType/accounts' q="action=browse&reset=1&aid=$aid"}" class="button"><span>{ts}View or Edit Financial Accounts{/ts}</a></span>
    </div>
   {/if}
</div>

