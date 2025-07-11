<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle}style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle}style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

  <!-- BEGIN HEADER -->
    {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
    {site.message_header}
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
   <td>
     {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
     {if $userText}
       <p>{$userText}</p>
     {elseif {contribution.contribution_page_id.receipt_text|boolean}}
       <p>{contribution.contribution_page_id.receipt_text}</p>
     {/if}

    {if $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {/if}

   </td>
  </tr>
</table>
<table style="width:100%; max-width:500px; border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">
  {if {contribution.total_amount|boolean}}
    <tr>
      <th {$headerStyle}>
        {ts}Contribution Information{/ts}
      </th>
    </tr>

    {if $isShowLineItems}
      <tr>
        <td colspan="2" {$valueStyle}>
          <table>
            <tr>
              <th>{ts}Item{/ts}</th>
              <th>{ts}Qty{/ts}</th>
              <th>{ts}Each{/ts}</th>
              {if $isShowTax && {contribution.tax_amount|boolean}}
                <th>{ts}Subtotal{/ts}</th>
                <th>{ts}Tax Rate{/ts}</th>
                <th>{ts}Tax Amount{/ts}</th>
              {/if}
              <th>{ts}Total{/ts}</th>
            </tr>
            {foreach from=$lineItems item=line}
              <tr>
                <td>{$line.title}</td>
                <td>{$line.qty}</td>
                <td>{$line.unit_price|crmMoney:$currency}</td>
                {if $isShowTax && {contribution.tax_amount|boolean}}
                  <td>{$line.line_total|crmMoney:$currency}</td>
                  {if $line.tax_rate || $line.tax_amount != ""}
                    <td>{$line.tax_rate|string_format:"%.2f"}%</td>
                    <td>{$line.tax_amount|crmMoney:$currency}</td>
                  {else}
                    <td></td>
                    <td></td>
                  {/if}
                {/if}
                <td>
                  {$line.line_total_inclusive|crmMoney:$currency}
                </td>
              </tr>
            {/foreach}
          </table>
        </td>
      </tr>

      {if $isShowTax && {contribution.tax_amount|boolean}}
        <tr>
          <td {$labelStyle}>
            {ts} Amount before Tax : {/ts}
          </td>
          <td {$valueStyle}>
            {contribution.tax_exclusive_amount}
          </td>
        </tr>

        {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
          <tr>
            <td>{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if}</td>
            <td>{$taxDetail.amount|crmMoney:'{contribution.currency}'}</td>
          </tr>
        {/foreach}

      {/if}
      {if $isShowTax}
        <tr>
          <td {$labelStyle}>
            {ts}Total Tax{/ts}
          </td>
          <td {$valueStyle}>
            {contribution.tax_amount}
          </td>
        </tr>
      {/if}
      <tr>
        <td {$labelStyle}>
          {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
          {contribution.total_amount}
        </td>
      </tr>
    {else}
      {if {contribution.tax_amount|boolean}}
        <tr>
          <td {$labelStyle}>
            {ts}Total Tax Amount{/ts}
          </td>
          <td {$valueStyle}>
            {contribution.tax_amount}
          </td>
         </tr>
       {/if}
       <tr>
        <td {$labelStyle}>
         {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
         {contribution.total_amount} {if '{contribution.amount_level}'} - {contribution.amount_level}{/if}
        </td>
       </tr>

      {/if}

  {/if}


     {if !empty($receive_date)}
      <tr>
       <td {$labelStyle}>
        {ts}Date{/ts}
       </td>
       <td {$valueStyle}>
        {$receive_date|crmDate}
       </td>
      </tr>
     {/if}

     {if {contribution.trxn_id|boolean}}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction #{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.trxn_id}
       </td>
      </tr>
     {/if}

    {if !empty($is_recur)}
      <tr>
        <td  colspan="2" {$labelStyle}>
          {ts}This is a recurring contribution.{/ts}
          {if $cancelSubscriptionUrl}
            {ts 1=$cancelSubscriptionUrl}You can cancel future contributions by <a href="%1">visiting this web page</a>.{/ts}
          {/if}
        </td>
      </tr>
      {if $updateSubscriptionBillingUrl}
        <tr>
          <td colspan="2" {$labelStyle}>
            {ts 1=$updateSubscriptionBillingUrl}You can update billing details for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}
          </td>
        </tr>
      {/if}
      {if $updateSubscriptionUrl}
        <tr>
          <td colspan="2" {$labelStyle}>
            {ts 1=$updateSubscriptionUrl}You can update recurring contribution amount or change the number of installments for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}
          </td>
        </tr>
      {/if}
    {/if}

     {if $honor_block_is_active}
      <tr>
       <th {$headerStyle}>
        {$soft_credit_type}
       </th>
      </tr>
      {foreach from=$honoreeProfile item=value key=label}
        <tr>
         <td {$labelStyle}>
          {$label}
         </td>
         <td {$valueStyle}>
          {$value}
         </td>
        </tr>
      {/foreach}
      {elseif !empty($softCreditTypes) and !empty($softCredits)}
      {foreach from=$softCreditTypes item=softCreditType key=n}
       <tr>
        <th {$headerStyle}>
         {$softCreditType}
        </th>
       </tr>
       {foreach from=$softCredits.$n item=value key=label}
         <tr>
          <td {$labelStyle}>
           {$label}
          </td>
          <td {$valueStyle}>
           {$value}
          </td>
         </tr>
        {/foreach}
       {/foreach}
     {/if}

     {if !empty($pcpBlock)}
      <tr>
       <th {$headerStyle}>
        {ts}Personal Campaign Page{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Display In Honor Roll{/ts}
       </td>
       <td {$valueStyle}>
        {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
       </td>
      </tr>
      {if $pcp_roll_nickname}
       <tr>
        <td {$labelStyle}>
         {ts}Nickname{/ts}
        </td>
        <td {$valueStyle}>
         {$pcp_roll_nickname}
        </td>
       </tr>
      {/if}
      {if $pcp_personal_note}
       <tr>
        <td {$labelStyle}>
         {ts}Personal Note{/ts}
        </td>
        <td {$valueStyle}>
         {$pcp_personal_note}
        </td>
       </tr>
      {/if}
     {/if}

     {if !empty($onBehalfProfile)}
      <tr>
       <th {$headerStyle}>
        {$onBehalfProfile_grouptitle}
       </th>
      </tr>
      {foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
        <tr>
         <td {$labelStyle}>
          {$onBehalfName}
         </td>
         <td {$valueStyle}>
          {$onBehalfValue}
         </td>
        </tr>
      {/foreach}
     {/if}

     {if {contribution.contribution_page_id.is_share|boolean}}
      <tr>
        <td colspan="2" {$valueStyle}>
            {capture assign=contributionUrl}{crmURL p='civicrm/contribute/transact' q="reset=1&id={contribution.contribution_page_id}" a=true fe=1 h=1}{/capture}
            {include file="CRM/common/SocialNetwork.tpl" emailMode=true url=$contributionUrl title=$title pageURL=$contributionUrl}
        </td>
      </tr>
     {/if}

     {if {contribution.address_id.display|boolean}}
       <tr>
        <th {$headerStyle}>
         {ts}Billing Address{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
          {contribution.address_id.name}<br/>
          {contribution.address_id.display}
         {$email}
        </td>
       </tr>
     {elseif !empty($email)}
       <tr>
        <th {$headerStyle}>
         {ts}Registered Email{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
         {$email}
        </td>
       </tr>
     {/if}

     {if !empty($credit_card_type)}
      <tr>
       <th {$headerStyle}>
        {ts}Credit Card Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$credit_card_type}<br />
        {$credit_card_number}<br />
        {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}<br />
       </td>
      </tr>
     {/if}

     {if !empty($selectPremium)}
      <tr>
       <th {$headerStyle}>
        {ts}Premium Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$labelStyle}>
        {$product_name}
       </td>
      </tr>
      {if $option}
       <tr>
        <td {$labelStyle}>
         {ts}Option{/ts}
        </td>
        <td {$valueStyle}>
         {$option}
        </td>
       </tr>
      {/if}
      {if $sku}
       <tr>
        <td {$labelStyle}>
         {ts}SKU{/ts}
        </td>
        <td {$valueStyle}>
         {$sku}
        </td>
       </tr>
      {/if}
      {if $start_date}
       <tr>
        <td {$labelStyle}>
         {ts}Start Date{/ts}
        </td>
        <td {$valueStyle}>
         {$start_date|crmDate}
        </td>
       </tr>
      {/if}
      {if $end_date}
       <tr>
        <td {$labelStyle}>
         {ts}End Date{/ts}
        </td>
        <td {$valueStyle}>
         {$end_date|crmDate}
        </td>
       </tr>
      {/if}
      {if !empty($contact_email) OR !empty($contact_phone)}
       <tr>
        <td colspan="2" {$valueStyle}>
         <p>{ts}For information about this premium, contact:{/ts}</p>
         {if !empty($contact_email)}
          <p>{$contact_email}</p>
         {/if}
         {if !empty($contact_phone)}
          <p>{$contact_phone}</p>
         {/if}
        </td>
       </tr>
      {/if}
      {if $is_deductible AND !empty($price)}
        <tr>
         <td colspan="2" {$valueStyle}>
          <p>{ts 1=$price|crmMoney:$currency}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}</p>
         </td>
        </tr>
      {/if}
     {/if}

     {if !empty($customPre)}
      <tr>
       <th {$headerStyle}>
        {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=customValue key=customName}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
      {/foreach}
     {/if}

     {if !empty($customPost)}
      <tr>
       <th {$headerStyle}>
        {$customPost_grouptitle}
       </th>
      </tr>
      {foreach from=$customPost item=customValue key=customName}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
      {/foreach}
     {/if}

  </table>

</body>
</html>
