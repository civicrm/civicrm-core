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
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    {if $formValues.receipt_text_signup}
     <p>{$formValues.receipt_text_signup|htmlize}</p>
    {elseif $formValues.receipt_text_renewal}
     <p>{$formValues.receipt_text_renewal|htmlize}</p>
    {else}
     <p>{ts}Thank you for your support.{/ts}</p>
    {/if}
    {if ! $cancelled}
     <p>{ts}Please print this receipt for your records.{/ts}</p>
    {/if}
   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     {if !$lineItem}
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
     {/if}
     {if ! $cancelled}
     {if !$lineItem}
      <tr>
       <td {$labelStyle}>
        {ts}Membership Start Date{/ts}
       </td>
       <td {$valueStyle}>
        {$mem_start_date}
       </td>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Membership End Date{/ts}
       </td>
       <td {$valueStyle}>
        {$mem_end_date}
       </td>
      </tr>
      {/if}
      {if $formValues.total_amount OR $formValues.total_amount eq 0 }
       <tr>
        <th {$headerStyle}>
         {ts}Membership Fee{/ts}
        </th>
       </tr>
       {if $formValues.contributionType_name}
        <tr>
         <td {$labelStyle}>
          {ts}Financial Type{/ts}
         </td>
         <td {$valueStyle}>
          {$formValues.contributionType_name}
         </td>
        </tr>
       {/if}

       {if $lineItem}
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
          {ts}Amount Before Tax : {/ts}
         </td>
         <td {$valueStyle}>
          {$formValues.total_amount-$totalTaxAmount|crmMoney}
         </td>
        </tr>
       {foreach from=$dataArray item=value key=priceset}
        <tr>
        {if $priceset}
         <td>&nbsp;{$taxTerm} {$priceset|string_format:"%.2f"}%</td>
         <td>&nbsp;{$value|crmMoney:$currency}</td>
        {elseif  $priceset == 0}
         <td>&nbsp;{ts}No{/ts} {$taxTerm}</td>
         <td>&nbsp;{$value|crmMoney:$currency}</td>
        {/if}
        </tr>
       {/foreach}
      {/if}
      {/if}
      {if isset($totalTaxAmount)}
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
         {$formValues.total_amount|crmMoney}
        </td>
       </tr>
       {if $receive_date}
        <tr>
         <td {$labelStyle}>
          {ts}Received Date{/ts}
         </td>
         <td {$valueStyle}>
          {$receive_date|truncate:10:''|crmDate}
         </td>
        </tr>
       {/if}
       {if $formValues.paidBy}
        <tr>
         <td {$labelStyle}>
          {ts}Paid By{/ts}
         </td>
         <td {$valueStyle}>
          {$formValues.paidBy}
         </td>
        </tr>
        {if $formValues.check_number}
         <tr>
          <td {$labelStyle}>
           {ts}Check Number{/ts}
          </td>
          <td {$valueStyle}>
           {$formValues.check_number}
          </td>
         </tr>
        {/if}
       {/if}
      {/if}
     {/if}
    </table>
   </td>
  </tr>

  {if $isPrimary}
   <tr>
    <td>
     <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">

      {if $contributeMode ne 'notify' and !$isAmountzero and !$is_pay_later }
       <tr>
        <th {$headerStyle}>
         {ts}Billing Name and Address{/ts}
        </th>
       </tr>
       <tr>
        <td {$labelStyle}>
         {$billingName}<br />
         {$address}
        </td>
       </tr>
      {/if}

      {if $contributeMode eq 'direct' and !$isAmountzero and !$is_pay_later}
       <tr>
        <th {$headerStyle}>
         {ts}Credit Card Information{/ts}
        </th>
       </tr>
       <tr>
        <td {$valueStyle}>
         {$credit_card_type}<br />
         {$credit_card_number}
        </td>
       </tr>
       <tr>
        <td {$labelStyle}>
         {ts}Expires{/ts}
        </td>
        <td {$valueStyle}>
         {$credit_card_exp_date|truncate:7:''|crmDate}
        </td>
       </tr>
      {/if}

     </table>
    </td>
   </tr>
  {/if}

  {if $customValues}
   <tr>
    <td>
     <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
      <tr>
       <th {$headerStyle}>
        {ts}Membership Options{/ts}
       </th>
      </tr>
      {foreach from=$customValues item=value key=customName}
       <tr>
        <td {$labelStyle}>
         {$customName}
        </td>
        <td {$valueStyle}>
         {$value}
        </td>
       </tr>
      {/foreach}
     </table>
    </td>
   </tr>
  {/if}

 </table>
</center>

</body>
</html>
