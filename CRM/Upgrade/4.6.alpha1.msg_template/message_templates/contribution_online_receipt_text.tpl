{if $receipt_text}
{$receipt_text}
{/if}
{if $is_pay_later}

===========================================================
{$pay_later_receipt}
===========================================================
{else}

{ts}Please print this receipt for your records.{/ts}
{/if}

{if $amount}
===========================================================
{ts}Contribution Information{/ts}

===========================================================
{if $lineItem and $priceSetID and !$is_quick_config}
{foreach from=$lineItem item=value key=priceset}
---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $dataArray}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $dataArray} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {if $dataArray}{$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate != "" || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if}  {/if} {$line.line_total+$line.tax_amount|crmMoney:$currency|string_format:"%10s"}
{/foreach}
{/foreach}

{if $dataArray}
{ts}Amount before Tax{/ts}: {$amount-$totalTaxAmount|crmMoney:$currency}

{foreach from=$dataArray item=value key=priceset}
{if $priceset || $priceset == 0}
{$taxTerm} {$priceset|string_format:"%.2f"}%: {$value|crmMoney:$currency}
{else}
{ts}No{/ts} {$taxTerm}: {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}

{if $totalTaxAmount}
{ts}Total Tax Amount{/ts}: {$totalTaxAmount|crmMoney:$currency}
{/if}

{ts}Total Amount{/ts}: {$amount|crmMoney:$currency}
{else}
{ts}Amount{/ts}: {$amount|crmMoney:$currency} {if $amount_level } - {$amount_level} {/if}
{/if}
{/if}
{if $receive_date}

{ts}Date{/ts}: {$receive_date|crmDate}
{/if}
{if $is_monetary and $trxn_id}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}

{if $is_recur and ($contributeMode eq 'notify' or $contributeMode eq 'directIPN')}
{ts}This is a recurring contribution. You can cancel future contributions at:{/ts}

{$cancelSubscriptionUrl}

{if $updateSubscriptionBillingUrl}
{ts}You can update billing details for this recurring contribution at:{/ts}

{$updateSubscriptionBillingUrl}

{/if}
{ts}You can update recurring contribution amount or change the number of installments for this recurring contribution at:{/ts}

{$updateSubscriptionUrl}

{/if}

{if $honor_block_is_active}
===========================================================
{$soft_credit_type}
===========================================================
{foreach from=$honoreeProfile item=value key=label}
{$label}: {$value}
{/foreach}
{elseif $softCreditTypes and $softCredits}
{foreach from=$softCreditTypes item=softCreditType key=n}
===========================================================
{$softCreditType}
===========================================================
{foreach from=$softCredits.$n item=value key=label}
{$label}: {$value}
{/foreach}
{/foreach}
{/if}
{if $pcpBlock}
===========================================================
{ts}Personal Campaign Page{/ts}

===========================================================
{ts}Display In Honor Roll{/ts}: {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}

{if $pcp_roll_nickname}{ts}Nickname{/ts}: {$pcp_roll_nickname}{/if}

{if $pcp_personal_note}{ts}Personal Note{/ts}: {$pcp_personal_note}{/if}

{/if}
{if $onBehalfProfile}
===========================================================
{ts}On Behalf Of{/ts}

===========================================================
{foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
{$onBehalfName}: {$onBehalfValue}
{/foreach}
{/if}

{if !( $contributeMode eq 'notify' OR $contributeMode eq 'directIPN' ) and $is_monetary}
{if $is_pay_later && !$isBillingAddressRequiredForPayLater}
===========================================================
{ts}Registered Email{/ts}

===========================================================
{$email}
{elseif $amount GT 0}
===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

{$email}
{/if} {* End ! is_pay_later condition. *}
{/if}
{if $contributeMode eq 'direct' AND !$is_pay_later AND $amount GT 0}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}

{if $selectPremium }
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
{if $contact_email OR $contact_phone}

{ts}For information about this premium, contact:{/ts}

{if $contact_email}
  {$contact_email}
{/if}
{if $contact_phone}
  {$contact_phone}
{/if}
{/if}
{if $is_deductible AND $price}

{ts 1=$price|crmMoney:$currency}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}{/if}
{/if}

{if $customPre}
===========================================================
{$customPre_grouptitle}

===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}


{if $customPost}
===========================================================
{$customPost_grouptitle}

===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ( $trackingFields and ! in_array( $customName, $trackingFields ) ) or ! $trackingFields}
 {$customName}: {$customValue}
{/if}
{/foreach}
{/if}
