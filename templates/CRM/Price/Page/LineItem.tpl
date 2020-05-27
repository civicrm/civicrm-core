{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Displays contribution/event fees when price set is used. *}
{if !$currency && $fee_currency}
  {assign var=currency value="$fee_currency"}
{/if}

{foreach from=$lineItem item=value key=priceset}
  {if $value neq 'skip'}
    {if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
      {if $priceset GT 0}
        <br />
      {/if}
      <strong>{ts}Participant {$priceset+1}{/ts}</strong> {$part.$priceset.info}
    {/if}
    <table>
      <tr class="columnheader">
        <th>{ts}Item{/ts}</th>
        {if $displayLineItemFinancialType}
          <th>{ts}Financial Type{/ts}</th>
        {/if}
        {if $context EQ "Membership"}
          <th class="right">{ts}Fee{/ts}</th>
        {else}
          <th class="right">{ts}Qty{/ts}</th>
          <th class="right">{ts}Unit Price{/ts}</th>
          {if !$getTaxDetails}
            <th class="right">{ts}Total Price{/ts}</th>
          {/if}
        {/if}

        {if $getTaxDetails}
          <th class="right">{ts}Subtotal{/ts}</th>
          <th class="right">{ts}Tax Rate{/ts}</th>
          <th class="right">{ts}Tax Amount{/ts}</th>
          <th class="right">{ts}Total Amount{/ts}</th>
        {/if}

        {if $pricesetFieldsCount}
          <th class="right">{ts}Total Participants{/ts}</th>
        {/if}
      </tr>
      {foreach from=$value item=line}
        <tr{if $line.qty EQ 0} class="cancelled"{/if}>
          <td>{if $line.field_title && $line.html_type neq 'Text'}{$line.field_title} &ndash; {$line.label}{else}{$line.label}{/if} {if $line.description}<div class="description">{$line.description}</div>{/if}</td>
          {if $displayLineItemFinancialType}
            <td>{$line.financial_type}</td>
          {/if}
          {if $context NEQ "Membership"}
            <td class="right">{$line.qty}</td>
            <td class="right">{$line.unit_price|crmMoney:$currency}</td>
    {else}
            <td class="right">{$line.line_total|crmMoney:$currency}</td>
          {/if}
    {if !$getTaxDetails && $context NEQ "Membership"}
      <td class="right">{$line.line_total|crmMoney:$currency}</td>
    {/if}
    {if $getTaxDetails}
      <td class="right">{$line.line_total|crmMoney:$currency}</td>
      {if $line.tax_rate != "" || $line.tax_amount != ""}
        <td class="right">{$taxTerm} ({$line.tax_rate}%)</td>
        <td class="right">{$line.tax_amount|crmMoney:$currency}</td>
      {else}
        <td></td>
        <td></td>
      {/if}
      <td class="right">{$line.line_total+$line.tax_amount|crmMoney:$currency}</td>
    {/if}
          {if $pricesetFieldsCount}
            <td class="right">{$line.participant_count}</td>
          {/if}
        </tr>
      {/foreach}
    </table>
  {/if}
{/foreach}

<div class="crm-section no-label total_amount-section">
  <div class="content bold">
    {if $getTaxDetails && $totalTaxAmount}
      {ts 1=$taxTerm}Total %1 Amount{/ts}: {$totalTaxAmount|crmMoney:$currency}<br />
    {/if}
    {if $context EQ "Contribution"}
      {ts}Contribution Total{/ts}:
    {elseif $context EQ "Event"}
      {if $totalTaxAmount}
        {ts}Event SubTotal: {$totalAmount-$totalTaxAmount|crmMoney:$currency}{/ts}<br />
      {/if}
      {ts}Event Total{/ts}:
    {elseif $context EQ "Membership"}
      {ts}Membership Fee Total{/ts}:
    {else}
      {ts}Total Amount{/ts}:
    {/if}
    {$totalAmount|crmMoney:$currency}
  </div>
  <div class="clear"></div>
  <div class="content bold">
    {if $pricesetFieldsCount}
      {ts}Total Participants{/ts}:
      {foreach from=$lineItem item=pcount}
        {if $pcount neq 'skip'}
        {assign var="lineItemCount" value=0}

        {foreach from=$pcount item=p_count}
          {assign var="lineItemCount" value=$lineItemCount+$p_count.participant_count}
        {/foreach}
        {if $lineItemCount < 1 }
          {assign var="lineItemCount" value=1}
        {/if}
        {assign var="totalcount" value=$totalcount+$lineItemCount}
        {/if}
      {/foreach}
      {$totalcount}
    {/if}
  </div>
  <div class="clear"></div>
</div>

{if $hookDiscount.message}
  <div class="crm-section hookDiscount-section">
    <em>({$hookDiscount.message})</em>
  </div>
{/if}
{literal}
<script type="text/javascript">
CRM.$(function($) {
  {/literal}
    var comma = '{$config->monetaryThousandSeparator}';
    var dot = '{$config->monetaryDecimalPoint}';
    var format = '{$config->moneyformat}';
    var currency = '{$currency}';
    var currencySymbol = '{$currencySymbol}';
  {literal}
  // Todo: This function should be a utility
  function moneyFormat(amount) {
    amount = parseFloat(amount).toFixed(2);
    amount = amount.replace(',', 'comma').replace('.', 'dot');
    amount = amount.replace('comma', comma).replace('dot', dot);
    return format.replace('%C', currency).replace('%c', currencySymbol).replace('%a', amount);
  }});
</script>
{/literal}
