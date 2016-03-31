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


<center>
 <table width="700" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
  <p>{contact.email_greeting},</p>

    {if $event.confirm_email_text AND (not $isOnWaitlist AND not $isRequireApproval)}
     <p>{$event.confirm_email_text|htmlize}</p>

    {else}
  <p>Thank you for your participation.  This letter is a confirmation that your registration has been received and your status has been updated to <strong>{if $participant_status}{$participant_status}{else}{if $isOnWaitlist}waitlisted{else}registered{/if}{/if}</strong>.</p>

    {/if}

    <p>
    {if $isOnWaitlist}
     <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
     {if $isPrimary}
       <p>{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}</p>
     {/if}
    {elseif $isRequireApproval}
     <p>{ts}Your registration has been submitted.{/ts}</p>
     {if $isPrimary}
      <p>{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}</p>
     {/if}
    {elseif $is_pay_later && !$isAmountzero && !$isAdditionalParticipant}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {else}
     <p>{ts}Please print this confirmation for your records.{/ts}</p>
    {/if}

   </td>
  </tr>
  <tr>
   <td>
    <table width="700" style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">
     <tr>
      <th {$headerStyle}>
       {ts}Event Information and Location{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       {$event.event_title}<br />
       {$event.event_start_date|date_format:"%A"} {$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|date_format:"%A"} {$event.event_end_date|crmDate}{/if}{/if}
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
   {if $session.start_date|date_format:"%Y/%m/%d" != $group_by_day|date_format:"%Y/%m/%d"}
    {assign var='group_by_day' value=$session.start_date}
          <em>{$group_by_day|date_format:"%m/%d/%Y"}</em><br />
   {/if}
   {$session.start_date|crmDate:0:1}{if $session.end_date}-{$session.end_date|crmDate:0:1}{/if} {$session.title}<br />
   {if $session.location}&nbsp;&nbsp;&nbsp;&nbsp;{$session.location}<br />{/if}
  {/foreach}
       </td>
      </tr>
     {/if}

     {if $event.participant_role neq 'Attendee' and $defaultRole}
      <tr>
       <td {$labelStyle}>
        {ts}Participant Role{/ts}
       </td>
       <td {$valueStyle}>
        {$event.participant_role}
       </td>
      </tr>
     {/if}

     {if $isShowLocation}
      <tr>
       <td colspan="2" {$valueStyle}>
        {if $location.address.1.name}
         {$location.address.1.name}<br />
        {/if}
        {if $location.address.1.street_address}
         {$location.address.1.street_address}<br />
        {/if}
        {if $location.address.1.supplemental_address_1}
         {$location.address.1.supplemental_address_1}<br />
        {/if}
        {if $location.address.1.supplemental_address_2}
         {$location.address.1.supplemental_address_2}<br />
        {/if}
        {if $location.address.1.city}
         {$location.address.1.city}, {$location.address.1.state_province} {$location.address.1.postal_code}{if $location.address.1.postal_code_suffix} - {$location.address.1.postal_code_suffix}{/if}<br />
        {/if}
       </td>
      </tr>
     {/if}

     {if $location.phone.1.phone || $location.email.1.email}
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

     {if $event.is_public}
      <tr>
       <td colspan="2" {$valueStyle}>
        {capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
        <a href="{$icalFeed}">{ts}Download iCalendar File{/ts}</a>
       </td>
      </tr>
     {/if}

    {if $event.is_share}
        <tr>
            <td colspan="2" {$valueStyle}>
                {capture assign=eventUrl}{crmURL p='civicrm/event/info' q="id=`$event.id`&reset=1" a=true fe=1 h=1}{/capture}
                {include file="CRM/common/SocialNetwork.tpl" emailMode=true url=$eventUrl title=$event.title pageURL=$eventUrl}
            </td>
        </tr>
    {/if}
    {if $payer.name}
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
    {if $event.is_monetary}

      <tr>
       <th {$headerStyle}>
        {$event.fee_label}
       </th>
      </tr>

      {if $lineItem}
       {foreach from=$lineItem item=value key=priceset}
        {if $value neq 'skip'}
         {if $isPrimary}
          {if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
           <tr>
            <td colspan="2" {$labelStyle}>
             {ts 1=$priceset+1}Participant %1{/ts} {$part.$priceset.info}
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
             {if $dataArray}
              <th>{ts}SubTotal{/ts}</th>
              <th>{ts}Tax Rate{/ts}</th>
              <th>{ts}Tax Amount{/ts}</th>
             {/if}
             <th>{ts}Total{/ts}</th>
       {if  $pricesetFieldsCount }<th>{ts}Total Participants{/ts}</th>{/if}
            </tr>
            {foreach from=$value item=line}
             <tr>
              <td {$tdfirstStyle}>
              {if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description}<div>{$line.description|truncate:30:"..."}</div>{/if}
              </td>
              <td {$tdStyle} align="middle">
               {$line.qty}
              </td>
              <td {$tdStyle}>
               {$line.unit_price|crmMoney:$currency}
              </td>
              {if $dataArray}
               <td {$tdStyle}>
                {$line.unit_price*$line.qty|crmMoney}
               </td>
               {if $line.tax_rate != "" || $line.tax_amount != ""}
                <td {$tdStyle}>
                 {$line.tax_rate|string_format:"%.2f"}%
                </td>
                <td {$tdStyle}>
                 {$line.tax_amount|crmMoney}
                </td>
               {else}
                <td></td>
                <td></td>
               {/if}
              {/if}
              <td {$tdStyle}>
               {$line.line_total+$line.tax_amount|crmMoney:$currency}
              </td>
        {if $pricesetFieldsCount }<td {$tdStyle}>{$line.participant_count}</td> {/if}
             </tr>
            {/foreach}
            {if $individual}
              <tr {$participantTotal}>
                <td colspan=3>{ts}Participant Total{/ts}</td>
                <td  colspan=2>{$individual.$priceset.totalAmtWithTax-$individual.$priceset.totalTaxAmt|crmMoney}</td>
                <td  colspan=1>{$individual.$priceset.totalTaxAmt|crmMoney}</td>
                <td  colspan=2>{$individual.$priceset.totalAmtWithTax|crmMoney}</td>
              </tr>
            {/if}
           </table>
          </td>
         </tr>
        {/if}
       {/foreach}
       {if $dataArray}
        <tr>
         <td {$labelStyle}>
          {ts} Amount Before Tax: {/ts}
         </td>
         <td {$valueStyle}>
          {$totalAmount-$totalTaxAmount|crmMoney}
         </td>
        </tr>
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

      {if $amounts && !$lineItem}
       {foreach from=$amounts item=amnt key=level}
        <tr>
         <td colspan="2" {$valueStyle}>
          {$amnt.amount|crmMoney:$currency} {$amnt.label}
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
      {if $isPrimary}
       <tr>
        <td {$labelStyle}>
         {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$totalAmount|crmMoney:$currency} {if $hookDiscount.message}({$hookDiscount.message}){/if}
        </td>
       </tr>
       {if $pricesetFieldsCount }
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

       {if $receive_date}
        <tr>
         <td {$labelStyle}>
          {ts}Transaction Date{/ts}
         </td>
         <td {$valueStyle}>
          {$receive_date|crmDate}
         </td>
        </tr>
       {/if}

       {if $financialTypeName}
        <tr>
         <td {$labelStyle}>
          {ts}Financial Type{/ts}
         </td>
         <td {$valueStyle}>
          {$financialTypeName}
         </td>
        </tr>
       {/if}

       {if $trxn_id}
        <tr>
         <td {$labelStyle}>
          {ts}Transaction #{/ts}
         </td>
         <td {$valueStyle}>
          {$trxn_id}
         </td>
        </tr>
       {/if}

       {if $paidBy}
        <tr>
         <td {$labelStyle}>
          {ts}Paid By{/ts}
         </td>
         <td {$valueStyle}>
         {$paidBy}
         </td>
        </tr>
       {/if}

       {if $checkNumber}
        <tr>
         <td {$labelStyle}>
          {ts}Check Number{/ts}
         </td>
         <td {$valueStyle}>
          {$checkNumber}
         </td>
        </tr>
       {/if}

       {if $contributeMode ne 'notify' and !$isAmountzero and (!$is_pay_later or $isBillingAddressRequiredForPayLater) and !$isOnWaitlist and !$isRequireApproval}
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

       {if $contributeMode eq 'direct' and !$isAmountzero and !$is_pay_later and !$isOnWaitlist and !$isRequireApproval}
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


{if $customPre}
{foreach from=$customPre item=customPr key=i}
   <tr> <th {$headerStyle}>{$customPre_grouptitle.$i}</th></tr>
   {foreach from=$customPr item=customValue key=customName}
   {if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
     <tr>
         <td {$labelStyle}>{$customName}</td>
         <td {$valueStyle}>{$customValue}</td>
     </tr>
   {/if}
   {/foreach}
{/foreach}
{/if}

{if $customPost}
{foreach from=$customPost item=customPos key=j}
   <tr> <th {$headerStyle}>{$customPost_grouptitle.$j}</th></tr>
   {foreach from=$customPos item=customValue key=customName}
   {if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
     <tr>
         <td {$labelStyle}>{$customName}</td>
         <td {$valueStyle}>{$customValue}</td>
     </tr>
{/if}
{/foreach}
{/foreach}
{/if}

{if $customProfile}
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
    </table>
    {if $event.allow_selfcancelxfer }
     <tr>
      <td colspan="2" {$valueStyle}>
        {ts 1=$event.selfcancelxfer_time}You may transfer your registration to another participant or cancel your registration up to %1 hours before the event.{/ts} {if $totalAmount}{ts}Cancellations are not refundable.{/ts}{/if}<br />
        {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`$participant.id`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
        <a href="{$selfService}">{ts}Click here to transfer or cancel your registration.{/ts}</a>
      </td>
     </tr>
    {/if}
   </td>
  </tr>
 </table>
</center>

</body>
</html>
