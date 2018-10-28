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
 <table width="500" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
     {assign var="greeting" value="{contact.email_greeting}"}{if $greeting}<p>{$greeting},</p>{/if}
    {if $receipt_text}
     <p>{$receipt_text|htmlize}</p>
    {/if}

    {if $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {else}
     <p>{ts}Please print this confirmation for your records.{/ts}</p>
    {/if}

   </td>
  </tr>
  </table>
  <table width="500" style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">

     {if $membership_assign && !$useForMember}
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

      {if !$useForMember and $membership_amount and $is_quick_config}

       <tr>
        <td {$labelStyle}>
         {ts 1=$membership_name}%1 Membership{/ts}
        </td>
        <td {$valueStyle}>
         {$membership_amount|crmMoney}
        </td>
       </tr>
       {if $amount && !$is_separate_payment }
         <tr>
          <td {$labelStyle}>
           {ts}Contribution Amount{/ts}
          </td>
          <td {$valueStyle}>
           {$amount|crmMoney}
          </td>
         </tr>
         <tr>
           <td {$labelStyle}>
           {ts}Total{/ts}
            </td>
            <td {$valueStyle}>
            {$amount+$membership_amount|crmMoney}
           </td>
         </tr>
       {/if}

      {elseif !$useForMember && $lineItem and $priceSetID and !$is_quick_config}

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
       {if $useForMember && $lineItem and !$is_quick_config}
       {foreach from=$lineItem item=value key=priceset}
        <tr>
         <td colspan="2" {$valueStyle}>
          <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
           <tr>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Fee{/ts}</th>
            {if $dataArray}
              <th>{ts}SubTotal{/ts}</th>
              <th>{ts}Tax Rate{/ts}</th>
              <th>{ts}Tax Amount{/ts}</th>
              <th>{ts}Total{/ts}</th>
            {/if}
      <th>{ts}Membership Start Date{/ts}</th>
      <th>{ts}Membership End Date{/ts}</th>
           </tr>
           {foreach from=$value item=line}
            <tr>
             <td>
             {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description}<div>{$line.description|truncate:30:"..."}</div>{/if}
             </td>
             <td>
              {$line.line_total|crmMoney}
             </td>
             {if $dataArray}
              <td>
               {$line.unit_price*$line.qty|crmMoney}
              </td>
              {if $line.tax_rate != "" || $line.tax_amount != ""}
               <td>
                {$line.tax_rate|string_format:"%.2f"}%
               </td>
               <td>
                {$line.tax_amount|crmMoney}
               </td>
              {else}
               <td></td>
               <td></td>
              {/if}
              <td>
               {$line.line_total+$line.tax_amount|crmMoney}
              </td>
             {/if}
             <td>
              {$line.start_date}
             </td>
       <td>
              {$line.end_date}
             </td>
            </tr>
           {/foreach}
          </table>
         </td>
        </tr>
       {/foreach}
       {if $dataArray}
        <tr>
         <td {$labelStyle}>
          {ts}Amount Before Tax:{/ts}
         </td>
         <td {$valueStyle}>
          {$amount-$totalTaxAmount|crmMoney}
         </td>
        </tr>
        {foreach from=$dataArray item=value key=priceset}
         <tr>
         {if $priceset || $priceset == 0}
           <td>&nbsp;{$taxTerm} {$priceset|string_format:"%.2f"}%</td>
           <td>&nbsp;{$value|crmMoney:$currency}</td>
         {else}
           <td>&nbsp;{ts}NO{/ts} {$taxTerm}</td>
           <td>&nbsp;{$value|crmMoney:$currency}</td>
         {/if}
         </tr>
        {/foreach}
       {/if}
       {/if}
       {if $totalTaxAmount}
        <tr>
         <td {$labelStyle}>
          {ts}Total Tax Amount{/ts}
         </td>
         <td {$valueStyle}>
          {$totalTaxAmount|crmMoney:$currency}
         </td>
        </tr>
       {/if}
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
      {if $contributeMode eq 'notify' or $contributeMode eq 'directIPN'}
       <tr>
        <td colspan="2" {$labelStyle}>
         {ts 1=$cancelSubscriptionUrl}This membership will be renewed automatically. You can cancel the auto-renewal option by <a href="%1">visiting this web page</a>.{/ts}
        </td>
       </tr>
       {if $updateSubscriptionBillingUrl}
         <tr>
          <td colspan="2" {$labelStyle}>
           {ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}
          </td>
         </tr>
       {/if}
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

     {if $onBehalfProfile}
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
        {$customPre_grouptitle}
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
        {$customPost_grouptitle}
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
</center>

</body>
</html>
