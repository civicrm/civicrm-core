<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- BEGIN HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>

    {if $receipt_text}
     <p>{$receipt_text}</p>
    {/if}

    {if $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {else}
     <p>{ts}Please print this confirmation for your records.{/ts}</p>
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">

     {if $membership_assign}
      <tr>
       <th {$headerStyle}>
        {ts}Membership Information{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Membership Type{/ts}
       </td>
       <td {$valueStyle}>
        {$membership_name}
       </td>
      </tr>
      {if $mem_start_date}
       <tr>
        <td {$labelStyle}>
         {ts}Membership Start Date{/ts}
        </td>
        <td {$valueStyle}>
         {$mem_start_date|crmDate}
        </td>
       </tr>
      {/if}
      {if $mem_end_date}
       <tr>
        <td {$labelStyle}>
         {ts}Membership End Date{/ts}
        </td>
        <td {$valueStyle}>
          {$mem_end_date|crmDate}
        </td>
       </tr>
      {/if}
     {/if}


     {if $amount}


      <tr>
       <th {$headerStyle}>
        {ts}Membership Fee{/ts}
       </th>
      </tr>

      {if $membership_amount}

       <tr>
        <td {$labelStyle}>
         {ts 1=$membership_name}%1 Membership{/ts}
        </td>
        <td {$valueStyle}>
         {$membership_amount|crmMoney}
        </td>
       </tr>
       {if $amount}
        {if ! $is_separate_payment }
         <tr>
          <td {$labelStyle}>
           {ts}Contribution Amount{/ts}
          </td>
          <td {$valueStyle}>
           {$amount|crmMoney}
          </td>
         </tr>
        {else}
         <tr>
          <td {$labelStyle}>
           {ts}Additional Contribution{/ts}
          </td>
          <td {$valueStyle}>
           {$amount|crmMoney}
          </td>
         </tr>
        {/if}
       {/if}
       <tr>
        <td {$labelStyle}>
         {ts}Total{/ts}
        </td>
        <td {$valueStyle}>
         {$amount+$membership_amount|crmMoney}
        </td>
       </tr>

      {elseif $lineItem and $priceSetID}

       {foreach from=$lineItem item=value key=priceset}
        <tr>
         <td colspan="2" {$valueStyle}>
          <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
           <tr>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Qty{/ts}</th>
            <th>{ts}Each{/ts}</th>
            <th>{ts}Total{/ts}</th>
           </tr>
           {foreach from=$value item=line}
            <tr>
             <td>
              {$line.description|truncate:30:"..."}
             </td>
             <td>
              {$line.qty}
             </td>
             <td>
              {$line.unit_price|crmMoney}
             </td>
             <td>
              {$line.line_total|crmMoney}
             </td>
            </tr>
           {/foreach}
          </table>
         </td>
        </tr>
       {/foreach}
       <tr>
        <td {$labelStyle}>
         {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$amount|crmMoney}
        </td>
       </tr>

      {else}

       <tr>
        <td {$labelStyle}>
         {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$amount|crmMoney} {if $amount_level} - {$amount_level}{/if}
        </td>
       </tr>

      {/if}


     {elseif $membership_amount}


      <tr>
       <th {$headerStyle}>
        {ts}Membership Fee{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts 1=$membership_name}%1 Membership{/ts}
       </td>
       <td {$valueStyle}>
        {$membership_amount|crmMoney}
       </td>
      </tr>


     {/if}


     {if $receive_date}
      <tr>
       <td {$labelStyle}>
        {ts}Date{/ts}
       </td>
       <td {$valueStyle}>
        {$receive_date|crmDate}
       </td>
      </tr>
     {/if}

     {if $is_monetary and $trxn_id}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction #{/ts}
       </td>
       <td {$valueStyle}>
        {$trxn_id}
       </td>
      </tr>
     {/if}

     {if $membership_trx_id}
      <tr>
       <td {$labelStyle}>
        {ts}Membership Transaction #{/ts}
       </td>
       <td {$valueStyle}>
        {$membership_trx_id}
       </td>
      </tr>
     {/if}

     {if $is_recur}
      {if $contributeMode eq 'notify'}
       <tr>
        <td {$labelStyle}>
         {ts 1=$cancelSubscriptionUrl}This is a recurring contribution. You can modify or cancel future contributions by <a href="%1">logging in to your account</a>.{/ts}
        </td>
       </tr>
      {elseif $contributeMode eq 'directIPN' and $receiptFromEmail}
       <tr>
        <td {$labelStyle}>
         {ts 1=$receiptFromEmail}This is a recurring contribution. To modify or cancel future contributions please contact us at %1.{/ts}
        </td>
       </tr>
      {/if}
     {/if}

     {if $honor_block_is_active}
      <tr>
       <th {$headerStyle}>
        {$honor_type}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$labelStyle}>
        {$honor_prefix} {$honor_first_name} {$honor_last_name}
       </td>
      </tr>
      {if $honor_email}
       <tr>
        <td {$labelStyle}>
         {ts}Honoree Email{/ts}
        </td>
        <td {$valueStyle}>
         {$honor_email}
        </td>
       </tr>
      {/if}
     {/if}

     {if $pcpBlock}
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

     {if $onBehalfName}
      <tr>
       <th {$headerStyle}>
        {ts}On Behalf Of{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$onBehalfName}<br />
        {$onBehalfAddress|nl2br}<br />
        {$onBehalfEmail}
       </td>
      </tr>
     {/if}

     {if ! ($contributeMode eq 'notify' OR $contributeMode eq 'directIPN') and $is_monetary}
      {if $is_pay_later}
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
      {elseif $amount GT 0 OR $membership_amount GT 0}
       <tr>
        <th {$headerStyle}>
         {ts}Billing Name and Address{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valueStyle}>
         {$billingName}<br />
         {$address|nl2br}<br />
         {$email}
        </td>
       </tr>
      {/if}
     {/if}

     {if $contributeMode eq 'direct' AND !$is_pay_later AND ($amount GT 0 OR $membership_amount GT 0)}
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

     {if $selectPremium}
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
      {if $contact_email OR $contact_phone}
       <tr>
        <td colspan="2" {$valueStyle}>
         <p>{ts}For information about this premium, contact:{/ts}</p>
         {if $contact_email}
          <p>{$contact_email}</p>
         {/if}
         {if $contact_phone}
          <p>{$contact_phone}</p>
         {/if}
        </td>
       </tr>
      {/if}
      {if $is_deductible AND $price}
        <tr>
         <td colspan="2" {$valueStyle}>
          <p>{ts 1=$price|crmMoney}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}</p>
         </td>
        </tr>
      {/if}
     {/if}

     {if $customPre}
      <tr>
       <th {$headerStyle}>
        {ts}{$customPre_grouptitle} {/ts}
       </th>
      </tr>
      {foreach from=$customPre item=customValue key=customName}
       {if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if $customPost}
      <tr>
       <th {$headerStyle}>
        {ts}{$customPost_grouptitle}{/ts}
       </th>
      </tr>
      {foreach from=$customPost item=customValue key=customName}
       {if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$customValue}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

    </table>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
