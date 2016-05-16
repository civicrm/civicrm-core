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

    {if $formValues.receipt_text}
     <p>{$formValues.receipt_text|htmlize}</p>
    {else}
     <p>{ts}Thanks for your support.{/ts}</p>
    {/if}

    <p>{ts}Please print this receipt for your records.{/ts}</p>

   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Contribution Information{/ts}
      </th>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Contribution Type{/ts}
      </td>
      <td {$valueStyle}>
       {$formValues.contributionType_name}
      </td>
     </tr>

     {if $lineItem}
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
     {/if}

     <tr>
      <td {$labelStyle}>
       {ts}Total Amount{/ts}
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

     {if $formValues.paidBy and !$formValues.hidden_CreditCard}
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

     {if $formValues.trxn_id}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction ID{/ts}
       </td>
       <td {$valueStyle}>
        {$formValues.trxn_id}
       </td>
      </tr>
     {/if}

     {if $ccContribution}
      <tr>
       <th {$headerStyle}>
        {ts}Billing Name and Address{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$billingName}<br />
        {$address|nl2br}
       </td>
      </tr>
      <tr>
       <th {$headerStyle}>
        {ts}Credit Card Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$credit_card_type}<br />
        {$credit_card_number}<br />
        {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
       </td>
      </tr>
     {/if}

     {if $customGroup}
      {foreach from=$customGroup item=value key=customName}
       <tr>
        <th {$headerStyle}>
         {$customName}
        </th>
       </tr>
       {foreach from=$value item=v key=n}
        <tr>
         <td {$labelStyle}>
          {$n}
         </td>
         <td {$valueStyle}>
          {$v}
         </td>
        </tr>
       {/foreach}
      {/foreach}
     {/if}

     {if $formValues.honor_first_name}
      <tr>
       <th {$headerStyle}>
        {$formValues.honor_type}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$formValues.honor_prefix} {$formValues.honor_first_name} {$formValues.honor_last_name}<br />
        {if $formValues.honor_email}
         {ts}Honoree Email{/ts}: {$formValues.honor_email}
        {/if}
       </td>
      </tr>
     {/if}

     {if $formValues.product_name}
      <tr>
       <th {$headerStyle}>
        {ts}Premium Information{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$labelStyle}>
        {$formValues.product_name}
       </td>
      </tr>
      {if $formValues.product_option}
       <tr>
        <td {$labelStyle}>
         {ts}Option{/ts}
        </td>
        <td {$valueStyle}>
         {$formValues.product_option}
        </td>
       </tr>
      {/if}
      {if $formValues.product_sku}
       <tr>
        <td {$labelStyle}>
         {ts}SKU{/ts}
        </td>
        <td {$valueStyle}>
         {$formValues.product_sku}
        </td>
       </tr>
      {/if}
      {if $fulfilled_date}
       <tr>
        <td {$labelStyle}>
         {ts}Sent{/ts}
        </td>
        <td {$valueStyle}>
         {$fulfilled_date|truncate:10:''|crmDate}
        </td>
       </tr>
      {/if}
     {/if}

    </table>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
