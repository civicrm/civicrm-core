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
      <p>
        {if {contribution.contribution_page_id.receipt_text|boolean}}{contribution.contribution_page_id.receipt_text}
        {elseif {contribution.paid_amount|boolean}}{ts}Below you will find a receipt for this contribution.{/ts}{/if}
      </p>
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
            <td>
              {$line.title}
            </td>
            <td>
             {$line.qty}
            </td>
            <td>
             {$line.unit_price|crmMoney:'{contribution.currency}'}
            </td>
            {if $isShowTax && {contribution.tax_amount|boolean}}
              <td>
                {$line.line_total|crmMoney:'{contribution.currency}'}
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
             {$line.line_total_inclusive|crmMoney:'{contribution.currency}'}
            </td>
           </tr>
          {/foreach}
         </table>
        </td>
       </tr>

     {/if}
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
        {ts}Contribution Date{/ts}
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

     {if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
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
