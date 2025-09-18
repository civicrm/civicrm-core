{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="payment-info-block"}
{if !empty($payments)}
  <table class="selector row-highlight">
    <tr>
      <th>{ts}Amount{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th>{ts}Payment Method{/ts}</th>
      <th>{ts}Date{/ts}</th>
      <th>{ts}Transaction ID{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th></th>
    </tr>
    {foreach from=$payments item=payment}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$payment.total_amount|crmMoney:$payment.currency}</td>
        <td>{$payment.financial_type}</td>
        <td>{$payment.payment_instrument}{if $payment.check_number} (#{$payment.check_number}){/if}</td>
        <td>{$payment.receive_date|crmDate}</td>
        <td>{$payment.trxn_id}</td>
        <td>{$payment.status}</td>
        <td>{$payment.action}</td>
      </tr>
    {/foreach}
  </table>
{else}
   {if $component eq 'event'}
     {assign var='entity' value='participant'}
   {else}
     {assign var='entity' value=$component}
   {/if}
   {ts 1=$entity}No payments found for this %1 record{/ts}
{/if}

  {foreach from=$paymentLinks item=paymentLink}
    <a class="open-inline action-item crm-hover-button" href="{crmURL p=$paymentLink.url q=$paymentLink.qs}"><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {ts}{$paymentLink.title}{/ts}</a>
  {/foreach}

{/crmRegion}
