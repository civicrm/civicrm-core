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
{capture assign=tdfirstStyle}style="width: 180px; padding-bottom: 15px;"{/capture}
{capture assign=tdStyle}style="width: 100px;"{/capture}
{capture assign=participantTotal}style="margin: 0.5em 0 0.5em;padding: 0.5em;background-color: #999999;font-weight: bold;color: #FAFAFA;border-radius: 2px;"{/capture}


  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
     {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}

    {if !empty($event.confirm_email_text) AND (empty($isOnWaitlist) AND empty($isRequireApproval))}
     <p>{$event.confirm_email_text|htmlize}</p>

    {else}
     <p>{ts}Thank you for your registration.{/ts}
     {if $participant_status}{ts 1=$participant_status}This is a confirmation that your registration has been received and your status has been updated to <strong> %1</strong>.{/ts}
     {else}{if $isOnWaitlist}{ts}This is a confirmation that your registration has been received and your status has been updated to <strong>waitlisted</strong>.{/ts}{else}{ts}This is a confirmation that your registration has been received and your status has been updated to <strong>registered<strong>.{/ts}{/if}{/if}</p>

    {/if}

    <p>
    {if !empty($isOnWaitlist)}
     <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
     {if $isPrimary}
       <p>{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}</p>
     {/if}
    {elseif !empty($isRequireApproval)}
     <p>{ts}Your registration has been submitted.{/ts}</p>
     {if $isPrimary}
      <p>{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</p>
     {/if}
    {elseif !empty($is_pay_later) && empty($isAmountzero) && empty($isAdditionalParticipant)}
     <p>{if {event.pay_later_receipt|boolean}}{event.pay_later_receipt|boolean}{/if}</p> {* FIXME: this might be text rather than HTML *}
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table style="width:100%; max-width:700px; border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">
     <tr>
      <th {$headerStyle}>
       {ts}Event Information and Location{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       {event.title}<br />
       {event.start_date|crmDate:"%A"} {event.start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|crmDate:"%Y%m%d" == $event.event_start_date|crmDate:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate:"%A"} {$event.event_end_date|crmDate}{/if}{/if}
      </td>
     </tr>


     {if $conference_sessions}
      <tr>
       <td colspan="2" {$labelStyle}>
  {ts}Your schedule:{/ts}
       </td>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
  {assign var='group_by_day' value='NA'}
  {foreach from=$conference_sessions item=session}
   {if $session.start_date|crmDate:"%Y/%m/%d" != $group_by_day|crmDate:"%Y/%m/%d"}
    {assign var='group_by_day' value=$session.start_date}
          <em>{$group_by_day|crmDate:"%m/%d/%Y"}</em><br />
   {/if}
   {$session.start_date|crmDate:0:1}{if $session.end_date}-{$session.end_date|crmDate:0:1}{/if} {$session.title}<br />
   {if $session.location}&nbsp;&nbsp;&nbsp;&nbsp;{$session.location}<br />{/if}
  {/foreach}
       </td>
      </tr>
     {/if}

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

     {if {event.is_public|boolean}}
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
        <a href="{$icalFeed}">{ts}Download iCalendar entry for this event.{/ts}</a>
       </td>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
        <a href="{$gCalendar}">{ts}Add event to Google Calendar{/ts}</a>
       </td>
      </tr>
     {/if}

    {if $event.is_share}
      <tr>
        <td colspan="2" {$valueStyle}>
          {capture assign=eventUrl}{crmURL p='civicrm/event/info' q="id={event.id}&reset=1" a=true fe=1 h=1}{/capture}
          {include file="CRM/common/SocialNetwork.tpl" emailMode=true url=$eventUrl pageURL=$eventUrl title='{event.title}'}
        </td>
      </tr>
    {/if}
    {if !empty($payer.name)}
     <tr>
       <th {$headerStyle}>
         {ts}You were registered by:{/ts}
       </th>
     </tr>
     <tr>
       <td colspan="2" {$valueStyle}>
        {$payer.name}
       </td>
     </tr>
    {/if}
    {if {event.is_monetary|boolean} and empty($isRequireApproval)}

      <tr>
       <th {$headerStyle}>
        {event.fee_label}
       </th>
      </tr>

      {if $isShowLineItems}
        {foreach from=$participants key=index item=participant}
          {if $isPrimary || {participant.id} === $participant.id}
          {if $isPrimary && $lineItems|@count GT 1} {* Header for multi participant registration cases. *}
            <tr>
              <td colspan="2" {$labelStyle}>
                {ts 1=$participant.index}Participant %1{/ts} {$participant.contact.display_name}
              </td>
            </tr>
          {/if}
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
                      {if !empty($pricesetFieldsCount)}<th>{ts}Total Participants{/ts}</th>{/if}
                  </tr>
                  {foreach from=$participant.line_items item=line}
                    <tr>
                      <td {$tdfirstStyle}>{$line.title}</td>
                      <td {$tdStyle} align="middle">{$line.qty}</td>
                      <td {$tdStyle}>{$line.unit_price|crmMoney:$currency}</td>
                      {if $line.tax_rate || $line.tax_amount != ""}
                        <td>{$line.tax_rate|string_format:"%.2f"}%</td>
                        <td>{$line.tax_amount|crmMoney:$currency}</td>
                      {else}
                        <td></td>
                        <td></td>
                      {/if}
                      <td {$tdStyle}>
                        {$line.line_total+$line.tax_amount|crmMoney:$currency}
                      </td>
                      {if !empty($pricesetFieldsCount)}<td {$tdStyle}>{$line.participant_count}</td> {/if}
                    </tr>
                  {/foreach}
                  {if $isShowTax}
                    <tr {$participantTotal}>
                      <td colspan=3>{ts}Participant Total{/ts}</td>
                      <td colspan=2>{$participant.totals.total_amount_exclusive|crmMoney}</td>
                      <td colspan=1>{$participant.totals.tax_amount|crmMoney}</td>
                      <td colspan=2>{$participant.totals.total_amount_inclusive|crmMoney}</td>
                    </tr>
                  {/if}
                </table>
              </td>
            </tr>
          {/if}
        {/foreach}
        {/if}
        {if $isShowTax && {contribution.tax_amount|boolean}}
          <tr>
            <td {$labelStyle}>
              {ts}Amount Before Tax:{/ts}
            </td>
            <td {$valueStyle}>
              {if $isPrimary}{contribution.tax_exclusive_amount}{else}{$participant.totals.total_amount_exclusive|crmMoney}{/if}
            </td>
        </tr>

            {if !$isPrimary}
              {* Use the participant specific tax rate breakdown *}
              {assign var=taxRateBreakdown value=$participant.tax_rate_breakdown}
            {/if}
            {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
              <tr>
                <td {$labelStyle}>{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if}</td>
                <td {$valueStyle}>{$taxDetail.amount|crmMoney:'{contribution.currency}'}</td>
              </tr>
            {/foreach}
        {/if}

      {if !empty($amounts) && empty($lineItem)}
       {foreach from=$amounts item=amnt key=level}
        <tr>
         <td colspan="2" {$valueStyle}>
          {$amnt.amount|crmMoney:$currency} {$amnt.label}
         </td>
        </tr>
       {/foreach}
      {/if}

      {if $isShowTax && {contribution.tax_amount|boolean}}
       <tr>
        <td {$labelStyle}>
         {ts}Total Tax Amount{/ts}
        </td>
        <td {$valueStyle}>
          {if $isPrimary}{contribution.tax_amount}{else}{$participant.totals.tax_amount|crmMoney}{/if}
        </td>
       </tr>
      {/if}
      {if $isPrimary}
       <tr>
        <td {$labelStyle}>
         {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
          {contribution.total_amount} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
        </td>
       </tr>
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
        {assign var="lineItemCount" value=1}
      {/if}
      {assign var="count" value=$count+$lineItemCount}
      {/if}
      {/foreach}
     {$count}
     </td> </tr>
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
{foreach from=$customPre item=customPr key=i}
   <tr> <th {$headerStyle}>{$customPre_grouptitle.$i}</th></tr>
   {foreach from=$customPr item=customValue key=customName}
   {if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
     <tr>
         <td {$labelStyle}>{$customName}</td>
         <td {$valueStyle}>{$customValue}</td>
     </tr>
   {/if}
   {/foreach}
{/foreach}
{/if}

{if !empty($customPost)}
{foreach from=$customPost item=customPos key=j}
   <tr> <th {$headerStyle}>{$customPost_grouptitle.$j}</th></tr>
   {foreach from=$customPos item=customValue key=customName}
   {if (!empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
     <tr>
         <td {$labelStyle}>{$customName}</td>
         <td {$valueStyle}>{$customValue}</td>
     </tr>
{/if}
{/foreach}
{/foreach}
{/if}

{if !empty($customProfile)}
{foreach from=$customProfile.profile item=eachParticipant key=participantID}
     <tr><th {$headerStyle}>{ts 1=$participantID+2}Participant %1{/ts} </th></tr>
     {foreach from=$eachParticipant item=eachProfile key=pid}
     <tr><th {$headerStyle}>{$customProfile.title.$pid}</th></tr>
     {foreach from=$eachProfile item=val key=field}
     <tr>{foreach from=$val item=v key=f}
         <td {$labelStyle}>{$field}</td>
         <td {$valueStyle}>{$v}</td>
         {/foreach}
     </tr>
     {/foreach}
{/foreach}
{/foreach}
{/if}

    </table>
    {if !empty($event.allow_selfcancelxfer) }
     <tr>
      <td colspan="2" {$valueStyle}>
        {ts 1=$selfcancelxfer_time 2=$selfservice_preposition}You may transfer your registration to another participant or cancel your registration up to %1 hours %2 the event.{/ts} {if !empty($totalAmount)}{ts}Cancellations are not refundable.{/ts}{/if}<br />
        {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`{participant.id}`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
        <a href="{$selfService}">{ts}Click here to transfer or cancel your registration.{/ts}</a>
      </td>
     </tr>
    {/if}
 </table>

</body>
</html>
