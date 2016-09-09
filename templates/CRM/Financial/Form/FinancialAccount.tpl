{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
<div class="crm-block crm-form-block crm-contribution_type-form-block crm-financial_type-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}WARNING: You cannot delete a {$delName} Financial Account if it is currently used by any Financial Types. Consider disabling this option instead.{/ts} {ts}Deleting a financial type cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr class="crm-contribution-form-block-name">
      <td class="label">{$form.name.label}</td>
      <td class="html-adjust">{$form.name.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td class="html-adjust">{$form.description.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-organisation_name">
      <td class="label">{$form.contact_id.label}&nbsp;{help id="id-financial-owner" file="CRM/Financial/Form/FinancialAccount.hlp"}</td>
      <td class="html-adjust">{$form.contact_id.html}<br />
        <span class="description">{ts}Use this field to indicate the organization that owns this account.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-financial_account_type_id">
      <td class="label">{$form.financial_account_type_id.label}</td>
      <td class="html-adjust">{$form.financial_account_type_id.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-accounting_code">
      <td class="label">{$form.accounting_code.label}</td>
      <td class="html-adjust">{$form.accounting_code.html}<br />
        <span class="description">{ts}Enter the corresponding account code used in your accounting system. This code will be available for contribution export, and included in accounting batch exports.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-account_type_code">
      <td class="label">{$form.account_type_code.label}&nbsp;{help id="id-account-type-code" file="CRM/Financial/Form/FinancialAccount.hlp"}</td>
      <td class="html-adjust">{$form.account_type_code.html}<br />
        <span class="description">{ts}Enter an account type code for this account. Account type codes are required for QuickBooks integration and will be included in all accounting batch exports.{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-is_deductible">
      <td class="label">{$form.is_deductible.label}</td>
      <td class="html-adjust">{$form.is_deductible.html}<br />
        <span class="description">{ts}Are monies received into this account tax-deductible?{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-is_active">
      <td class="label">{$form.is_active.label}</td>
      <td class="html-adjust">{$form.is_active.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-is_tax">
      <td class="label">{$form.is_tax.label}</td>
      <td class="html-adjust">{$form.is_tax.html}<br />
        <span class="description">{ts}Does this account hold taxes collected?{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-tax_rate">
      <td class="label">{$form.tax_rate.label}</td>
      <td class="html-adjust">{$form.tax_rate.html}<br />
        <span class="description">{ts}The default rate used to calculate the taxes collected into this account (e.g. for tax rate of 8.27%, enter 8.27).{/ts}</span>
      </td>
    </tr>
    <tr class="crm-contribution-form-block-is_default">
      <td class="label">{$form.is_default.label}</td>
      <td class="html-adjust">{$form.is_default.html}<br />
        <span class="description">{ts}Is this account to be used as the default account for its financial account type when associating financial accounts with financial types?{/ts}</span>
      </td>
    </tr>
    {if $form.opening_balance}
      <tr class="crm-contribution-form-block-opening_balance">
        <td class="label">{$form.opening_balance.label}</td>
        <td class="html-adjust">{$form.opening_balance.html}
        </td>
      </tr>
      <tr class="crm-contribution-form-block-current_period_opening_balance">
        <td class="label">{$form.current_period_opening_balance.label}</td>
        <td class="html-adjust">{$form.current_period_opening_balance.html}
        </td>
      </tr>
    {/if}
  </table>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>
{if $form.opening_balance}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#financial_account_type_id').on('change', showHideElement);
      showHideElement();
      function showHideElement() {
        var financialAccountType = $('#financial_account_type_id').val();
        var financialAccountTypes = '{/literal}{$limitedAccount}{literal}';
	if ($.inArray(financialAccountType, financialAccountTypes) > -1) {
	  $('tr.crm-contribution-form-block-current_period_opening_balance').show();
	  $('tr.crm-contribution-form-block-opening_balance').show();
	}
	else {
	  $('tr.crm-contribution-form-block-current_period_opening_balance').hide();
	  $('tr.crm-contribution-form-block-opening_balance').hide();
	}
      }
    });
  </script>
{/literal}
{/if}
