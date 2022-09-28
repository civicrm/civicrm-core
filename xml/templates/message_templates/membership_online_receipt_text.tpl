{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}
{if !empty($receipt_text)}
{$receipt_text}
{/if}
{if $is_pay_later}

===========================================================
{if isset($pay_later_receipt)}{$pay_later_receipt}{/if}
===========================================================
{/if}

{if $membership_assign && !$useForMember}
===========================================================
{ts}Membership Information{/ts}

===========================================================
{ts}Membership Type{/ts}: {$membership_name}
{if $mem_start_date}{ts}Membership Start Date{/ts}: {$mem_start_date|crmDate}
{/if}
{if $mem_end_date}{ts}Membership End Date{/ts}: {$mem_end_date|crmDate}
{/if}

{/if}
{if $amount}
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{if !$useForMember && isset($membership_amount) && !empty($is_quick_config)}
{ts 1=$membership_name}%1 Membership{/ts}: {$membership_amount|crmMoney}
{if $amount && !$is_separate_payment }
{ts}Contribution Amount{/ts}: {$amount|crmMoney}
-------------------------------------------
{ts}Total{/ts}: {$amount+$membership_amount|crmMoney}
{/if}
{elseif !$useForMember && !empty($lineItem) and !empty($priceSetID) & empty($is_quick_config)}
{foreach from=$lineItem item=value key=priceset}
---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$value item=line}
{$line.description|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney|string_format:"%10s"} {$line.line_total|crmMoney|string_format:"%10s"}
{/foreach}
{/foreach}

{ts}Total Amount{/ts}: {$amount|crmMoney}
{else}
{if $useForMember && $lineItem && empty($is_quick_config)}
{foreach from=$lineItem item=value key=priceset}
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_total}{ts}Fee{/ts}{/capture}
{if !empty($dataArray)}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{/if}
{capture assign=ts_start_date}{ts}Membership Start Date{/ts}{/capture}
{capture assign=ts_end_date}{ts}Membership End Date{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_total|string_format:"%10s"} {if !empty($dataArray)} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {$ts_total|string_format:"%10s"} {/if} {$ts_start_date|string_format:"%20s"} {$ts_end_date|string_format:"%20s"}
--------------------------------------------------------------------------------------------------

{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.line_total|crmMoney|string_format:"%10s"}  {if !empty($dataArray)} {$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if isset($line.tax_rate) and ($line.tax_rate != "" || $line.tax_amount != "")}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}   {$line.line_total+$line.tax_amount|crmMoney|string_format:"%10s"} {/if} {$line.start_date|string_format:"%20s"} {$line.end_date|string_format:"%20s"}
{/foreach}
{/foreach}

{if !empty($dataArray)}
{ts}Amount before Tax{/ts}: {$amount-$totalTaxAmount|crmMoney:$currency}

{foreach from=$dataArray item=value key=priceset}
{if $priceset || $priceset == 0}
{if isset($taxTerm)}{$taxTerm}{/if} {$priceset|string_format:"%.2f"}%: {$value|crmMoney:$currency}
{else}
{ts}No{/ts} {if isset($taxTerm)}{$taxTerm}{/if}: {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}
--------------------------------------------------------------------------------------------------
{/if}

{if isset($totalTaxAmount)}
{ts}Total Tax Amount{/ts}: {$totalTaxAmount|crmMoney:$currency}
{/if}

{ts}Amount{/ts}: {$amount|crmMoney} {if isset($amount_level) } - {$amount_level} {/if}
{/if}
{elseif isset($membership_amount)}
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{ts 1=$membership_name}%1 Membership{/ts}: {$membership_amount|crmMoney}
{/if}

{if !empty($receive_date)}

{ts}Date{/ts}: {$receive_date|crmDate}
{/if}
{if !empty($is_monetary) and !empty($trxn_id)}
{ts}Transaction #{/ts}: {$trxn_id}

{/if}
{if !empty($membership_trx_id)}
{ts}Membership Transaction #{/ts}: {$membership_trx_id}

{/if}
{if !empty($is_recur)}
{ts}This membership will be renewed automatically.{/ts}
{if $cancelSubscriptionUrl}

{ts 1=$cancelSubscriptionUrl}You can cancel the auto-renewal option by visiting this web page: %1.{/ts}

{/if}

{if $updateSubscriptionBillingUrl}

{ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}
{/if}
{/if}

{if $honor_block_is_active }
===========================================================
{$soft_credit_type}
===========================================================
{foreach from=$honoreeProfile item=value key=label}
{$label}: {$value}
{/foreach}

{/if}
{if !empty($pcpBlock)}
===========================================================
{ts}Personal Campaign Page{/ts}

===========================================================
{ts}Display In Honor Roll{/ts}: {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}

{if $pcp_roll_nickname}{ts}Nickname{/ts}: {$pcp_roll_nickname}{/if}

{if $pcp_personal_note}{ts}Personal Note{/ts}: {$pcp_personal_note}{/if}

{/if}
{if !empty($onBehalfProfile)}
===========================================================
{ts}On Behalf Of{/ts}

===========================================================
{foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
{$onBehalfName}: {$onBehalfValue}
{/foreach}
{/if}

{if !empty($billingName)}
===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

{$email}
{elseif !empty($email)}
===========================================================
{ts}Registered Email{/ts}

===========================================================
{$email}
{/if} {* End billingName or email *}
{if !empty($credit_card_type)}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}

{if !empty($selectPremium)}
===========================================================
{ts}Premium Information{/ts}

===========================================================
{$product_name}
{if $option}
{ts}Option{/ts}: {$option}
{/if}
{if $sku}
{ts}SKU{/ts}: {$sku}
{/if}
{if $start_date}
{ts}Start Date{/ts}: {$start_date|crmDate}
{/if}
{if $end_date}
{ts}End Date{/ts}: {$end_date|crmDate}
{/if}
{if !empty($contact_email) OR !empty($contact_phone)}

{ts}For information about this premium, contact:{/ts}

{if !empty($contact_email)}
  {$contact_email}
{/if}
{if !empty($contact_phone)}
  {$contact_phone}
{/if}
{/if}
{if !empty($is_deductible) AND !empty($price)}

{ts 1=$price|crmMoney}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}{/if}
{/if}

{if !empty($customPre)}
===========================================================
{$customPre_grouptitle}

===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}


{if !empty($customPost)}
===========================================================
{$customPost_grouptitle}

===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ( !empty($trackingFields) and ! in_array( $customName, $trackingFields ) ) or empty($trackingFields)}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}
