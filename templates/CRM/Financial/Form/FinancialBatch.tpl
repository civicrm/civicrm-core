{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting financial batch  *}
<div class="crm-block crm-form-block crm-financial_type-form-block">
{if $action eq 8}
  <div class="messages status">
    {icon icon="fa-info-circle"}{/icon}
    {ts}WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.{/ts} {ts}Deleting a financial type cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
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
  <details class="crm-accordion-light">
    <summary>{ts}Optional Constraints{/ts}</summary>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr class="crm-contribution-form-block-payment_instrument">
          <td class="label">{$form.payment_instrument_id.label}</td>
          <td class="html-adjust">{$form.payment_instrument_id.html} {help id="payment_instrument_id"}</td>
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
  </details>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>
{include file="CRM/Form/validate.tpl"}
