{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Displays contribution/event fees. *}

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
          <th class="right text-right">{ts}Fee{/ts}</th>
        {else}
          <th class="right text-right">{ts}Qty{/ts}</th>
          <th class="right text-right">{ts}Unit Price{/ts}</th>
          {if !$getTaxDetails}
            <th class="right text-right">{ts}Total Price{/ts}</th>
          {/if}
        {/if}

        {if $getTaxDetails}
          <th class="right text-right">{ts}Subtotal{/ts}</th>
          <th class="right text-right">{ts}Tax Rate{/ts}</th>
          <th class="right text-right">{ts}Tax Amount{/ts}</th>
          <th class="right text-right">{ts}Total Amount{/ts}</th>
        {/if}

        {if $pricesetFieldsCount}
          <th class="right text-right">{ts}Total Participants{/ts}</th>
        {/if}
      </tr>
      {foreach from=$value item=line}
        <tr{if $line.qty EQ 0} class="cancelled"{/if}>
          <td>{if $line.field_title && $line.html_type neq 'Text'}{$line.field_title} &ndash; {$line.label}{else}{$line.label}{/if} {if $line.description}<div class="description">{$line.description}</div>{/if}</td>
          {if $displayLineItemFinancialType}
            <td>{$line.financial_type}</td>
          {/if}
          {if $context NEQ "Membership"}
            <td class="right text-right">{$line.qty}</td>
            <td class="right text-right">{$line.unit_price|crmMoney:$currency}</td>
    {else}
            <td class="right text-right">{$line.line_total|crmMoney:$currency}</td>
          {/if}
    {if !$getTaxDetails && $context NEQ "Membership"}
      <td class="right text-right">{$line.line_total|crmMoney:$currency}</td>
    {/if}
    {if $getTaxDetails}
      <td class="right text-right">{$line.line_total|crmMoney:$currency}</td>
      {if $line.tax_rate != "" || $line.tax_amount != ""}
        <td class="right text-right">{$taxTerm} ({$line.tax_rate}%)</td>
        <td class="right text-right">{$line.tax_amount|crmMoney:$currency}</td>
      {else}
        <td></td>
        <td></td>
      {/if}
      <td class="right text-right">{assign var=totalWithTax value=$line.line_total+$line.tax_amount}{$totalWithTax|crmMoney:$currency}</td>
    {/if}
          {if $pricesetFieldsCount}
            <td class="right text-right">{$line.participant_count}</td>
          {/if}
        </tr>
      {/foreach}
    </table>
  {/if}
{/foreach}

<div class="crm-grid-table total_amount-section pull-right float-right">
  {if $getTaxDetails && $totalTaxAmount}
    <div class="crm-grid-row">
      <div class="crm-grid-cell bold right text-right">{ts 1=$taxTerm}Total %1{/ts}</div>
      <div class="crm-grid-cell right text-right" id="totalTaxAmount" data-totalTaxAmount="{$totalTaxAmount}">{$totalTaxAmount|crmMoney:$currency}</div>
    </div>
  {/if}
  {if $context EQ "Event"}
    {if $totalTaxAmount}
      {assign var=eventSubTotal value=$totalAmount-$totalTaxAmount}
      <div class="crm-grid-row">
        <div class="crm-grid-cell bold right text-right">{ts}Subtotal{/ts}</div>
        <div class="crm-grid-cell right text-right" id="eventSubTotal" data-eventSubTotal="{$eventSubTotal}">{$eventSubTotal|crmMoney:$currency}</div>
      </div>
    {/if}
  {/if}
  <div class="crm-grid-row">
    <div class="crm-grid-cell bold right text-right">{ts}Total{/ts}</div>
    <div class="crm-grid-cell right text-right" id="totalAmount" data-totalAmount="{$totalAmount}">{$totalAmount|crmMoney:$currency}</div>
  </div>
  {* set by CRM/Contribute/Page/PaymentInfo.tpl *}
  <div class="hiddenElement">
    <div class="crm-grid-cell bold right text-right">{ts}Amount Paid{/ts}</div>
    <div class="crm-grid-cell right text-right" id="paymentInfoTotalPaid"></div>
  </div>
  <div class="hiddenElement">
    <div class="crm-grid-cell bold right text-right">{ts}Amount Due{/ts}</div>
    <div class="crm-grid-cell right text-right" id="paymentInfoAmountDue"></div>
  </div>
  {if $pricesetFieldsCount}
    <div class="crm-grid-row">
      <div class="crm-grid-cell bold right text-right">{ts}Total Participants{/ts}</div>
      {foreach from=$lineItem item=pcount}
        {if $pcount neq 'skip'}
          {assign var="lineItemCount" value=0}
          {foreach from=$pcount item=p_count}
            {assign var="intPCount" value=$p_count.participant_count|string_format:"%d"}
            {assign var="lineItemCount" value=$lineItemCount+$intPCount}
          {/foreach}
          {if $lineItemCount < 1}
            {assign var="lineItemCount" value=1}
          {/if}
          {assign var="totalcount" value=$totalcount+$lineItemCount}
        {/if}
      {/foreach}
      <div class="crm-grid-cell right text-right" id="participantTotalCount" data-participantTotalCount="{$totalcount}">{$totalcount}</div>
    </div>
  {/if}
</div>

{if $hookDiscount && $hookDiscount.message}
  <div class="crm-section hookDiscount-section">
    <em>({$hookDiscount.message})</em>
  </div>
{/if}
