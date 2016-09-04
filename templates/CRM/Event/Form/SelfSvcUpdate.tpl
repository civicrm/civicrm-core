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
        CRM.alert(ts('Cancellations are not refundable.'), 'Warning', 'alert');
      }
    });
  });
</script>
{/literal}
