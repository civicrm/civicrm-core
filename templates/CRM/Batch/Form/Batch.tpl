{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing batch  *}
{if $action eq 8}
  <h3>{ts}Delete Data Entry Batch{/ts}</h3>
{elseif $action eq 2}
  <h3>{ts}Edit Data Entry Batch{/ts}</h3>
{else}
  <h3>{ts}New Data Entry Batch{/ts}</h3>
{/if}
<div class="crm-block crm-form-block crm-batch-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
     {ts}WARNING: Deleting this batch will result in the loss of all data entered for the batch.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
      <tr class="crm-batch-form-block-title">
          <td class="label">{$form.title.label}</td>
          <td>{$form.title.html}</td>
      </tr>
      <tr class="crm-batch-form-block-type_id">
          <td class="label">{$form.type_id.label}</td>
          <td>{$form.type_id.html}</td>
      </tr>
      <tr class="crm-batch-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
      </tr>
      <tr class="crm-batch-form-block-item_count">
          <td class="label">{$form.item_count.label}</td>
          <td>{$form.item_count.html}</td>
      </tr>
      <tr  class="crm-batch-form-block-total">
           <td class="label">{$form.total.label}</td>
           <td>{$form.total.html|crmAddClass:eight}</td>
      </tr>
  </table>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
