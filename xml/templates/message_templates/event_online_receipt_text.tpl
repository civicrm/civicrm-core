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
===============================================================================

{ts}You have been added to the WAIT LIST for this event.{/ts}

{if $isPrimary}
{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
{/if}
===============================================================================

{elseif !empty($isRequireApproval)}
===============================================================================

{ts}Your registration has been submitted.{/ts}

{if $isPrimary}
{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}

{/if}
===============================================================================

{elseif $isPrimary && {contribution.balance_amount|boolean} && {contribution.is_pay_later|boolean}}


===============================================================================

{if {event.pay_later_receipt|boolean}}{event.pay_later_receipt}{/if}
===============================================================================

{/if}


===============================================================================

{ts}Event Information and Location{/ts}

===============================================================================

{event.title}
{event.start_date|crmDate:"%A"} {event.start_date|crmDate}{if {event.end_date|boolean}}-{if $event.event_end_date|crmDate:"%Y%m%d" == $event.event_start_date|crmDate:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate:"%A"} {$event.event_end_date|crmDate}{/if}{/if}

{if "{participant.role_id:label}" neq 'Attendee'}
{ts}Participant Role{/ts}: {participant.role_id:label}
{/if}

{if !empty($isShowLocation)}
{event.location}
{/if}{*End of isShowLocation condition*}

{if {event.loc_block_id.phone_id.phone|boolean} || {event.loc_block_id.email_id.email|boolean}}

{ts}Event Contacts:{/ts}
{if {event.loc_block_id.phone_id.phone|boolean}}
  {if {event.loc_block_id.phone_id.phone_type_id|boolean}}{event.loc_block_id.phone_id.phone_type_id:label}{else}{ts}Phone{/ts}{/if} {event.loc_block_id.phone_id.phone} {if {event.loc_block_id.phone_id.phone_ext|boolean}} {ts}ext.{/ts} {event.loc_block_id.phone_id.phone_ext}{/if}
{/if}
{if {event.loc_block_id.email_id.email|boolean}}
{ts}Email {/ts}{event.loc_block_id.email_id.email}
{/if}
{if {event.loc_block_id.email_2_id.email|boolean}}
{ts}Email {/ts}{event.loc_block_id.email_2_id.email}{/if}
{/if}
{if {event.is_public|boolean} and {event.is_show_calendar_links|boolean}}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
{ts}Download iCalendar entry for this event.{/ts} {$icalFeed}
{capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
{ts}Add event to Google Calendar{/ts} {$gCalendar}
{/if}

{if !empty($payer.name)}
You were registered by: {$payer.name}
{/if}
{if {event.is_monetary|boolean} and empty($isRequireApproval)} {* This section for Paid events only.*}

===============================================================================

{event.fee_label}
===============================================================================

{if !empty($lineItem)}{foreach from=$lineItem item=value key=priceset}

{if $value neq 'skip'}
{if $isPrimary}
{if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
{ts 1=$priceset+1}Participant %1{/ts} {if !empty($part.$priceset)}{$part.$priceset.info}{/if}

{/if}
{/if}
----------------------------------------------------------------------------------------------------------------

{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $isShowTax && {contribution.tax_amount|boolean}}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{if !empty($pricesetFieldsCount)}{capture assign=ts_participant_total}{ts}Total Participants{/ts}{/capture}{/if}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"} {if !empty($ts_participant_total)}{$ts_participant_total|string_format:"%10s"}{/if}
----------------------------------------------------------------------------------------------------------------

{foreach from=$value item=line}
{if !empty($pricesetFieldsCount)}{capture assign=ts_participant_count}{$line.participant_count}{/capture}{/if}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}} {$line.line_total|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}  {/if} {$line.line_total+$line.tax_amount|crmMoney:$currency|string_format:"%10s"}{if !empty($ts_participant_count)}{$ts_participant_count|string_format:"%10s"}{/if}
{/foreach}
----------------------------------------------------------------------------------------------------------------
{if !empty($individual)}{ts}Participant Total{/ts} {$individual.$priceset.totalAmtWithTax-$individual.$priceset.totalTaxAmt|crmMoney:$currency|string_format:"%29s"} {$individual.$priceset.totalTaxAmt|crmMoney:$currency|string_format:"%33s"} {$individual.$priceset.totalAmtWithTax|crmMoney:$currency|string_format:"%12s"}{/if}
{/if}
{""|string_format:"%120s"}
{/foreach}
{""|string_format:"%120s"}

{if $isShowTax && {contribution.tax_amount|boolean}}
{ts}Amount before Tax:{/ts} {if $isPrimary}{contribution.tax_exclusive_amount}{else}{$participant.totals.total_amount_exclusive|crmMoney}{/if}
{if !$isPrimary}{* Use the participant specific tax rate breakdown *}{assign var=taxRateBreakdown value=$participant.tax_rate_breakdown}{/if}
{foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if}   {$valueStyle}>{$taxDetail.amount|crmMoney:'{contribution.currency}'}
{/foreach}
{/if}
{/if}

{if !$isShowLineItems}
{foreach from=$participants key=index item=currentParticipant}
{if $isPrimary || {participant.id} === $currentParticipant.id}
{foreach from=$currentParticipant.line_items key=index item=currentLineItem}
{$currentLineItem.label} {if $isPrimary} - {$currentParticipant.contact.display_name}{/if} - {$currentLineItem.line_total|crmMoney:$currency}
{/foreach}
{/if}
{/foreach}
{/if}

{if $isShowTax && {contribution.tax_amount|boolean}}
{ts}Total Tax Amount{/ts}: {if $isPrimary}{contribution.tax_amount}{else}{$participant.totals.tax_amount|crmMoney}{/if}
{/if}
{if $isPrimary}

{ts}Total Amount{/ts}: {if !empty($totalAmount)}{$totalAmount|crmMoney:$currency}{/if} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}

{if !empty($pricesetFieldsCount)}
      {assign var="count" value= 0}
      {foreach from=$lineItem item=pcount}
      {assign var="lineItemCount" value=0}
      {if $pcount neq 'skip'}
        {foreach from=$pcount item=p_count}
        {assign var="lineItemCount" value=$lineItemCount+$p_count.participant_count}
        {/foreach}
      {if $lineItemCount < 1}
        {assign var="lineItemCount" value=1}
      {/if}
      {assign var="count" value=$count+$lineItemCount}
      {/if}
      {/foreach}

{ts}Total Participants{/ts}: {$count}
{/if}

{if {participant.register_date|boolean}}
{ts}Registration Date{/ts}: {participant.register_date}
{/if}
{if {contribution.receive_date|boolean}}
{ts}Transaction Date{/ts}: {contribution.receive_date}
{/if}
{if !empty($financialTypeName)}
{ts}Financial Type{/ts}: {$financialTypeName}
{/if}
{if !empty($trxn_id)}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}
{if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
{ts}Paid By{/ts}: {contribution.payment_instrument_id:label}
{/if}
{if !empty($checkNumber)}
{ts}Check Number{/ts}: {$checkNumber}
{/if}
{if {contribution.address_id.display|boolean}}

===============================================================================

{ts}Billing Name and Address{/ts}

===============================================================================

{contribution.address_id.name}
{contribution.address_id.display}
{/if}

{if !empty($credit_card_type)}
===============================================================================

{ts}Credit Card Information{/ts}

===============================================================================

{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}
{/if} {* End of conditional section for Paid events *}

{if !empty($customPre)}
{foreach from=$customPre item=customPr key=i}
===============================================================================

{$customPre_grouptitle.$i}
===============================================================================

{foreach from=$customPr item=customValue key=customName}
 {$customName}: {$customValue}
{/foreach}
{/foreach}
{/if}

{if !empty($customPost)}
{foreach from=$customPost item=customPos key=j}
===============================================================================

{$customPost_grouptitle.$j}
===============================================================================

{foreach from=$customPos item=customValue key=customName}
 {$customName}: {$customValue}
{/foreach}
{/foreach}
{/if}
{if !empty($customProfile)}

{foreach from=$customProfile.profile item=eachParticipant key=participantID}
===============================================================================

{ts 1=$participantID+2}Participant Information - Participant %1{/ts}

===============================================================================

{foreach from=$eachParticipant item=eachProfile key=pid}
------------------------------------------------------------------------------

{$customProfile.title.$pid}
------------------------------------------------------------------------------

{foreach from=$eachProfile item=val key=field}
{foreach from=$val item=v key=f}
{$field}: {$v}
{/foreach}
{/foreach}
{/foreach}
{/foreach}
{/if}

{if !empty($event.allow_selfcancelxfer)}
{ts 1=$selfcancelxfer_time 2=$selfservice_preposition}You may transfer your registration to another participant or cancel your registration up to %1 hours %2 the event.{/ts} {if !empty($totalAmount)}{ts}Cancellations are not refundable.{/ts}{/if}
   {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid={participant.id}&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
{ts}Transfer or cancel your registration:{/ts} {$selfService}
{/if}
