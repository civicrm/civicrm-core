{if $event.confirm_email_text AND (not $isOnWaitlist AND not $isRequireApproval)}
{$event.confirm_email_text}
{/if}

{if $isOnWaitlist}
==========================================================={if $pricesetFieldsCount }===================={/if}

{ts}You have been added to the WAIT LIST for this event.{/ts}

{if $isPrimary}
{ts}If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}

{/if}
==========================================================={if $pricesetFieldsCount }===================={/if}

{elseif $isRequireApproval}
==========================================================={if $pricesetFieldsCount }===================={/if}

{ts}Your registration has been submitted.{/ts}

{if $isPrimary}
{ts}Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.{/ts}

{/if}
==========================================================={if $pricesetFieldsCount }===================={/if}

{elseif $is_pay_later}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$pay_later_receipt}
==========================================================={if $pricesetFieldsCount }===================={/if}

{else}

{ts}Please print this confirmation for your records.{/ts}
{/if}


==========================================================={if $pricesetFieldsCount }===================={/if}

{ts}Event Information and Location{/ts}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$event.event_title}
{$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate}{/if}{/if}

{if $event.participant_role neq 'Attendee' and $defaultRole}
{ts}Participant Role{/ts}: {$event.participant_role}
{/if}

{if $isShowLocation}
{if $location.address.1.name}

{$location.address.1.name}
{/if}
{if $location.address.1.street_address}{$location.address.1.street_address}
{/if}
{if $location.address.1.supplemental_address_1}{$location.address.1.supplemental_address_1}
{/if}
{if $location.address.1.supplemental_address_2}{$location.address.1.supplemental_address_2}
{/if}
{if $location.address.1.city}{$location.address.1.city} {$location.address.1.postal_code}{if $location.address.1.postal_code_suffix} - {$location.address.1.postal_code_suffix}{/if}
{/if}

{/if}{*End of isShowLocation condition*}

{if $location.phone.1.phone || $location.email.1.email}

{ts}Event Contacts:{/ts}
{foreach from=$location.phone item=phone}
{if $phone.phone}

{if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone}{/if} {if $phone.phone_ext} {ts}ext.{/ts} {$phone.phone_ext}{/if}
{/foreach}
{foreach from=$location.email item=eventEmail}
{if $eventEmail.email}

{ts}Email{/ts}: {$eventEmail.email}{/if}{/foreach}
{/if}

{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
{ts}Download iCalendar File:{/ts} {$icalFeed}
{if $email}

==========================================================={if $pricesetFieldsCount }===================={/if}

{ts}Registered Email{/ts}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$email}
{/if}
{if $event.is_monetary} {* This section for Paid events only.*}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$event.fee_label}
==========================================================={if $pricesetFieldsCount }===================={/if}

{if $lineItem}{foreach from=$lineItem item=value key=priceset}

{if $value neq 'skip'}
{if $isPrimary}
{if $lineItem|@count GT 1} {* Header for multi participant registration cases. *}
{ts 1=$priceset+1}Participant %1{/ts}
{/if}
{/if}
---------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{capture assign=ts_participant_total}{if $pricesetFieldsCount }{ts}Total Participants{/ts}{/if}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {$ts_total|string_format:"%10s"} {$ts_participant_total|string_format:"%10s"}
----------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{foreach from=$value item=line}
{if $pricesetFieldsCount }{capture assign=ts_participant_count}{$line.participant_count}{/capture}{/if}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney|string_format:"%10s"} {$line.line_total|crmMoney|string_format:"%10s"} {$ts_participant_count|string_format:"%10s"}
{/foreach}
{/if}
{/foreach}
{/if}
{if $amount && !$lineItem}
{foreach from=$amount item=amnt key=level}{$amnt.amount|crmMoney} {$amnt.label}
{/foreach}
{/if}
{if $isPrimary }

{ts}Total Amount{/ts}: {$totalAmount|crmMoney} {if $hookDiscount.message}({$hookDiscount.message}){/if}

{if $pricesetFieldsCount }
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

{if $is_pay_later }

==========================================================={if $pricesetFieldsCount }===================={/if}

{$pay_later_receipt}
==========================================================={if $pricesetFieldsCount }===================={/if}

{/if}

{if $register_date}
{ts}Registration Date{/ts}: {$register_date|crmDate}
{/if}
{if $receive_date}
{ts}Transaction Date{/ts}: {$receive_date|crmDate}
{/if}
{if $contributionTypeName}
{ts}Financial Type{/ts}: {$contributionTypeName}
{/if}
{if $trxn_id}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}
{if $paidBy}
{ts}Paid By{/ts}: {$paidBy}
{/if}
{if $checkNumber}
{ts}Check Number{/ts}: {$checkNumber}
{/if}
{if $contributeMode ne 'notify' and !$isAmountzero and !$is_pay_later and !$isOnWaitlist and !$isRequireApproval}

==========================================================={if $pricesetFieldsCount }===================={/if}

{ts}Billing Name and Address{/ts}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$billingName}
{$address}
{/if}

{if $contributeMode eq 'direct' and !$isAmountzero and !$is_pay_later and !$isOnWaitlist and !$isRequireApproval}
===========================================================
{ts}Credit Card Information{/ts}

==========================================================={if $pricesetFieldsCount }===================={/if}

{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}
{/if} {* End of conditional section for Paid events *}

{if $customPre}
==========================================================={if $pricesetFieldsCount }===================={/if}

{$customPre_grouptitle}
==========================================================={if $pricesetFieldsCount }===================={/if}

{foreach from=$customPre item=value key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
{$customName}: {$value}
{/if}
{/foreach}
{/if}

{if $customPost}
==========================================================={if $pricesetFieldsCount }===================={/if}

{$customPost_grouptitle}
==========================================================={if $pricesetFieldsCount }===================={/if}

{foreach from=$customPost item=value key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
{$customName}: {$value}
{/if}
{/foreach}
{/if}
{if $customProfile}

{foreach from=$customProfile item=value key=customName}
==========================================================={if $pricesetFieldsCount }===================={/if}

{ts 1=$customName+1}Participant Information - Participant %1{/ts}

==========================================================={if $pricesetFieldsCount }===================={/if}

{foreach from=$value item=val key=field}
{if $field eq 'additionalCustomPre' or $field eq 'additionalCustomPost' }
{if $field eq 'additionalCustomPre' }
----------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{$additionalCustomPre_grouptitle}
----------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{else}
----------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{$additionalCustomPost_grouptitle}
----------------------------------------------------------{if $pricesetFieldsCount }--------------------{/if}

{/if}
{foreach from=$val item=v key=f}
{$f}: {$v}
{/foreach}
{/if}
{/foreach}
{/foreach}
{/if}
{if $customGroup}
{foreach from=$customGroup item=value key=customName}
=========================================================={if $pricesetFieldsCount }===================={/if}

{$customName}
=========================================================={if $pricesetFieldsCount }===================={/if}

{foreach from=$value item=v key=n}
{$n}: {$v}
{/foreach}
{/foreach}
{/if}


