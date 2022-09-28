{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-selfsvcupdate-form">
  <table class="crm-selfsvcupdate-form-details">
    <tr>
      <th>{ts}Participant{/ts}</th>
      <th>{ts}Event{/ts}</th>
      <th>{ts}Fee Level{/ts}</th>
      <th>{ts}Amount{/ts}</th>
      <th>{ts}Registered{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th>{ts}Role{/ts}</th>
    </tr>
    <tr class="crm-selfsvcupdate-form-details">
      <td>{$details.name}</td><td>{$details.title}<br />{$details.event_start_date|truncate:10:''|crmDate}</td>
      <td class="crm-participant-participant_fee_level">{$details.fee_level}</td>
      <td class="right nowrap crm-paticipant-participant_fee_amount">{$details.fee_amount}</td>
      <td>{$details.register_date|truncate:10:''|crmDate}</td>
      <td>{$details.status}</td><td class="crm-participant-participant_role">{$details.role}</td>
    </tr>
  </table>
  <div class="crm-public-form-item crm-section selfsvcupdate-section">
    <div class="label">{$form.action.label}</div>
    <div class="content">{$form.action.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
  var contributionID = {/literal}'{$contributionId}'{literal};
  CRM.$(function($) {
    $('#action').on('change', function() {
      selected = $(this).find("option:selected").text();
      if (selected == 'Cancel' && contributionID) {
        CRM.alert('{/literal}{ts}Cancellations are not refundable.{/ts}{literal}', 'Warning', 'alert');
      }
    });
  });
</script>
{/literal}
