{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="crm-block crm-content-block crm-contribution-view-form-block">
<div class="action-link">
  <div class="crm-submit-buttons">
    {if (call_user_func(array('CRM_Core_Permission','check'), 'edit contributions') && call_user_func(array('CRM_Core_Permission', 'check'), "edit contributions of type $financial_type") && $canEdit) ||
    	(call_user_func(array('CRM_Core_Permission','check'), 'edit contributions') && $noACL)}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context"}
      {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
        {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&key=$searchKey"}
      {/if}
      <a class="button" href="{crmURL p='civicrm/contact/view/contribution' q=$urlParams}" accesskey="e"><span>
          <i class="crm-i fa-pencil"></i> {ts}Edit{/ts}</span>
      </a>
    {/if}
    {if (call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute') && call_user_func(array('CRM_Core_Permission', 'check'), "delete contributions of type $financial_type") && $canDelete)     || (call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute') && $noACL)}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context"}
      {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
        {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&key=$searchKey"}
      {/if}
      <a class="button" href="{crmURL p='civicrm/contact/view/contribution' q=$urlParams}"><span>
          <i class="crm-i fa-trash"></i> {ts}Delete{/ts}</span>
      </a>
    {/if}
    {include file="CRM/common/formButtons.tpl" location="top"}
    {assign var='pdfUrlParams' value="reset=1&id=$id&cid=$contact_id"}
    {assign var='emailUrlParams' value="reset=1&id=$id&cid=$contact_id&select=email"}
    {if $invoicing}
      <div class="css_right">
        <a class="button no-popup" href="{crmURL p='civicrm/contribute/invoice' q=$pdfUrlParams}">
          <i class="crm-i fa-print"></i>
        {if $contribution_status != 'Refunded' && $contribution_status != 'Cancelled' }
          {ts}Print Invoice{/ts}</a>
        {else}
          {ts}Print Invoice and Credit Note{/ts}</a>
        {/if}
        <a class="button" href="{crmURL p='civicrm/contribute/invoice/email' q=$emailUrlParams}">
          <i class="crm-i fa-paper-plane"></i>
          {ts}Email Invoice{/ts}</a>
      </div>
    {/if}
  </div>
</div>
<table class="crm-info-panel">
  <tr>
    <td class="label">{ts}From{/ts}</td>
    <td class="bold">{$displayName}</td>
  </tr>
  <tr>
    <td class="label">{ts}Financial Type{/ts}</td>
    <td>{$financial_type}{if $is_test} {ts}(test){/ts} {/if}</td>
  </tr>
  {if $lineItem}
    <tr>
      <td class="label">{ts}Contribution Amount{/ts}</td>
      <td>{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
        {if $contribution_recur_id}
          <strong>{ts}Recurring Contribution{/ts}</strong>
          <br/>
          {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
        {/if}
      </td>
    </tr>
  {else}
    <tr>
      <td class="label">{ts}Total Amount{/ts}</td>
      <td><strong>{$total_amount|crmMoney:$currency}</strong>&nbsp;
        {if $contribution_recur_id}
          <strong>{ts}Recurring Contribution{/ts}</strong>
          <br/>
          {ts}Installments{/ts}: {if $recur_installments}{$recur_installments}{else}{ts}(ongoing){/ts}{/if}, {ts}Interval{/ts}: {$recur_frequency_interval} {$recur_frequency_unit}(s)
        {/if}
      </td>
    </tr>
  {/if}
  {if $invoicing && $tax_amount}
    <tr>
      <td class="label">{ts}Total Tax Amount{/ts}</td>
      <td>{$tax_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $non_deductible_amount}
    <tr>
      <td class="label">{ts}Non-deductible Amount{/ts}</td>
      <td>{$non_deductible_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $fee_amount}
    <tr>
      <td class="label">{ts}Fee Amount{/ts}</td>
      <td>{$fee_amount|crmMoney:$currency}</td>
    </tr>
  {/if}
  {if $net_amount}
    <tr>
      <td class="label">{ts}Net Amount{/ts}</td>
      <td>{$net_amount|crmMoney:$currency}</td>
    </tr>
  {/if}

  <tr>
    <td class="label">{ts}Received{/ts}</td>
    <td>{if $receive_date}{$receive_date|crmDate}{else}({ts}not available{/ts}){/if}</td>
  </tr>
  {if $to_financial_account }
    <tr>
      <td class="label">{ts}Received Into{/ts}</td>
      <td>{$to_financial_account}</td>
    </tr>
  {/if}
  <tr>
    <td class="label">{ts}Contribution Status{/ts}</td>
    <td {if $contribution_status_id eq 3} class="font-red bold"{/if}>{$contribution_status}
      {if $contribution_status_id eq 2} {if $is_pay_later}: {ts}Pay Later{/ts} {else} : {ts}Incomplete Transaction{/ts} {/if}{/if}</td>
  </tr>

  {if $cancel_date}
    <tr>
      <td class="label">{ts}Cancelled / Refunded Date{/ts}</td>
      <td>{$cancel_date|crmDate}</td>
    </tr>
    {if $cancel_reason}
      <tr>
        <td class="label">{ts}Cancellation / Refund Reason{/ts}</td>
        <td>{$cancel_reason}</td>
      </tr>
    {/if}
    {if $refund_trxn_id}
      <tr>
        <td class="label">{ts}Refund Transaction ID{/ts}</td>
        <td>{$refund_trxn_id}</td>
      </tr>
    {/if}
  {/if}
  <tr>
    <td class="label">{ts}Payment Method{/ts}</td>
    <td>{$payment_instrument}{if $payment_processor_name} ({$payment_processor_name}){/if}</td>
  </tr>

  {if $payment_instrument eq 'Check'|ts}
    <tr>
      <td class="label">{ts}Check Number{/ts}</td>
      <td>{$check_number}</td>
    </tr>
  {/if}
  <tr>
    <td class="label">{ts}Source{/ts}</td>
    <td>{$source}</td>
  </tr>

  {if $campaign}
    <tr>
      <td class="label">{ts}Campaign{/ts}</td>
      <td>{$campaign}</td>
    </tr>
  {/if}

  {if $contribution_page_title}
    <tr>
      <td class="label">{ts}Online Contribution Page{/ts}</td>
      <td>{$contribution_page_title}</td>
    </tr>
  {/if}
  {if $receipt_date}
    <tr>
      <td class="label">{ts}Receipt Sent{/ts}</td>
      <td>{$receipt_date|crmDate}</td>
    </tr>
  {/if}
  {foreach from=$note item="rec"}
    {if $rec }
      <tr>
        <td class="label">{ts}Note{/ts}</td>
        <td>{$rec}</td>
      </tr>
    {/if}
  {/foreach}

  {if $trxn_id}
    <tr>
      <td class="label">{ts}Transaction ID{/ts}</td>
      <td>{$trxn_id}</td>
    </tr>
  {/if}

  {if $invoice_id}
    <tr>
      <td class="label">{ts}Invoice ID{/ts}</td>
      <td>{$invoice_id}&nbsp;</td>
    </tr>
  {/if}

  {if $thankyou_date}
    <tr>
      <td class="label">{ts}Thank-you Sent{/ts}</td>
      <td>{$thankyou_date|crmDate}</td>
    </tr>
  {/if}
</table>

{if count($softContributions)} {* We show soft credit name with PCP section if contribution is linked to a PCP. *}
  <div class="crm-accordion-wrapper crm-soft-credit-pane">
    <div class="crm-accordion-header">
      {ts}Soft Credit{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="crm-info-panel crm-soft-credit-listing">
        {foreach from=$softContributions item="softCont"}
          <tr>
            <td>
              <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$softCont.contact_id`"}"
                 title="{ts}View contact record{/ts}">{$softCont.contact_name}
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
  </div>
{/if}

{if $premium}
  <div class="crm-accordion-wrapper ">
    <div class="crm-accordion-header">
      {ts}Premium Information{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="crm-info-panel">
        <td class="label">{ts}Premium{/ts}</td>
        <td>{$premium}</td>
        <td class="label">{ts}Option{/ts}</td>
        <td>{$option}</td>
        <td class="label">{ts}Fulfilled{/ts}</td>
        <td>{$fulfilled|truncate:10:''|crmDate}</td>
      </table>
    </div>
  </div>
{/if}

{if $pcp_id}
  <div id='PCPView' class="crm-accordion-wrapper ">
    <div class="crm-accordion-header">
      {ts}Personal Campaign Page Contribution Information{/ts}
    </div>
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
                 title="{ts}View contact record{/ts}">{$pcp_soft_credit_to_name}</a></td>
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
  </div>
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

<div class="crm-submit-buttons">
  {if (call_user_func(array('CRM_Core_Permission','check'), 'edit contributions') && call_user_func(array('CRM_Core_Permission', 'check'), "edit contributions of type $financial_type") && $canEdit) ||
    	(call_user_func(array('CRM_Core_Permission','check'), 'edit contributions') && $noACL)}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context"}
    {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&key=$searchKey"}
    {/if}
    <a class="button" href="{crmURL p='civicrm/contact/view/contribution' q=$urlParams}" accesskey="e"><span><i class="crm-i fa-pencil"></i> {ts}Edit{/ts}</span></a>
  {/if}
  {if (call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute') && call_user_func(array('CRM_Core_Permission', 'check'), "delete contributions of type $financial_type") && $canDelete)     || (call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviContribute') && $noACL)}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context"}
    {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
      {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&key=$searchKey"}
    {/if}
    <a class="button" href="{crmURL p='civicrm/contact/view/contribution' q=$urlParams}"><span><i class="crm-i fa-trash"></i> {ts}Delete{/ts}</span></a>
  {/if}
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
