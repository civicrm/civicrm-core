{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{* this template is used for adding/editing/deleting grant *}

<div class="crm-block crm-form-block crm-grant-form-block">
  {if $action eq 8}
     <div class="messages status">
         <p><div class="icon inform-icon"></div>&nbsp;
         {ts}Are you sure you want to delete this Grant?{/ts} {ts}This action cannot be undone.{/ts}</p>
         <p>{include file="CRM/Grant/Form/Task.tpl"}</p>
     </div>
  {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      <table class="form-layout-compressed">
      {if $context eq 'standalone' || $context eq 'search'}
        <tr class="crm-grant-form-block-contact_id">
          <td class="label">{$form.contact_id.label}</td>
          <td>{$form.contact_id.html}</td>
        </tr>
      {/if}
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
         <td>{$form.amount_requested.html}<br /><span class="description">{ts}Amount requested for grant in original currency (if different).{/ts}</span></td>
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
           {$form.decision_date.html}<br />
           <span class="description">{ts}Date on which the grant decision was finalized.{/ts}</span>
         </td>
      </tr>
      <tr class="crm-grant-form-block-money_transfer_date">
        <td class="label">{$form.money_transfer_date.label}</td>
        <td>
          {$form.money_transfer_date.html}<br />
          <span class="description">{ts}Date on which the grant money was transferred.{/ts}</span>
        </td>
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

  {include file="CRM/common/customDataBlock.tpl"}

  <div class="crm-grant-form-block-attachment">
    {include file="CRM/Form/attachment.tpl"}
  </div>

   {/if}
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
