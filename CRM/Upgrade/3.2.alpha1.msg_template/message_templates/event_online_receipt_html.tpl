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

    {if $event.confirm_email_text AND (not $isOnWaitlist AND not $isRequireApproval)}
     <p>{$event.confirm_email_text|htmlize}</p>
    {/if}

    {if $isOnWaitlist}
     <p>{ts}You have been added to the WAIT LIST for this event.{/ts}</p>
     {if $isPrimary}
       <p>{ts}If space becomes available you will receive an email with
a link to a web page where you can complete your registration.{/ts}</p>
     {/if}
    {elseif $isRequireApproval}
     <p>{ts}Your registration has been submitted.{/ts}</p>
     {if $isPrimary}
      <p>{ts}Once your registration has been reviewed, you will receive
an email with a link to a web page where you can complete the
registration process.{/ts}</p>
     {/if}
    {elseif $is_pay_later}
     <p>{$pay_later_receipt}</p> {* FIXME: this might be text rather than HTML *}
    {else}
     <p>{ts}Please print this confirmation for your records.{/ts}</p>
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
         {$location.address.1.city} {$location.address.1.postal_code}{if $location.address.1.postal_code_suffix} - {$location.address.1.postal_code_suffix}{/if}<br />
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
          {$phone.phone}
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
     <tr>
      <td colspan="2" {$valueStyle}>
       {capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
       <a href="{$icalFeed}">{ts}Download iCalendar File{/ts}</a>
      </td>
     </tr>
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
             <th>{ts}Total{/ts}</th>
       {if  $participantCount }<th>{ts}Total Participants{/ts}</th>{/if}
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
        {if  $participantCount }<td>{$line.participant_count}</td> {/if}
             </tr>
            {/foreach}
           </table>
          </td>
         </tr>
        {/if}
       {/foreach}
      {/if}

      {if $amount && !$lineItem}
       {foreach from=$amount item=amnt key=level}
        <tr>
         <td colspan="2" {$valueStyle}>
          {$amnt.amount|crmMoney} {$amnt.label}
         </td>
        </tr>
       {/foreach}
      {/if}

      {if $isPrimary}
       <tr>
        <td {$labelStyle}>
         {ts}Total Amount{/ts}
        </td>
        <td {$valueStyle}>
         {$totalAmount|crmMoney} {if $hookDiscount.message}({$hookDiscount.message}){/if}
        </td>
       </tr>
       {if $participantCount }
     <tr>
       <td {$labelStyle}>
      {ts}Total Participants{/ts}</td>
       <td {$valueStyle}>
      {assign var="count" value= 0}
      {foreach from=$lineItem item=pcount}
      {foreach from=$pcount item=p_count}
      {assign var="count" value=$count+$p_count.participant_count}
      {/foreach}
      {/foreach}
     {$count}
     </td> </tr>
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

       {if $contributionTypeName}
        <tr>
         <td {$labelStyle}>
          {ts}Contribution Type{/ts}
         </td>
         <td {$valueStyle}>
          {$contributionTypeName}
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

       {if $contributeMode ne 'notify' and !$isAmountzero and !$is_pay_later and !$isOnWaitlist and !$isRequireApproval}
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
      <tr>
       <th {$headerStyle}>
        {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=value key=customName}
       {if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
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

     {if $customPost}
      <tr>
       <th {$headerStyle}>
        {$customPost_grouptitle}
       </th>
      </tr>
      {foreach from=$customPost item=value key=customName}
       {if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
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

     {if $customProfile}
      {foreach from=$customProfile item=value key=customName}
       <tr>
        <th {$headerStyle}>
         {ts 1=$customName+1}Participant Information - Participant %1{/ts}
        </th>
       <tr>
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
   </td>
  </tr>

 </table>
</center>

</body>
</html>
