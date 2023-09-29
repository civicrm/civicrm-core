{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}
{if !empty($event.confirm_email_text) AND (empty($isOnWaitlist) AND empty($isRequireApproval))}
{$event.confirm_email_text}
{/if}

{if !empty($isOnWaitlist)}
===============================================================================

{ts}You have been added to the WAIT LIST for this event.{/ts}

{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}

===============================================================================

{elseif !empty($isRequireApproval)}
===============================================================================

{ts}Your registration has been submitted.{/ts}

{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}

===============================================================================

{elseif $isPrimary && {contribution.balance_amount|boolean} && {contribution.is_pay_later|boolean}}

===============================================================================

{$pay_later_receipt}
===============================================================================

{/if}


===============================================================================

{ts}Event Information and Location{/ts}

===============================================================================

{event.title}
{event.start_date|crmDate}{if {event.end_date|boolean}}-{if '{event.end_date|crmDate:"%Y%m%d"}' === '{event.start_date|crmDate:"%Y%m%d"}'}{event.end_date|crmDate:"Time"}{else}{event.end_date}{/if}{/if}

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

{if {event.loc_block_id.phone_2_id.phone|boolean}}
{if {event.loc_block_id.phone_2_id.phone_type_id|boolean}}{event.loc_block_id.phone_2_id.phone_type_id:label}{else}{ts}Phone{/ts}{/if} {event.loc_block_id.phone_2_id.phone} {if {event.loc_block_id.phone_2_id.phone_ext|boolean}} {ts}ext.{/ts} {event.loc_block_id.phone_2_id.phone_ext}{/if}
{/if}

{if {event.loc_block_id.email_id.email|boolean}}
{ts}Email {/ts}{event.loc_block_id.email_id.email}
{/if}
{if {event.loc_block_id.email_2_id.email|boolean}}
{ts}Email {/ts}{event.loc_block_id.email_2_id.email}{/if}
{/if}


{if {event.is_public|boolean}}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
{ts}Download iCalendar entry for this event.{/ts} {$icalFeed}
{capture assign=gCalendar}{crmURL p='civicrm/event/ical' q="gCalendar=1&reset=1&id={event.id}" h=0 a=1 fe=1}{/capture}
{ts}Add event to Google Calendar{/ts} {$gCalendar}
{/if}

{if {contact.email_primary.email|boolean}}

===============================================================================

{ts}Registered Email{/ts}

===============================================================================

{contact.email_primary.email}
{/if}
{if {event.is_monetary|boolean}} {* This section for Paid events only.*}

===============================================================================

{event.fee_label}
===============================================================================

{if !empty($lineItem)}{foreach from=$lineItem item=value key=priceset}

{if $value neq 'skip'}
{if {event.is_monetary|boolean}}
{if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
{ts 1=$priceset+1}Participant %1{/ts}
{/if}
{/if}
-----------------------------------------------------------------------------

{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $isShowTax && {contribution.tax_amount|boolean}}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{capture assign=ts_participant_total}{if !empty($pricesetFieldsCount)}{ts}Total Participants{/ts}{/if}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"} {if !empty($ts_participant_total)}{$ts_participant_total|string_format:"%10s"}{/if}

{foreach from=$value item=line}
{if !empty($pricesetFieldsCount)}{capture assign=ts_participant_count}{$line.participant_count}{/capture}{/if}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney|string_format:"%10s"} {if !empty($dataArray)} {$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}  {/if}  {$line.line_total+$line.tax_amount|crmMoney|string_format:"%10s"} {if !empty($ts_participant_count)}{$ts_participant_count|string_format:"%10s"}{/if}
{/foreach}
{/if}
{/foreach}

{if $isShowTax && {contribution.tax_amount|boolean}}
{if $totalAmount and $totalTaxAmount}
{ts}Amount before Tax:{/ts} {$totalAmount-$totalTaxAmount|crmMoney:$currency}
{/if}

{foreach from=$dataArray item=value key=priceset}
{if $priceset || $priceset == 0}
{$taxTerm} {$priceset|string_format:"%.2f"}%: {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}
{/if}

{if !empty($amount) && !$lineItem}
{foreach from=$amount item=amnt key=level}{$amnt.amount|crmMoney} {$amnt.label}
{/foreach}
{/if}

{if {contribution.tax_amount|boolean}}
{ts}Total Tax Amount{/ts}: {contribution.tax_amount}
{/if}
{if {event.is_monetary|boolean}}

{if {contribution.balance_amount|boolean}}{ts}Total Paid{/ts}: {contribution.paid_amount} {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
{ts}Balance{/ts}: {contribution.balance_amount}
{else}{ts}Total Amount{/ts}: {contribution.total_amount}  {if !empty($hookDiscount.message)}({$hookDiscount.message}){/if}
{/if}

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

{if $isPrimary && {contribution.balance_amount|boolean} && {contribution.is_pay_later|boolean}}
===============================================================================

{$pay_later_receipt}
===============================================================================

{/if}

{if {participant.register_date|boolean}}
{ts}Registration Date{/ts}: {participant.register_date}
{/if}
{if {contribution.receive_date|boolean}}
{ts}Transaction Date{/ts}: {contribution.receive_date}
{/if}
{if {contribution.financial_type_id|boolean}}
{ts}Financial Type{/ts}: {contribution.financial_type_id:label}
{/if}
{if {contribution.trxn_id|boolean}}
{ts}Transaction #{/ts}: {contribution.trxn_id}
{/if}
{if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
{ts}Paid By{/ts}: {contribution.payment_instrument_id:label}
{/if}
{if {contribution.check_number|boolean}}
{ts}Check Number{/ts}: {contribution.check_number}
{/if}
{if !empty($billingName)}

===============================================================================

{ts}Billing Name and Address{/ts}

===============================================================================

{$billingName}
{$address}
{/if}

{if !empty($credit_card_type)}
===========================================================
{ts}Credit Card Information{/ts}

===============================================================================

{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}
{/if} {* End of conditional section for Paid events *}

{if !empty($customGroup)}
{foreach from=$customGroup item=value key=customName}
==============================================================================

{$customName}
==============================================================================

{foreach from=$value item=v key=n}
{$n}: {$v}
{/foreach}
{/foreach}
{/if}


