<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
</head>
<body>
  receive_date:::{$receive_date}
  receipt_date:::{$receipt_date}
  receipt_text:::{$receipt_text}
  is_pay_later:::{$is_pay_later}
  displayName:::{$displayName}
  financialTypeId:::{$financialTypeId}
  financialTypeName:::{$financialTypeName}
  contactID:::{$contactID}
  contributionID:::{$contributionID}
  amount:::{$amount}
  amount_level:::{$amount_level}
  pay_later_receipt:::{$pay_later_receipt}
  headerStyle:::{$headerStyle}
  valueStyle:::{$valueStyle}
  labelStyle:::{$labelStyle}
  priceSetID:::{$priceSetID}
  currency:::{$currency}
  is_quick_config:::{$is_quick_config}
  getTaxDetails:::{$getTaxDetails}
  totalTaxAmount:::{$totalTaxAmount}
  is_monetary:::{$is_monetary}
  isShare:::{$isShare}
  honor_block_is_active:::{$honor_block_is_active}
  soft_credit_type:::{$soft_credit_type}
  is_recur:::{$is_recur}
  contributeMode:::{$contributeMode}
  trxn_id:::{$trxn_id}
  cancelSubscriptionUrl:::{$cancelSubscriptionUrl}
  updateSubscriptionBillingUrl:::{$updateSubscriptionBillingUrl}
  updateSubscriptionUrl:::{$updateSubscriptionUrl}
  priceset:::{$priceset}
  taxTerm:::{$taxTerm}
  pcpBlock:::{$pcpBlock}
  pcp_display_in_roll:::{$pcp_display_in_roll}
  pcp_roll_nickname:::{$pcp_roll_nickname}
  pcp_personal_note:::{$pcp_personal_note}
  onBehalfProfile_grouptitle:::{$onBehalfProfile_grouptitle}
  email:::{$email}
  contributionPageId:::{$contributionPageId}
  title:::{$title}
  isBillingAddressRequiredForPayLater:::{$isBillingAddressRequiredForPayLater}
  billingName:::{$billingName}
  address:::{$address}
  credit_card_type:::{$credit_card_type}
  credit_card_number:::{$credit_card_number}
  credit_card_exp_date:::{$credit_card_exp_date}
  selectPremium:::{$selectPremium}
  product_name:::{$product_name}
  option:::{$option}
  sku:::{$sku}
  start_date:::{$start_date}
  end_date:::{$end_date}
  is_deductible:::{$is_deductible}
  contact_email:::{$contact_email}
  contact_phone:::{$contact_phone}
  price:::{$price}
  customPre_grouptitle:::{$customPre_grouptitle}
  customPost_grouptitle:::{$customPost_grouptitle}
  contributionStatus:::{$contributionStatus}
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
 {foreach from=$dataArray item=value key=priceset}
    dataArray: priceset:::$priceset
    dataArray: value:::$value
 {/foreach}
 {foreach from=$honoreeProfile item=value key=label}
    honoreeProfile: label:::$label
    honoreeProfile: value:::$value
 {/foreach}
 {foreach from=$softCreditTypes item=softCreditType key=n}
    softCreditType:::$softCreditType
  {foreach from=$softCredits.$n item=value key=label}
     softCredits: label:::$label
     softCredits: value:::$value
  {/foreach}
 {/foreach}
 {foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
    onBehalfName:::$onBehalfName
    onBehalfValue:::$onBehalfValue
 {/foreach}
 {foreach from=$customPre item=customValue key=customName}
    customPre: customName:::$customName
    customPre: customValue:::$customValue
 {/foreach}
 {foreach from=$customPost item=customValue key=customName}
    customPost: customName:::$customName
    customPost: customValue:::$customValue
 {/foreach}
 {foreach from=$trackingFields item=trackingValue key=trackingName}
   trackingName:::$trackingName
   trackingValue:::$trackingValue
 {/foreach}

</body>
</html>
