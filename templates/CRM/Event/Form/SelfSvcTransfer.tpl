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
<div class="crm-selfsvctransfer-form">
  <table class="crm-selfsvctransfer-form-details">
    <tr>
      <th>{ts}Current<br />Participant{/ts}</th>
      <th>{ts}Event{/ts}</th>
      <th>{ts}Fee Level{/ts}</th>
      <th>{ts}Amount{/ts}</th>
      <th>{ts}Registered{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th>{ts}Role{/ts}</th>
    </tr>
    <tr class="crm-selfsvctransfer-form-details">
      <td>{$details.name}</td>
      <td>{$details.title}<br />{$details.event_start_date|truncate:10:''|crmDate}</td>
      <td class="crm-participant-participant_fee_level">{$details.fee_level}</td>
      <td class="right nowrap crm-paticipant-participant_fee_amount">{$details.fee_amount}</td>
      <td>{$details.register_date|truncate:10:''|crmDate}</td>
      <td>{$details.status}</td>
      <td class="crm-participant-participant_role">{$details.role}</td>
    </tr>
  </table>
  {if $form.contact_id}
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
