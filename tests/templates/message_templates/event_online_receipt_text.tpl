{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}

contactID:::{$contactID}
{if !empty($event.confirm_email_text)}
event.confirm_email_text:::{$event.confirm_email_text}
{/if}
{if isset($isOnWaitlist)}
isOnWaitlist:::{$isOnWaitlist}
{/if}
{if isset($isRequireApproval)}
isRequireApproval:::{$isRequireApproval}
{/if}
participant_status:::{$participant_status}
{if isset($pricesetFieldsCount)}
pricesetFieldsCount:::{$pricesetFieldsCount}
{/if}
{if !empty($isPrimary)}
isPrimary:::{$isPrimary}
{/if}
{if isset($conference_sessions)}
conference_sessions:::{$conference_sessions}
{/if}
{if isset($is_pay_later)}
is_pay_later:::{$is_pay_later}
{/if}
{if isset($isAmountzero)}
isAmountzero:::{$isAmountzero}
{/if}
{if isset($isAdditionalParticipant)}
isAdditionalParticipant:::{$isAdditionalParticipant}
{/if}
{if isset($pay_later_receipt)}
pay_later_receipt:::{$pay_later_receipt}
{/if}
event.event_title:::{$event.event_title}
event.event_start_date:::{$event.event_start_date|date_format:"%A"}
event.event_end_date:::{$event.event_end_date|date_format:"%Y%m%d"}
{if isset($event.is_monetary)}
event.is_monetary:::{$event.is_monetary}
{/if}
{if !empty($event.fee_label)}
event.fee_label:::{$event.fee_label}
{/if}
{if !empty($conference_sessions)}
conference_sessions:::{$conference_sessions}
{/if}
{if !empty($event.participant_role)}
  event.participant_role::{$event.participant_role}
  defaultRole:::{$defaultRole}
{/if}
{if !empty($isShowLocation)}
isShowLocation:::{$isShowLocation}
location.address.1.display:::{$location.address.1.display}
location.phone.1.phone:::{$location.phone.1.phone}
location.phone.1.phone_type_display:::{$location.phone.1.phone_type_display}
location.phone.1.phone_ext:::{$location.phone.1.phone_ext}
location.email.1.email:::{$location.email.1.email}
{/if}
{if !empty($event.is_public)}
event.is_public:::{$event.is_public}
{/if}
{if !empty($payer.name)}
payer.name:::{$payer.name}
{/if}
lineitem:::{if !empty($lineItem)}
 {foreach from=$lineItem item=value key=priceset}
  {foreach from=$value item=line}
     line.html_type:::{$line.html_type}
     line.label:::{$line.label}
     line.field_title:::{$line.field_title}
     line.description:::{$line.description}
     line.qty:::{$line.qty}
     line.unit_price:::{$line.unit_price}
     {if isset($line.tax_rate)}
     line.tax_rate:::{$line.tax_rate}
     line.tax_amount:::{$line.tax_amount}
     {/if}
     line.line_total:::{$line.line_total}
  {/foreach}
 {/foreach}
{/if}

{if !empty($part)}
part:::{foreach from=$part item=value key=key}
{$key}{$value}
{/foreach}
{/if}

{if !empty($dataArray)}
dataArray:::{$dataArray}
{/if}

{if isset($totalTaxAmount)}
totalTaxAmount:::{$totalTaxAmount}
{/if}
{if !empty($amounts)}
{foreach from=$amounts item=amountValue key=amountKey}
amounts:::$amountKey $amountValue
{/foreach}
{/if}
register_date:::{$register_date|crmDate}
{if !empty($receive_date)}
receive_date:::{$receive_date|crmDate}
{/if}
{if !empty($financialTypeName)}
financialTypeName:::{$financialTypeName}
{/if}
{if !empty($trxn_id)}
trxn_id:::{$trxn_id}
{/if}
{if !empty($paidBy)}
paidBy:::{$paidBy}
{/if}
{if isset($checkNumber)}
checkNumber:::{$checkNumber}
{/if}
{if isset($billingName)}
billingName:::{$billingName}
{/if}
{if isset($credit_card_type)}
credit_card_type:::{$credit_card_type}
credit_card_number:::{$credit_card_number}
address:::{$address}
credit_card_exp_date:::{$credit_card_exp_date}
{/if}
{if !empty($customPre)}
{foreach from=$customPre item=customValue key=customName}
  customPre: customName:::$customName
  customPre: customValue:::$customValue
{/foreach}
{/if}
{if !empty($customPost)}
{foreach from=$customPost item=customValue key=customName}
  customPost: customName:::$customName
  customPost: customValue:::$customValue
{/foreach}
{/if}
