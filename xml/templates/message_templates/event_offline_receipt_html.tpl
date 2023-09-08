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

    {if !empty($event.confirm_email_text) AND (empty($isOnWaitlist) AND empty($isRequireApproval))}
     <p>{$event.confirm_email_text}</p>
    {/if}

    {if !empty($isOnWaitlist)}
      <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
      <p>{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}</p>
    {elseif !empty($isRequireApproval)}
      <p>{ts}Your registration has been submitted.{/ts}</p>
      <p>{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</p>
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
       {event.title}<br />
       {event.start_date|crmDate}{if {event.end_date|boolean}}-{if '{event.end_date|crmDate:"%Y%m%d"}' === '{event.start_date|crmDate:"%Y%m%d"}'}{event.end_date|crmDate:"Time"}{else}{event.end_date}{/if}{/if}
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

     {if {event.loc_block_id.phone_id.phone|boolean} || {event.loc_block_id.email_id.email|boolean}}
      <tr>
       <td colspan="2" {$labelStyle}>
        {ts}Event Contacts:{/ts}
       </td>
      </tr>

       {if {event.loc_block_id.phone_id.phone|boolean}}
        <tr>
         <td {$labelStyle}>
          {if {event.loc_block_id.phone_id.phone_type_id|boolean}}
            {event.loc_block_id.phone_id.phone_type_id:label}
          {else}
           {ts}Phone{/ts}
          {/if}
         </td>
         <td {$valueStyle}>
          {event.loc_block_id.phone_id.phone} {if {event.loc_block_id.phone_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_id.phone_ext}{/if}
         </td>
        </tr>
       {/if}
         {if {event.loc_block_id.phone_2_id.phone|boolean}}
           <tr>
             <td {$labelStyle}>
                 {if {event.loc_block_id.phone_2_id.phone_type_id|boolean}}
                     {event.loc_block_id.phone_2_id.phone_type_id:label}
                 {else}
                     {ts}Phone{/ts}
                 {/if}
             </td>
             <td {$valueStyle}>
                 {event.loc_block_id.phone_2_id.phone} {if {event.loc_block_id.phone_2_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_2_id.phone_ext}{/if}
             </td>
           </tr>
         {/if}


       {if {event.loc_block_id.email_id.email|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Email{/ts}
         </td>
         <td {$valueStyle}>
             {event.loc_block_id.email_id.email}
         </td>
        </tr>
       {/if}

       {if {event.loc_block_id.email_2_id.email|boolean}}
         <tr>
           <td {$labelStyle}>
               {ts}Email{/ts}
           </td>
           <td {$valueStyle}>
               {event.loc_block_id.email_2_id.email}
           </td>
         </tr>
       {/if}

     {/if}

     {if {event.is_public|boolean}}
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


     {if {event.is_monetary|boolean}}
      <tr>
       <th {$headerStyle}>
         {event.fee_label}
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
                   {if $isShowLineSubtotal}<th>{ts}SubTotal{/ts}</th>{/if}
                   <th>{ts}Tax Rate{/ts}</th>
                   <th>{ts}Tax Amount{/ts}</th>
                 {/if}
                 <th>{ts}Total{/ts}</th>
                 {if $isShowParticipantCount}
                   <th>{ts}Total Participants{/ts}</th>
                 {/if}
               </tr>
                 {foreach from=$participants key=index item=participant}
                   {* Display if it is the current participant or this is the primary participant *}
                   {if $isPrimary || {participant.id} === $participant.id}
                     {foreach from=$participant.line_items item=line}
                       <tr>
                         <td>{$line.title}</td>
                         <td>{$line.qty}</td>
                         <td>{$line.unit_price|crmMoney:$currency}</td>
                         {if $isShowTax && {contribution.tax_amount|boolean}}
                           {if $isShowLineSubtotal}<td>{$line.line_total|crmMoney:$currency}</td>{/if}
                           <td>{$line.tax_rate|string_format:"%.2f"}%</td>
                           <td>{$line.tax_amount|crmMoney:$currency}</td>
                         {/if}
                         <td>{$line.line_total+$line.tax_amount|crmMoney}</td>
                         {if $isShowParticipantCount}
                           <td>{$line.participant_count}</td>
                         {/if}
                       </tr>
                     {/foreach}
                   {/if}
                 {/foreach}
             </table>
           </td>
         </tr>
       {/if}
       {foreach from=$participants key=index item=participant}
         {if !$isPrimary && {participant.id} === $participant.id}
           {* Use the participant specific tax rate breakdown *}
           {assign var=taxRateBreakdown value=$participant.tax_rate_breakdown}
         {/if}
       {/foreach}
       {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
         <tr>
           <td {$labelStyle}>{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if}</td>
           <td {$valueStyle}>{$taxDetail.amount|crmMoney:'{contribution.currency}'}</td>
         </tr>
       {/foreach}

      {if {contribution.tax_amount|boolean}}
       <tr>
        <td {$labelStyle}>
         {ts}Total Tax Amount{/ts}
        </td>
        <td {$valueStyle}>
            {if $isPrimary}{contribution.tax_amount}{else}{$participantDetail.totals.tax_amount|crmMoney}{/if}
        </td>
       </tr>
      {/if}
      {if {event.is_monetary|boolean}}
      {if $isPrimary && {contribution.balance_amount|boolean}}
         <tr>
           <td {$labelStyle}>{ts}Total Paid{/ts}</td>
           <td {$valueStyle}>
             {contribution.paid_amount} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
           </td>
          </tr>
          <tr>
           <td {$labelStyle}>{ts}Balance{/ts}</td>
           <td {$valueStyle}>{contribution.balance_amount}</td>
         </tr>
        {else}
         <tr>
           <td {$labelStyle}>{ts}Total Amount{/ts}</td>
           <td {$valueStyle}>
             {if $isPrimary}{contribution.total_amount}{else}{$participantDetail.totals.total_amount_inclusive|crmMoney:$currency}{/if} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
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

       {if {participant.register_date|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Registration Date{/ts}
         </td>
         <td {$valueStyle}>
           {participant.register_date}
         </td>
        </tr>
       {/if}

       {if {contribution.receive_date|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Transaction Date{/ts}
         </td>
         <td {$valueStyle}>
           {contribution.receive_date}
         </td>
        </tr>
       {/if}

       {if {contribution.financial_type_id|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Financial Type{/ts}
         </td>
         <td {$valueStyle}>
           {contribution.financial_type_id:label}
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

       {if {contribution.payment_instrument_id|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Paid By{/ts}
         </td>
         <td {$valueStyle}>
           {contribution.payment_instrument_id:label}
         </td>
        </tr>
       {/if}

       {if {contribution.check_number|boolean}}
        <tr>
         <td {$labelStyle}>
          {ts}Check Number{/ts}
         </td>
         <td {$valueStyle}>
           {contribution.check_number}
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
