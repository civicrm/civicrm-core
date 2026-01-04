{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-content-block crm-contribution-view-form-block">
<table class="crm-info-panel">
  {if $is_test}
    <div class="help">
      <strong>{ts}This is a TEST transaction{/ts}</strong>
    </div>
  {/if}
  <tr class="crm-contribution-form-block-contact_id">
    <td class="label">{ts}From{/ts}</td>
    <td class="bold"><a href="{crmURL p='civicrm/contact/view' q="cid=$contact_id"}">{$displayName}</a></td>
  </tr>
  <tr class="crm-contribution-form-block-financial_type_id">
    <td class="label">{ts}Financial Type{/ts}</td>
    <td>{$financial_type}{if $is_test} {ts}(test){/ts} {/if}</td>
  </tr>
  <tr class="crm-contribution-form-block-source">
    <td class="label">{ts}Source{/ts}</td>
    <td>{$source|escape}</td>
  </tr>
  {if empty($is_template)}
  <tr class="crm-contribution-form-block-receive_date">
    <td class="label">{ts}Date{/ts}</td>
    <td>{if $receive_date}{$receive_date|crmDate}{else}({ts}not available{/ts}){/if}</td>
  </tr>
  {/if}
</table>
{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
{if $contribution_recur_id}
  <a class="open-inline action-item crm-hover-button" href='{crmURL p="civicrm/contact/view/contributionrecur" q="reset=1&id=`$contribution_recur_id`&cid=`$contact_id`&context=contribution"}'>
      {ts}View Recurring Contribution{/ts}
  </a>
  <br/>
  {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
{/if}
<div class="clear"></div>
<br>
<table class="crm-info-panel">
  {if $associatedParticipants}
    <tr class="crm-contribution-form-block-associated_participants">
      <td class="label">{ts}Associated participants{/ts}</td>
      <td>
        {include file="CRM/Contribute/Form/ContributionViewAssociatedParticipants.tpl" associatedParticipants=$associatedParticipants}
      </td>
    </tr>
  {/if}
  {if $non_deductible_amount}
    <tr class="crm-contribution-form-block-non_deductible_amount">
      <td class="label">{ts}Non-deductible Amount{/ts}</td>
      <td>{$non_deductible_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $fee_amount}
    <tr class="crm-contribution-form-block-fee_amount">
      <td class="label">{ts}Processor Fee{/ts}</td>
      <td>{$fee_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $net_amount}
    <tr class="crm-contribution-form-block-net_amount">
      <td class="label">{ts}Net Amount{/ts}</td>
      <td>{$net_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $isDeferred AND $revenue_recognition_date}
    <tr>
      <td class="label">{ts}Revenue Recognition Date{/ts}</td>
      <td>{$revenue_recognition_date|crmDate:"%B, %Y"}</td>
    </tr>
  {/if}
  {if $to_financial_account}
    <tr class="crm-contribution-form-block-to_financial_account">
      <td class="label">{ts}Received Into{/ts}</td>
      <td>{$to_financial_account}</td>
    </tr>
  {/if}
  {if empty($is_template)}
  <tr class="crm-contribution-form-block-contribution_status_id">
    <td class="label">{ts}Contribution Status{/ts}</td>
    <td {if $contribution_status_id eq 3} class="font-red bold"{/if}>{$contribution_status}
      {if $contribution_status_id eq 2} {if $is_pay_later}: {ts}Pay Later{/ts} {else} : {ts}Incomplete Transaction{/ts} {/if}{/if}</td>
  </tr>
  {/if}

  {if $cancel_date}
    <tr class="crm-contribution-form-block-cancel_date">
      <td class="label">{ts}Cancelled / Refunded Date{/ts}</td>
      <td>{$cancel_date|crmDate}</td>
    </tr>
    {if $cancel_reason}
      <tr class="crm-contribution-form-block-cancel_reason">
        <td class="label">{ts}Cancellation / Refund Reason{/ts}</td>
        <td>{$cancel_reason}</td>
      </tr>
    {/if}
    {if $refund_trxn_id}
      <tr class="crm-contribution-form-block-refund_trxn_id">
        <td class="label">{ts}Refund Transaction ID{/ts}</td>
        <td>{$refund_trxn_id}</td>
      </tr>
    {/if}
  {/if}
  <tr class="crm-contribution-form-block-payment_instrument">
    <td class="label">{ts}Payment Method{/ts}</td>
    <td>{$payment_instrument}{if $payment_processor_name} ({$payment_processor_name}){/if}</td>
  </tr>

  {if $check_number}
    <tr class="crm-contribution-form-block-check_number">
      <td class="label">{ts}Check Number{/ts}</td>
      <td>{$check_number}</td>
    </tr>
  {/if}

  {if $campaign}
    <tr class="crm-contribution-form-block-campaign">
      <td class="label">{ts}Campaign{/ts}</td>
      <td>{$campaign}</td>
    </tr>
  {/if}

  {if $contribution_page_title}
    <tr class="crm-contribution-form-block-contribution_page_title">
      <td class="label">{ts}Online Contribution Page{/ts}</td>
      <td>{$contribution_page_title}</td>
    </tr>
  {/if}
  {if $receipt_date}
    <tr class="crm-contribution-form-block-receipt_date">
      <td class="label">{ts}Receipt Sent{/ts}</td>
      <td>{$receipt_date|crmDate}</td>
    </tr>
  {/if}
  {foreach from=$note item="rec"}
    {if $rec}
      <tr class="crm-contribution-form-block-note">
        <td class="label">{ts}Note{/ts}</td>
        <td>{$rec}</td>
      </tr>
    {/if}
  {/foreach}
  <tr class="crm-contribution-form-block-id">
    <td class="label">{ts}Contribution ID{/ts}</td>
    <td>{$id}</td>
  </tr>
  {if $invoice_number}
    <tr class="crm-contribution-form-block-invoice_number">
      <td class="label">{ts}Invoice Number{/ts}</td>
      <td>{$invoice_number}&nbsp;</td>
    </tr>
  {/if}

  {if $invoice_id}
    <tr class="crm-contribution-form-block-invoice_id">
      <td class="label">{ts}Invoice Reference{/ts}</td>
      <td>{$invoice_id}&nbsp;</td>
    </tr>
  {/if}

  {if $thankyou_date}
    <tr class="crm-contribution-form-block-thankyou_date">
      <td class="label">{ts}Thank-you Sent{/ts}</td>
      <td>{$thankyou_date|crmDate}</td>
    </tr>
  {/if}
</table>

{if empty($is_template)}
  <h3>{ts}Payment Details{/ts}</h3>
  {include file="CRM/Contribute/Form/PaymentInfoBlock.tpl"}
{/if}

{if $softContributions && count($softContributions)} {* We show soft credit name with PCP section if contribution is linked to a PCP. *}
  <details class="crm-accordion-bold crm-soft-credit-pane" open>
    <summary>
      {ts}Soft Credit{/ts}
    </summary>
    <div class="crm-accordion-body">
      <table class="crm-info-panel crm-soft-credit-listing">
        {foreach from=$softContributions item="softCont"}
          <tr>
            <td>
              <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$softCont.contact_id`"}"
                 title="{ts escape='htmlattribute'}View contact record{/ts}">{$softCont.contact_name}
              </a>
            </td>
            <td>{$softCont.amount|crmMoney:$currency}
              {if $softCont.soft_credit_type_label}
                ({$softCont.soft_credit_type_label})
              {/if}
            </td>
          </tr>
        {/foreach}
      </table>
    </div>
  </details>
{/if}

{if $premium}
  <details class="crm-accordion-bold " open>
    <summary>
      {ts}Premium Information{/ts}
    </summary>
    <div class="crm-accordion-body">
      <table class="crm-info-panel">
        <td class="label">{ts}Premium{/ts}</td>
        <td>{$premium}</td>
        <td class="label">{ts}Option{/ts}</td>
        <td>{$option}</td>
        <td class="label">{ts}Fulfilled{/ts}</td>
        <td>{if $fulfilled}{$fulfilled|truncate:10:''|crmDate}{else}{ts}No{/ts}{/if}</td>
      </table>
    </div>
  </details>
{/if}

{if $pcp_id}
  <details id='PCPView' class="crm-accordion-bold " open>
    <summary>
      {ts}Personal Campaign Page Contribution Information{/ts}
    </summary>
    <div class="crm-accordion-body">
      <table class="crm-info-panel">
        <tr>
          <td class="label">{ts}Personal Campaign Page{/ts}</td>
          <td><a href="{crmURL p="civicrm/pcp/info" q="reset=1&id=`$pcp_id`"}">{$pcp_title}</a><br/>
            <span class="description">{ts}Contribution was made through this personal campaign page.{/ts}</span>
          </td>
        </tr>
        <tr>
          <td class="label">{ts}Soft Credit To{/ts}</td>
          <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$pcp_soft_credit_to_id`"}" id="view_contact"
                 title="{ts escape='htmlattribute'}View contact record{/ts}">{$pcp_soft_credit_to_name}</a></td>
        </tr>
        <tr>
          <td class="label">{ts}In Public Honor Roll?{/ts}</td>
          <td>{if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
        </tr>
        {if $pcp_roll_nickname}
          <tr>
            <td class="label">{ts}Honor Roll Name{/ts}</td>
            <td>{$pcp_roll_nickname}</td>
          </tr>
        {/if}
        {if $pcp_personal_note}
          <tr>
            <td class="label">{ts}Personal Note{/ts}</td>
            <td>{$pcp_personal_note}</td>
          </tr>
        {/if}
      </table>
    </div>
  </details>
{/if}

{include file="CRM/Custom/Page/CustomDataView.tpl"}

{if $billing_address}
  <fieldset>
    <legend>{ts}Billing Address{/ts}</legend>
    <div class="form-item">
      {$billing_address|nl2br}
    </div>
  </fieldset>
{/if}

<div id="payment-info"></div>
{include file="CRM/Contribute/Page/PaymentInfo.tpl" show='payments'}

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
{crmScript file='js/crm.expandRow.js'}
