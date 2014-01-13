{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
    var dataUrl = {/literal}'{crmURL p="civicrm/payment/view" h=0 q="action=browse&id=$participantId&cid=`$contactId`&component=event&context=payment_info&snippet=4"}'{literal};
    cj.ajax({
      url: dataUrl,
      async: false,
      success: function(html) {
        cj("#payment-info").html(html);
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
        <a class='action-item' href='{crmURL p="civicrm/payment/view" q="action=browse&cid=`$cid`&id=`$paymentInfo.id`&component=`$paymentInfo.component`&context=transaction"}'>{$paymentInfo.paid|crmMoney}<br/>>> view payments</a>
      {/if}
    </td>
    <td class='right'>{$paymentInfo.balance|crmMoney}</td>
  </tr>
</table>
{if $paymentInfo.balance > 0}
  <a class="button" href='{crmURL p="civicrm/payment/add" q="reset=1&component=`$component`&id=`$id`&cid=`$cid`"}' title="{ts}Record Payment{/ts}"><span><div class="icon add-icon"></div> {ts}Record Payment{/ts}</span></a>
{/if}
{elseif $context eq 'transaction'}
<table id='info'>
  <tr class="columnheader">
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Type{/ts}</th>
    <th>{ts}Paid By{/ts}</th>
    <th>{ts}Received{/ts}</th>
    <th>{ts}Transaction ID{/ts}</th>
    <th>{ts}Status{/ts}</th>
  </tr>
  {foreach from=$rows item=row}
    <tr>
      <td>{$row.total_amount|crmMoney}</td>
      <td>{$row.financial_type}</td>
      <td>{$row.payment_instrument}</td>
      <td>{$row.receive_date|crmDate}</td>
      <td>{$row.trxn_id}</td>
      <td>{$row.status}</td>
    </tr>
  {/foreach}
<table>
{/if}