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

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
    {if !empty($formValues.receipt_text)}
     <p>{$formValues.receipt_text|htmlize}</p>
    {else}
     <p>{ts}Below you will find a receipt for this contribution.{/ts}</p>
    {/if}
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
       {ts}Contributor Name{/ts}
      </td>
      <td {$valueStyle}>
       {contact.display_name}
      </td>
     </tr>
     <tr>
      {if '{contribution.financial_type_id}'}
        <td {$labelStyle}>
         {ts}Financial Type{/ts}
        </td>
        <td {$valueStyle}>
         {contribution.financial_type_id:label}
        </td>
      {/if}
     </tr>

     {if $isShowLineItems}
       <tr>
        <td colspan="2" {$valueStyle}>
         <table>
          <tr>
           <th>{ts}Item{/ts}</th>
           <th>{ts}Qty{/ts}</th>
           <th>{ts}Each{/ts}</th>
           {if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'}
             <th>{ts}Subtotal{/ts}</th>
             <th>{ts}Tax Rate{/ts}</th>
             <th>{ts}Tax Amount{/ts}</th>
           {/if}
           <th>{ts}Total{/ts}</th>
          </tr>
          {foreach from=$lineItems item=line}
           <tr>
            <td>
              {$line.title}
            </td>
            <td>
             {$line.qty}
            </td>
            <td>
             {$line.unit_price|crmMoney:'{contribution.currency}'}
            </td>
            {if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'}
              <td>
                {$line.unit_price*$line.qty|crmMoney:'{contribution.currency}'}
              </td>
              {if $line.tax_rate || $line.tax_amount != ""}
                <td>
                  {$line.tax_rate|string_format:"%.2f"}%
                </td>
                <td>
                  {$line.tax_amount|crmMoney:'{contribution.currency}'}
                </td>
              {else}
                <td></td>
                <td></td>
              {/if}
            {/if}
            <td>
             {$line.line_total+$line.tax_amount|crmMoney:'{contribution.currency}'}
            </td>
           </tr>
          {/foreach}
         </table>
        </td>
       </tr>

     {/if}
     {if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'}
       <tr>
         <td {$labelStyle}>
           {ts} Amount before Tax : {/ts}
         </td>
         <td {$valueStyle}>
           {$formValues.total_amount-$totalTaxAmount|crmMoney:'{contribution.currency}'}
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
          {ts}Total Tax Amount{/ts}
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

     {if '{contribution.receive_date}'}
       <tr>
       <td {$labelStyle}>
        {ts}Date Received{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.receive_date|crmDate:"shortdate"}
       </td>
      </tr>
     {/if}

      {if '{contribution.receipt_date}'}
      <tr>
       <td {$labelStyle}>
        {ts}Receipt Date{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.receipt_date|crmDate:"shortdate"}
       </td>
      </tr>
     {/if}

     {if '{contribution.payment_instrument_id}' and empty($formValues.hidden_CreditCard)}
      <tr>
       <td {$labelStyle}>
        {ts}Paid By{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.payment_instrument_id:label}
       </td>
      </tr>
      {if '{contribution.check_number}'}
       <tr>
        <td {$labelStyle}>
         {ts}Check Number{/ts}
        </td>
        <td {$valueStyle}>
          {contribution.check_number}
        </td>
       </tr>
      {/if}
     {/if}

     {if '{contribution.trxn_id}'}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction ID{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.trxn_id}
       </td>
      </tr>
     {/if}

     {if !empty($ccContribution)}
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

     {if !empty($softCreditTypes) and !empty($softCredits)}
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

     {if !empty($customGroup)}
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

     {if !empty($formValues.product_name)}
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
      {if !empty($fulfilled_date)}
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

</body>
</html>
