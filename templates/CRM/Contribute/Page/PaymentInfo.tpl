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
{if $show eq 'event-payment'}
{literal}
<script type='text/javascript'>
CRM.$(function($) {
  if ($("#payment-info").length) {
    var dataUrl = {/literal}'{crmURL p="civicrm/payment/view" h=0 q="action=browse&id=$participantId&cid=`$contactId`&component=event&context=payment_info&snippet=4"}'{literal};
    $.ajax({
      url: dataUrl,
      async: false,
      success: function(html) {
        $("#payment-info").html(html).trigger('crmLoad');
      }
    });

    var taxAmount = "{$totalTaxAmount}";
    if (taxAmount) {
      $('.total_amount-section').show();
    }
    else {
      $('.total_amount-section').remove();
    }
  }
});
</script>
{/literal}
{/if}
{if $context eq 'payment_info'}
<table id='info'>
  <tr class="columnheader">
    {if $component eq "event"}
      <th>{ts}Total Fee(s){/ts}</th>
    {/if}
    <th class="right">{ts}Total Paid{/ts}</th>
    <th class="right">{ts}Balance{/ts}</th>
  </tr>
  <tr>
    <td>{$paymentInfo.total|crmMoney}</td>
    <td class='right'>
      {if $paymentInfo.paid > 0}
        {$paymentInfo.paid|crmMoney}<br/>
        <a class="crm-hover-button action-item crm-popup medium-popup" href='{crmURL p="civicrm/payment" q="view=transaction&cid=`$cid`&id=`$paymentInfo.id`&component=`$paymentInfo.component`&action=browse"}'>
          <i class="crm-i fa-list"></i>
          {ts}view payments{/ts}
        </a>
      {/if}
    </td>
    <td class='right'>{$paymentInfo.balance|crmMoney}</td>
  </tr>
</table>
{if $paymentInfo.balance and !$paymentInfo.payLater}
  {if $paymentInfo.balance > 0}
     {assign var=paymentButtonName value='Record Payment'}
  {elseif $paymentInfo.balance < 0}
     {assign var=paymentButtonName value='Record Refund'}
  {/if}
  <a class="action-item crm-hover-button" href='{crmURL p="civicrm/payment" q="action=add&reset=1&component=`$component`&id=`$id`&cid=`$cid`"}'><i class="crm-i fa-plus-circle"></i> {ts}{$paymentButtonName}{/ts}</a>
{/if}
{/if}
