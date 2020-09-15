{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}

contactID:::{$contactID}
event.confirm_email_text:::{$event.confirm_email_text}
isOnWaitlist:::{$isOnWaitlist}
isRequireApproval:::{$isRequireApproval}
participant_status:::{$participant_status}
pricesetFieldsCount:::{$pricesetFieldsCount}
isPrimary:::{$isPrimary}
conference_sessions:::{$conference_sessions}
is_pay_later:::{$is_pay_later}
isAmountzero:::{$isAmountzero}
isAdditionalParticipant:::{$isAdditionalParticipant}
pay_later_receipt:::{$pay_later_receipt}
event.event_title:::{$event.event_title}
event.event_start_date:::{$event.event_start_date|date_format:"%A"}
event.event_end_date:::{$event.event_end_date|date_format:"%Y%m%d"}
event.is_monetary:::{$event.is_monetary}
event.fee_label:::{$event.fee_label}
conference_sessions:::{$conference_sessions}
event.participant_role::{$event.participant_role}
defaultRole:::{$defaultRole}
isShowLocation:::{$isShowLocation}
location.address.1.display:::{$location.address.1.display}
location.phone.1.phone:::{$location.phone.1.phone}
location.phone.1.phone_type_display:::{$location.phone.1.phone_type_display}
location.phone.1.phone_ext:::{$location.phone.1.phone_ext}
location.email.1.email:::{$location.email.1.email}
event.is_public:::{$event.is_public}
payer.name:::{$payer.name}
lineitem:::{if $lineItem}
 {foreach from=$lineItem item=value key=priceset}
  {foreach from=$value item=line}
     line.html_type:::{$line.html_type}
     line.label:::{$line.label}
     line.field_title:::{$line.field_title}
     line.description:::{$line.description}
     line.qty:::{$line.qty}
     line.unit_price:::{$line.unit_price}
     line.tax_rate:::{$line.tax_rate}
     line.tax_amount:::{$line.tax_amount}
     line.line_total:::{$line.line_total}
  {/foreach}
 {/foreach}
{/if}

part:::{foreach from=$part item=value key=key}
{$key}{$value}
{/foreach}

dataArray:::{$dataArray}

totalTaxAmount:::{$totalTaxAmount}
amounts:::{$amounts}
register_date:::{$register_date|crmDate}
receive_date:::{$receive_date|crmDate}
financialTypeName:::{$financialTypeName}
trxn_id:::{$trxn_id}
paidBy:::{$paidBy}
checkNumber:::{$checkNumber}
billingName:::{$billingName}
credit_card_type:::{$credit_card_type}
credit_card_number:::{$credit_card_number}
address:::{$address}
credit_card_exp_date:::{$credit_card_exp_date}
{foreach from=$customPre item=customValue key=customName}
  customPre: customName:::$customName
  customPre: customValue:::$customValue
{/foreach}
{foreach from=$customPost item=customValue key=customName}
  customPost: customName:::$customName
  customPost: customValue:::$customValue
{/foreach}
