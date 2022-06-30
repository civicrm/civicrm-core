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
    {assign var="greeting" value="{contact.email_greeting}"}{if $greeting}<p>{$greeting},</p>{/if}

    {if !empty($event.confirm_email_text) AND (empty($isOnWaitlist) AND empty($isRequireApproval))}
     <p>{$event.confirm_email_text|htmlize}</p>
    {/if}

    {if !empty($isOnWaitlist)}
     <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
     {if !empty($isPrimary)}
       <p>{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}</p>
     {/if}
    {elseif !empty($isRequireApproval)}
     <p>{ts}Your registration has been submitted.{/ts}</p>
     {if !empty($isPrimary)}
      <p>{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</p>
     {/if}
    {elseif $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Event Information and Location{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       {$event.event_title}<br />
       {$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate}{/if}{/if}
      </td>
     </tr>

     {if !empty($event.participant_role) and $event.participant_role neq 'Attendee' and !empty($defaultRole)}
      <tr>
       <td {$labelStyle}>
        {ts}Participant Role{/ts}
       </td>
       <td {$valueStyle}>
        {$event.participant_role}
       </td>
      </tr>
     {/if}

     {if !empty($isShowLocation)}
      <tr>
       <td colspan="2" {$valueStyle}>
        {$location.address.1.display|nl2br}
       </td>
      </tr>
     {/if}

     {if !empty($location.phone.1.phone) || !empty($location.email.1.email)}
      <tr>
       <td colspan="2" {$labelStyle}>
        {ts}Event Contacts:{/ts}
       </td>
      </tr>
      {foreach from=$location.phone item=phone}
       {if $phone.phone}
        <tr>
         <td {$labelStyle}>
          {if $phone.phone_type}
           {$phone.phone_type_display}
          {else}
           {ts}Phone{/ts}
          {/if}
         </td>
         <td {$valueStyle}>
          {$phone.phone} {if $phone.phone_ext}&nbsp;{ts}ext.{/ts} {$phone.phone_ext}{/if}
         </td>
        </tr>
       {/if}
      {/foreach}
      {foreach from=$location.email item=eventEmail}
       {if $eventEmail.email}
        <tr>
         <td {$labelStyle}>
          {ts}Email{/ts}
         </td>
         <td {$valueStyle}>
          {$eventEmail.email}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if !empty($event.is_public)}
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
        <a href="{$icalFeed}">{ts}Download iCalendar entry for this event.{/ts}</a>
       </td>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
         <a href="{$gCalendar}">{ts}Add event to Google Calendar{/ts}</a>
       </td>
      </tr>
     {/if}

     {if $email}
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


     {if !empty($event.is_monetary)}

      <tr>
       <th {$headerStyle}>
        {if !empty($event.fee_label)}{$event.fee_label}{/if}
       </th>
      </tr>

      {if !empty($lineItem)}
       {foreach from=$lineItem item=value key=priceset}
        {if $value neq 'skip'}
         {if !empty($isPrimary)}
          {if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
           <tr>
            <td colspan="2" {$labelStyle}>
             {ts 1=$priceset+1}Participant %1{/ts}
            </td>
           </tr>
          {/if}
         {/if}
         <tr>
          <td colspan="2" {$valueStyle}>
           <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
            <tr>
             <th>{ts}Item{/ts}</th>
             <th>{ts}Qty{/ts}</th>
             <th>{ts}Each{/ts}</th>
             {if !empty($dataArray)}
              <th>{ts}SubTotal{/ts}</th>
              <th>{ts}Tax Rate{/ts}</th>
              <th>{ts}Tax Amount{/ts}</th>
             {/if}
             <th>{ts}Total{/ts}</th>
       {if !empty($pricesetFieldsCount) }<th>{ts}Total Participants{/ts}</th>{/if}
            </tr>
            {foreach from=$value item=line}
             <tr>
              <td>
        {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description}<div>{$line.description|truncate:30:"..."}</div>{/if}
              </td>
              <td>
               {$line.qty}
              </td>
              <td>
               {$line.unit_price|crmMoney}
              </td>
              {if !empty($dataArray)}
               <td>
                {$line.unit_price*$line.qty|crmMoney}
               </td>
               {if $line.tax_rate || $line.tax_amount != ""}
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
              {/if}
              <td>
               {$line.line_total+$line.tax_amount|crmMoney}
              </td>
        {if  !empty($pricesetFieldsCount) }
        <td>
    {$line.participant_count}
              </td>
        {/if}
             </tr>
            {/foreach}
           </table>
          </td>
         </tr>
        {/if}
       {/foreach}
       {if !empty($dataArray)}
        {if $totalAmount and $totalTaxAmount}
        <tr>
         <td {$labelStyle}>
          {ts}Amount Before Tax:{/ts}
         </td>
         <td {$valueStyle}>
          {$totalAmount-$totalTaxAmount|crmMoney}
         </td>
        </tr>
        {/if}
        {foreach from=$dataArray item=value key=priceset}
          <tr>
           {if $priceset || $priceset == 0}
            <td>&nbsp;{$taxTerm} {$priceset|string_format:"%.2f"}%</td>
            <td>&nbsp;{$value|crmMoney:$currency}</td>
           {else}
            <td>&nbsp;{ts}No{/ts} {$taxTerm}</td>
            <td>&nbsp;{$value|crmMoney:$currency}</td>
           {/if}
          </tr>
        {/foreach}
       {/if}
      {/if}

      {if !empty($amount) && !$lineItem}
       {foreach from=$amount item=amnt key=level}
        <tr>
         <td colspan="2" {$valueStyle}>
          {$amnt.amount|crmMoney} {$amnt.label}
         </td>
        </tr>
       {/foreach}
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
      {if !empty($isPrimary)}
       <tr>
        <td {$labelStyle}>
        {if isset($balanceAmount)}
           {ts}Total Paid{/ts}
        {else}
           {ts}Total Amount{/ts}
         {/if}
        </td>
        <td {$valueStyle}>
         {if !empty($totalAmount)}{$totalAmount|crmMoney}{/if} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
        </td>
       </tr>
      {if isset($balanceAmount)}
       <tr>
        <td {$labelStyle}>
         {ts}Balance{/ts}
        </td>
        <td {$valueStyle}>
         {$balanceAmount|crmMoney}
        </td>
       </tr>
      {/if}
       {if !empty($pricesetFieldsCount) }
     <tr>
       <td {$labelStyle}>
   {ts}Total Participants{/ts}</td>
       <td {$valueStyle}>
   {assign var="count" value= 0}
         {foreach from=$lineItem item=pcount}
         {assign var="lineItemCount" value=0}
         {if $pcount neq 'skip'}
           {foreach from=$pcount item=p_count}
           {assign var="lineItemCount" value=$lineItemCount+$p_count.participant_count}
           {/foreach}
           {if $lineItemCount < 1 }
           assign var="lineItemCount" value=1}
           {/if}
           {assign var="count" value=$count+$lineItemCount}
         {/if}
         {/foreach}
   {$count}
       </td>
     </tr>
     {/if}
       {if $is_pay_later}
        <tr>
         <td colspan="2" {$labelStyle}>
          {$pay_later_receipt}
         </td>
        </tr>
       {/if}

       {if $register_date}
        <tr>
         <td {$labelStyle}>
          {ts}Registration Date{/ts}
         </td>
         <td {$valueStyle}>
          {$register_date|crmDate}
         </td>
        </tr>
       {/if}

       {if !empty($receive_date)}
        <tr>
         <td {$labelStyle}>
          {ts}Transaction Date{/ts}
         </td>
         <td {$valueStyle}>
          {$receive_date|crmDate}
         </td>
        </tr>
       {/if}

       {if !empty($financialTypeName)}
        <tr>
         <td {$labelStyle}>
          {ts}Financial Type{/ts}
         </td>
         <td {$valueStyle}>
          {$financialTypeName}
         </td>
        </tr>
       {/if}

       {if !empty($trxn_id)}
        <tr>
         <td {$labelStyle}>
          {ts}Transaction #{/ts}
         </td>
         <td {$valueStyle}>
          {$trxn_id}
         </td>
        </tr>
       {/if}

       {if !empty($paidBy)}
        <tr>
         <td {$labelStyle}>
          {ts}Paid By{/ts}
         </td>
         <td {$valueStyle}>
         {$paidBy}
         </td>
        </tr>
       {/if}

       {if !empty($checkNumber)}
        <tr>
         <td {$labelStyle}>
          {ts}Check Number{/ts}
         </td>
         <td {$valueStyle}>
          {$checkNumber}
         </td>
        </tr>
       {/if}

       {if !empty($billingName)}
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
          {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
         </td>
        </tr>
       {/if}

      {/if}

     {/if} {* End of conditional section for Paid events *}

     {if !empty($customPre)}
      <tr>
       <th {$headerStyle}>
        {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=value key=customName}
       {if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$value}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if !empty($customPost)}
      <tr>
       <th {$headerStyle}>
        {$customPost_grouptitle}
       </th>
      </tr>
      {foreach from=$customPost item=value key=customName}
       {if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
        <tr>
         <td {$labelStyle}>
          {$customName}
         </td>
         <td {$valueStyle}>
          {$value}
         </td>
        </tr>
       {/if}
      {/foreach}
     {/if}

     {if !empty($customProfile)}
      {foreach from=$customProfile item=value key=customName}
       <tr>
        <th {$headerStyle}>
         {ts 1=$customName+1}Participant Information - Participant %1{/ts}
        </th>
       </tr>
       {foreach from=$value item=val key=field}
        {if $field eq 'additionalCustomPre' or $field eq 'additionalCustomPost'}
         <tr>
          <td colspan="2" {$labelStyle}>
           {if $field eq 'additionalCustomPre'}
            {$additionalCustomPre_grouptitle}
           {else}
            {$additionalCustomPost_grouptitle}
           {/if}
          </td>
         </tr>
         {foreach from=$val item=v key=f}
          <tr>
           <td {$labelStyle}>
            {$f}
           </td>
           <td {$valueStyle}>
            {$v}
           </td>
          </tr>
         {/foreach}
        {/if}
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

    </table>
   </td>
  </tr>

 </table>

</body>
</html>
