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
    <p>{ts 1=$contact.display_name}Dear %1{/ts},</p>
   </td>
  </tr>
  {if !$isAdditional and $participant.id}
   <tr>
    <th {$headerStyle}>
     {ts}Confirm Your Registration{/ts}
    </th>
   </tr>
   <tr>
    <td colspan="2" {$valueStyle}>
     {capture assign=confirmUrl}{crmURL p='civicrm/event/confirm' q="reset=1&participantId=`$participant.id`&cs=`$checksumValue`" a=true h=0 fe=1}{/capture}
     <a href="{$confirmUrl}">Go to a web page where you can confirm your registration online</a>
    </td>
   </tr>
  {/if}
  {if $event.allow_selfcancelxfer }
  This event allows for self-cancel or transfer
  {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`$participantID`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
       <a href="{$selfService}">{ts}Self service cancel transfer{/ts}</a>
  {/if}

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
     <tr>
      <td {$labelStyle}>
       {ts}Participant Role{/ts}:
      </td>
      <td {$valueStyle}>
       {$participant.role}
      </td>
     </tr>

     {if $isShowLocation}
      <tr>
       <td colspan="2" {$valueStyle}>
        {if $event.location.address.1.name}
         {$event.location.address.1.name}<br />
        {/if}
        {if $event.location.address.1.street_address}
         {$event.location.address.1.street_address}<br />
        {/if}
        {if $event.location.address.1.supplemental_address_1}
         {$event.location.address.1.supplemental_address_1}<br />
        {/if}
        {if $event.location.address.1.supplemental_address_2}
         {$event.location.address.1.supplemental_address_2}<br />
        {/if}
        {if $event.location.address.1.city}
         {$event.location.address.1.city} {$event.location.address.1.postal_code}
         {if $event.location.address.1.postal_code_suffix}
          - {$event.location.address.1.postal_code_suffix}
         {/if}
        {/if}
       </td>
      </tr>
     {/if}

     {if $event.location.phone.1.phone || $event.location.email.1.email}
      <tr>
       <td colspan="2" {$labelStyle}>
        {ts}Event Contacts:{/ts}
       </td>
      </tr>
      {foreach from=$event.location.phone item=phone}
       {if $phone.phone}
        <tr>
         <td {$labelStyle}>
          {if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}
         </td>
         <td {$valueStyle}>
          {$phone.phone}
         </td>
        </tr>
       {/if}
      {/foreach}
      {foreach from=$event.location.email item=eventEmail}
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

     {if $contact.email}
      <tr>
       <th {$headerStyle}>
        {ts}Registered Email{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {$contact.email}
       </td>
      </tr>
     {/if}

     {if $register_date}
      <tr>
       <td {$labelStyle}>
        {ts}Registration Date{/ts}
       </td>
       <td {$valueStyle}>
        {$participant.register_date|crmDate}
       </td>
      </tr>
     {/if}

    </table>
   </td>
  </tr>
  {if $event.allow_selfcancelxfer }
   <tr>
     <td colspan="2" {$valueStyle}>
       {ts 1=$event.selfcancelxfer_time}You may transfer your registration to another participant or cancel your registration up to %1 hours before the event.{/ts} {if $totalAmount}{ts}Cancellations are not refundable.{/ts}{/if}<br />
         {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`$participant.id`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
       <a href="{$selfService}">{ts}Click here to transfer or cancel your registration.{/ts}</a>
     </td>
   </tr>
  {/if}
  <tr>
   <td colspan="2" {$valueStyle}>
    <p>{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions.{/ts}</p>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
