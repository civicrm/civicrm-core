{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}
{if !empty($receipt_text)}
{$receipt_text}
{/if}
{if $is_pay_later}

===========================================================
{$pay_later_receipt}
===========================================================
{/if}

{if '{contribution.total_amount|raw}' !== '0.00'}
===========================================================
{ts}Contribution Information{/ts}

===========================================================
{if $isShowLineItems}

---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$lineItems item=line}
{capture assign=ts_item}{$line.title}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {if !empty($dataArray)}{$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}  {/if} {$line.line_total+$line.tax_amount|crmMoney:$currency|string_format:"%10s"}
{/foreach}

{if $isShowTax && '{contribution.tax_amount|raw}' !== '0.00'}
{ts}Amount before Tax{/ts}: {$amount-$totalTaxAmount|crmMoney:$currency}
  {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
    {if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if} : {$taxDetail.amount|crmMoney:'{contribution.currency}'}
  {/foreach}
{/if}

{if $isShowTax}
{ts}Total Tax Amount{/ts}: {contribution.tax_amount|crmMoney}
{/if}

{ts}Total Amount{/ts}: {contribution.total_amount}
{else}
{ts}Amount{/ts}: {contribution.total_amount} {if '{contribution.amount_level}'} - {contribution.amount_level}{/if}
{/if}
{/if}
{if !empty($receive_date)}

{ts}Date{/ts}: {$receive_date|crmDate}
{/if}
{if !empty($is_monetary) and !empty($trxn_id)}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}

{if !empty($is_recur)}
{ts}This is a recurring contribution.{/ts}

{if $cancelSubscriptionUrl}
{ts}You can cancel future contributions at:{/ts}

{$cancelSubscriptionUrl}

{/if}

{if $updateSubscriptionBillingUrl}
{ts}You can update billing details for this recurring contribution at:{/ts}

{$updateSubscriptionBillingUrl}

{/if}

{if $updateSubscriptionUrl}
{ts}You can update recurring contribution amount or change the number of installments for this recurring contribution at:{/ts}

{$updateSubscriptionUrl}

{/if}
{/if}

{if $honor_block_is_active}
===========================================================
{$soft_credit_type}
===========================================================
{foreach from=$honoreeProfile item=value key=label}
{$label}: {$value}
{/foreach}
{elseif !empty($softCreditTypes) and !empty($softCredits)}
{foreach from=$softCreditTypes item=softCreditType key=n}
===========================================================
{$softCreditType}
===========================================================
{foreach from=$softCredits.$n item=value key=label}
{$label}: {$value}
{/foreach}
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
{/if} {* End billingName or Email*}
{if !empty($credit_card_type)}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}

{if !empty($selectPremium )}
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

{ts 1=$price|crmMoney:$currency}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}{/if}
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
