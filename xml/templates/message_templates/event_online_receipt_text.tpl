{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}
{if !empty($event.confirm_email_text) AND (empty($isOnWaitlist) AND empty($isRequireApproval))}
{$event.confirm_email_text}

{else}
  {ts}Thank you for your registration.{/ts}
  {if $participant_status}{ts 1=$participant_status}This is a confirmation that your registration has been received and your status has been updated to %1.{/ts}
  {else}{if !empty($isOnWaitlist)}{ts}This is a confirmation that your registration has been received and your status has been updated to waitlisted.{/ts}{else}{ts}This is a confirmation that your registration has been received and your status has been updated to registered.{/ts}{/if}
  {/if}
{/if}

{if !empty($isOnWaitlist)}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts}You have been added to the WAIT LIST for this event.{/ts}

{if !empty($isPrimary)}
{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
{/if}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{elseif !empty($isRequireApproval)}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts}Your registration has been submitted.{/ts}

{if !empty($isPrimary)}
{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}

{/if}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{elseif !empty($is_pay_later) && empty($isAmountzero) && empty($isAdditionalParticipant)}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{if isset($pay_later_receipt)}{$pay_later_receipt}{/if}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{/if}


==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts}Event Information and Location{/ts}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{event.title}
{event.start_date|crmDate:"%A"} {event.start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|crmDate:"%Y%m%d" == $event.event_start_date|crmDate:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate:"%A"} {$event.event_end_date|crmDate}{/if}{/if}
{if !empty($conference_sessions)}


{ts}Your schedule:{/ts}
{assign var='group_by_day' value='NA'}
{foreach from=$conference_sessions item=session}
{if $session.start_date|crmDate:"%Y/%m/%d" != $group_by_day|crmDate:"%Y/%m/%d"}
{assign var='group_by_day' value=$session.start_date}

{$group_by_day|crmDate:"%m/%d/%Y"}


{/if}
{$session.start_date|crmDate:0:1}{if $session.end_date}-{$session.end_date|crmDate:0:1}{/if} {$session.title}
{if $session.location}    {$session.location}{/if}
{/foreach}
{/if}

{if !empty($event.participant_role) and $event.participant_role neq 'Attendee' and !empty($defaultRole)}
{ts}Participant Role{/ts}: {$event.participant_role}
{/if}

{if !empty($isShowLocation)}
{$location.address.1.display|strip_tags:false}
{/if}{*End of isShowLocation condition*}

{if !empty($location.phone.1.phone) || !empty($location.email.1.email)}

{ts}Event Contacts:{/ts}
{foreach from=$location.phone item=phone}
{if $phone.phone}

{if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone}{/if} {if $phone.phone_ext} {ts}ext.{/ts} {$phone.phone_ext}{/if}
{/foreach}
{foreach from=$location.email item=eventEmail}
{if $eventEmail.email}

{ts}Email{/ts}: {$eventEmail.email}{/if}{/foreach}
{/if}

{if !empty($event.is_public)}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
{ts}Download iCalendar entry for this event.{/ts} {$icalFeed}
{capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
{ts}Add event to Google Calendar{/ts} {$gCalendar}
{/if}

{if !empty($payer.name)}
You were registered by: {$payer.name}
{/if}
{if !empty($event.is_monetary) and empty($isRequireApproval)} {* This section for Paid events only.*}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{if !empty ($event.fee_label)}{$event.fee_label}{/if}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{if !empty($lineItem)}{foreach from=$lineItem item=value key=priceset}

{if $value neq 'skip'}
{if !empty($isPrimary)}
{if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
{ts 1=$priceset+1}Participant %1{/ts} {if !empty($part.$priceset)}{$part.$priceset.info}{/if}

{/if}
{/if}
-----------------------------------------------------------{if !empty($pricesetFieldsCount)}-----------------------------------------------------{/if}

{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if !empty($dataArray)}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{if !empty($pricesetFieldsCount) }{capture assign=ts_participant_total}{ts}Total Participants{/ts}{/capture}{/if}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if !empty($dataArray)} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"} {if !empty($ts_participant_total)}{$ts_participant_total|string_format:"%10s"}{/if}
-----------------------------------------------------------{if !empty($pricesetFieldsCount)}-----------------------------------------------------{/if}

{foreach from=$value item=line}
{if !empty($pricesetFieldsCount) }{capture assign=ts_participant_count}{$line.participant_count}{/capture}{/if}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {if !empty($dataArray)} {$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}  {/if} {$line.line_total+$line.tax_amount|crmMoney:$currency|string_format:"%10s"}{if !empty($ts_participant_count)}{$ts_participant_count|string_format:"%10s"}{/if}
{/foreach}
----------------------------------------------------------------------------------------------------------------
{if !empty($individual)}{ts}Participant Total{/ts} {$individual.$priceset.totalAmtWithTax-$individual.$priceset.totalTaxAmt|crmMoney:$currency|string_format:"%29s"} {$individual.$priceset.totalTaxAmt|crmMoney:$currency|string_format:"%33s"} {$individual.$priceset.totalAmtWithTax|crmMoney:$currency|string_format:"%12s"}{/if}
{/if}
{""|string_format:"%120s"}
{/foreach}
{""|string_format:"%120s"}

{if !empty($dataArray)}
{if isset($totalAmount) and isset($totalTaxAmount)}
{ts}Amount before Tax{/ts}: {$totalAmount-$totalTaxAmount|crmMoney:$currency}
{/if}

{foreach from=$dataArray item=value key=priceset}
{if $priceset || $priceset == 0}
{$taxTerm} {$priceset|string_format:"%.2f"}%: {$value|crmMoney:$currency}
{else}
{ts}No{/ts} {$taxTerm}: {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}
{/if}

{if !empty($amounts) && empty($lineItem)}
{foreach from=$amounts item=amnt key=level}{$amnt.amount|crmMoney:$currency} {$amnt.label}
{/foreach}
{/if}

{if isset($totalTaxAmount)}
{ts}Total Tax Amount{/ts}: {$totalTaxAmount|crmMoney:$currency}
{/if}
{if !empty($isPrimary) }

{ts}Total Amount{/ts}: {if !empty($totalAmount)}{$totalAmount|crmMoney:$currency}{/if} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}

{if !empty($pricesetFieldsCount) }
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

{ts}Total Participants{/ts}: {$count}
{/if}

{if $register_date}
{ts}Registration Date{/ts}: {$register_date|crmDate}
{/if}
{if !empty($receive_date)}
{ts}Transaction Date{/ts}: {$receive_date|crmDate}
{/if}
{if !empty($financialTypeName)}
{ts}Financial Type{/ts}: {$financialTypeName}
{/if}
{if !empty($trxn_id)}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}
{if !empty($paidBy)}
{ts}Paid By{/ts}: {$paidBy}
{/if}
{if !empty($checkNumber)}
{ts}Check Number{/ts}: {$checkNumber}
{/if}
{if !empty($billingName)}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts}Billing Name and Address{/ts}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{$billingName}
{$address}
{/if}

{if !empty($credit_card_type)}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts}Credit Card Information{/ts}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}
{/if} {* End of conditional section for Paid events *}

{if !empty($customPre)}
{foreach from=$customPre item=customPr key=i}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{$customPre_grouptitle.$i}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{foreach from=$customPr item=customValue key=customName}
{if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/foreach}
{/if}

{if !empty($customPost)}
{foreach from=$customPost item=customPos key=j}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{$customPost_grouptitle.$j}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{foreach from=$customPos item=customValue key=customName}
{if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/foreach}
{/if}
{if !empty($customProfile)}

{foreach from=$customProfile.profile item=eachParticipant key=participantID}
==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{ts 1=$participantID+2}Participant Information - Participant %1{/ts}

==========================================================={if !empty($pricesetFieldsCount)}===================={/if}

{foreach from=$eachParticipant item=eachProfile key=pid}
----------------------------------------------------------{if !empty($pricesetFieldsCount)}--------------------{/if}

{$customProfile.title.$pid}
----------------------------------------------------------{if !empty($pricesetFieldsCount)}--------------------{/if}

{foreach from=$eachProfile item=val key=field}
{foreach from=$val item=v key=f}
{$field}: {$v}
{/foreach}
{/foreach}
{/foreach}
{/foreach}
{/if}

{if !empty($event.allow_selfcancelxfer) }
{ts 1=$selfcancelxfer_time 2=$selfservice_preposition}You may transfer your registration to another participant or cancel your registration up to %1 hours %2 the event.{/ts} {if !empty($totalAmount)}{ts}Cancellations are not refundable.{/ts}{/if}
   {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`$participant.id`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
{ts}Transfer or cancel your registration:{/ts} {$selfService}
{/if}
