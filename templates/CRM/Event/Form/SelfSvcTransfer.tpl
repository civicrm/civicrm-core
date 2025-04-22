{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-selfsvctransfer-form">
  <table class="crm-selfsvctransfer-form-details">
    <tr>
      <th>{ts}Current<br />Participant{/ts}</th>
      <th>{ts}Event{/ts}</th>
      {if !empty($details.fee_level)}
        <th>{ts}Fee Level{/ts}</th>
      {/if}
      {if !empty($details.fee_amount)}
        <th>{ts}Amount{/ts}</th>
      {/if}
      <th>{ts}Registered{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th>{ts}Role{/ts}</th>
    </tr>
    <tr class="crm-selfsvctransfer-form-details">
      <td>{$details.name}</td>
      <td>{$details.title}<br />{$details.event_start_date|truncate:10:''|crmDate}</td>
      {if !empty($details.fee_level)}
        <td class="crm-participant-participant_fee_level">{$details.fee_level}</td>
      {/if}
      {if !empty($details.fee_amount)}
        <td class="right nowrap crm-paticipant-participant_fee_amount">{$details.fee_amount}</td>
      {/if}
      <td>{$details.register_date|truncate:10:''|crmDate}</td>
      <td>{$details.statuslabel|escape}</td>
      <td class="crm-participant-participant_role">{$details.rolelabel|escape}</td>
    </tr>
  </table>
  {if !empty($form.contact_id)}
    <div class="crm-public-form-item crm-section selfsvctransfer-section">
      <div class="crm-public-form-item crm-section selfsvctransfer-contact_id-section">
        <div class="label">{$form.contact_id.label}</div>
        <div class="content">{$form.contact_id.html}</div>
        <div class="clear"></div>
      </div>
    </div>
  {else}
    <div class="crm-public-form-item crm-section selfsvctransfer-section">
      <div class="crm-public-form-item crm-section selfsvctransfer-firstname-section">
        <div class="label">{$form.first_name.label}</div>
        <div class="content">{$form.first_name.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-public-form-item crm-section selfsvctransfer-lastname-section">
        <div class="label">{$form.last_name.label}</div>
        <div class="content">{$form.last_name.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-public-form-item crm-section selfsvctransfer-email-section">
        <div class="label">{$form.email.label}</div>
        <div class="content">{$form.email.html}</div>
        <div class="clear"></div>
      </div>
    </div>
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
