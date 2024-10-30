{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting grant *}

<div class="crm-block crm-form-block crm-grant-form-block">
  {if $action eq 8}
     <div class="messages status">
         <p>{icon icon="fa-info-circle"}{/icon}
         {ts}Are you sure you want to delete this Grant?{/ts} {ts}This action cannot be undone.{/ts}</p>
         <p>{include file="CRM/Grant/Form/Task.tpl"}</p>
     </div>
  {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
        <tr class="crm-grant-form-block-contact_id">
          <td class="label">{$form.contact_id.label}</td>
          <td>{$form.contact_id.html}</td>
        </tr>
      <tr class="crm-grant-form-block-status_id">
         <td class="label">{$form.status_id.label}</td>
         <td>{$form.status_id.html}</td>
      </tr>
      <tr class="crm-grant-form-block-grant_type_id">
         <td class="label">{$form.grant_type_id.label}</td>
         <td>{$form.grant_type_id.html}</td>
      </tr>
      <tr class="crm-grant-form-block-amount_total">
         <td class="label">{$form.amount_total.label}</td>
         <td>{$form.amount_total.html}</td>
      </tr>
      <tr class="crm-grant-form-block-amount_requested">
         <td class="label">{$form.amount_requested.label}</td>
         <td>{$form.amount_requested.html}</td>
      </tr>
      <tr class="crm-grant-form-block-amount_granted">
         <td class="label">{$form.amount_granted.label}</td>
         <td>{$form.amount_granted.html}</td>
      </tr>
      <tr class="crm-grant-form-block-application_received_date">
         <td class="label">{$form.application_received_date.label}</td>
         <td>{$form.application_received_date.html}</td>
      </tr>
      <tr class="crm-grant-form-block-decision_date">
         <td class="label">{$form.decision_date.label}</td>
         <td>
           {$form.decision_date.html}</td>
      </tr>
      <tr class="crm-grant-form-block-money_transfer_date">
        <td class="label">{$form.money_transfer_date.label}</td>
        <td>
          {$form.money_transfer_date.html}</td>
      </tr>
      <tr class="crm-grant-form-block-grant_due_date">
        <td class="label">{$form.grant_due_date.label}</td>
        <td>{$form.grant_due_date.html}</td>
      </tr>
      <tr class="crm-grant-form-block-grant_report_received">
          <td class="label">{$form.grant_report_received.label}</td>
    <td>{$form.grant_report_received.html}</td>
      </tr>
      <tr class="crm-grant-form-block-rationale">
        <td class="label">{$form.rationale.label}</td>
    <td>{$form.rationale.html}</td>
      </tr>
      <tr class="crm-grant-form-block-note">
    <td class="label">{$form.note.label}</td>
    <td>{$form.note.html}</td>
      </tr>
  </table>

  {include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Grant' cid=false}

  <div class="crm-grant-form-block-attachment">
    {include file="CRM/Form/attachment.tpl"}
  </div>

   {/if}
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
