{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* this template is used for adding/editing/deleting financial batch  *}
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
      <td class="label">{$form.title.label}</td>
      <td class="html-adjust">{$form.title.html}</td>
    </tr>
    <tr class="crm-contribution-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td class="html-adjust">{$form.description.html}</td>
    </tr>
    {if $action eq 2}
      <tr class="crm-contribution-form-block-contact">
        <td class="label">{ts}Created By{/ts}</td>
        <td class="html-adjust">{$contactName}</td>
      </tr>
      <tr class="crm-contribution-form-block-open_date">
        <td class="label">{ts}Opened Date{/ts}</td>
        <td class="html-adjust">{$created_date|crmDate}</td>
      </tr>
      <tr class="crm-contribution-form-block-modified_date">
        <td class="label">{ts}Modified Date{/ts}</td>
        <td class="html-adjust">{$modified_date|crmDate}</td>
      </tr>
      <tr class="crm-contribution-form-block-batch_status">
        <td class="label">{$form.status_id.label}</td>
        <td class="html-adjust">{$form.status_id.html}</td>
      </tr>
    {/if}
  </table>
  <fieldset class="crm-collapsible">
    <legend class="collapsible-title">{ts}Optional Constraints{/ts}</legend>
      <div>
      <table class="form-layout">
        <tr class="crm-contribution-form-block-payment_instrument">
          <td class="label">{$form.payment_instrument_id.label}</td>
          <td class="html-adjust">{$form.payment_instrument_id.html} {help id="payment_instrument"}</td>
        </tr>
        <tr class="crm-contribution-form-block-item_count">
          <td class="label">{$form.item_count.label}</td>
          <td class="html-adjust">{$form.item_count.html|crmAddClass:number} {help id="item_count"}</td>
        </tr>
        <tr class="crm-contribution-form-block-total">
          <td class="label">{$form.total.label}</td>
          <td class="html-adjust">{$form.total.html|crmAddClass:number} {help id="total"}</td>
        </tr>
      </table>
    </div>
  </fieldset>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>
{include file="CRM/Form/validate.tpl"}
