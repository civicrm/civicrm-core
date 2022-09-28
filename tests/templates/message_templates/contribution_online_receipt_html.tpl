<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
</head>
<body>
  receive_date:::{$receive_date}
  receipt_date:::{$receipt_date}
  {if !empty($receipt_text)}
  receipt_text:::{$receipt_text}
  {/if}
  is_pay_later:::{$is_pay_later}
  financialTypeId:::{$financialTypeId}
  financialTypeName:::{$financialTypeName}
  contactID:::{$contactID}
  contributionID:::{$contributionID}
  amount:::{$amount}
  {if !empty($amount_level)}
  amount_level:::{$amount_level}
  {/if}
  {if !empty($pay_later_receipt)}
  pay_later_receipt:::{$pay_later_receipt}
  {/if}
  {if !empty($headerstyle)}
  headerStyle:::{$headerStyle}
  {/if}
  {if !empty($valueStyle)}
  valueStyle:::{$valueStyle}
  {/if}
  {if !empty($labelStyle)}
  labelStyle:::{$labelStyle}
  {/if}
  priceSetID:::{$priceSetID}
  currency:::{$currency}
  {if !empty($is_quick_config)}
  is_quick_config:::{$is_quick_config}
  {/if}
  {if !empty($getTaxDetails)}
  getTaxDetails:::{$getTaxDetails}
  totalTaxAmount:::{$totalTaxAmount}
  {/if}
  {if !empty($is_monetary)}
  is_monetary:::{$is_monetary}
  {/if}
  {if !empty($isShare)}
  isShare:::{$isShare}
  {/if}
  honor_block_is_active:::{$honor_block_is_active}
  {if $honor_block_is_active}
  soft_credit_type:::{$soft_credit_type}
  {/if}
  {if !empty($is_recur)}
  is_recur:::{$is_recur}
  {/if}
  {if !empty($trxn_id)}
  trxn_id:::{$trxn_id}
  {/if}
  {if !empty($cancelSubscriptionUrl)}
  cancelSubscriptionUrl:::{$cancelSubscriptionUrl}
  updateSubscriptionBillingUrl:::{$updateSubscriptionBillingUrl}
  updateSubscriptionUrl:::{$updateSubscriptionUrl}
  {/if}
  {if !empty($priceset)}
  priceset:::{$priceset}
  {/if}
  taxTerm:::{$taxTerm}
  {if !empty($pcpBlock)}
  pcpBlock:::{$pcpBlock}
  pcp_display_in_roll:::{$pcp_display_in_roll}
  pcp_roll_nickname:::{$pcp_roll_nickname}
  pcp_personal_note:::{$pcp_personal_note}
  {/if}
  {if !empty($onBehalfProfile_grouptitle)}
  onBehalfProfile_grouptitle:::{$onBehalfProfile_grouptitle}
  {/if}
  email:::{$email}
  {if !empty($contributionPageId)}
  contributionPageId:::{$contributionPageId}
  title:::{$title}
  {/if}
  {if !empty($isBillingAddressRequiredForPayLater)}
  isBillingAddressRequiredForPayLater:::{$isBillingAddressRequiredForPayLater}
  {/if}
  billingName:::{$billingName}
  address:::{$address}
  {if !empty($credit_card_type)}
  credit_card_type:::{$credit_card_type}
  credit_card_number:::{$credit_card_number}
  credit_card_exp_date:::{$credit_card_exp_date}
  {/if}
  {if !empty($selectPremium)}
  selectPremium:::{$selectPremium}
  product_name:::{$product_name}
  option:::{$option}
  sku:::{$sku}
  {/if}
  {if !empty($start_date)}
  start_date:::{$start_date}
  end_date:::{$end_date}
  {/if}
  {if !empty($is_deductible)}
  is_deductible:::{$is_deductible}
  {/if}
  {if !empty($contact_email)}
  contact_email:::{$contact_email}
  {/if}
  {if !empty($contact_phone)}
  contact_phone:::{$contact_phone}
  {/if}
  {if !empty($price)}
  price:::{$price}
  {/if}
  {if !empty($customPre_grouptitle)}
  customPre_grouptitle:::{$customPre_grouptitle}
  {/if}
  {if !empty($customPost_grouptitle)}
  customPost_grouptitle:::{$customPost_grouptitle}
  {/if}
  contributionStatus:::{$contributionStatus}
 {if !empty($lineItem)}
 {foreach from=$lineItem item=value key=priceset}
  {foreach from=$value item=line}
     line.html_type:::{$line.html_type}
     line.label:::{$line.label}
     line.field_title:::{$line.field_title}
     line.description:::{$line.description}
     line.qty:::{$line.qty}
     line.unit_price:::{$line.unit_price}
     {if !empty($line.tax_rate)}
     line.tax_rate:::{$line.tax_rate}
     line.tax_amount:::{$line.tax_amount}
     {/if}
     line.line_total:::{$line.line_total}
  {/foreach}
 {/foreach}
 {/if}
 {if !empty($dataArray)}
 {foreach from=$dataArray item=value key=priceset}
    dataArray: priceset:::$priceset
    dataArray: value:::$value
 {/foreach}
 {/if}
 {if !empty($honoreeProfile)}
 {foreach from=$honoreeProfile item=value key=label}
    honoreeProfile: label:::$label
    honoreeProfile: value:::$value
 {/foreach}
 {/if}
 {if !empty($softCreditTypes)}
 {foreach from=$softCreditTypes item=softCreditType key=n}
    softCreditType:::$softCreditType
  {foreach from=$softCredits.$n item=value key=label}
     softCredits: label:::$label
     softCredits: value:::$value
  {/foreach}
 {/foreach}
 {/if}
 {if !empty($onBehalfProfile)}
 {foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
    onBehalfName:::$onBehalfName
    onBehalfValue:::$onBehalfValue
 {/foreach}
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
 {if !empty($trackingFields)}
 {foreach from=$trackingFields item=trackingValue key=trackingName}
   trackingName:::$trackingName
   trackingValue:::$trackingValue
 {/foreach}
 {/if}

</body>
</html>
