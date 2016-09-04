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
{if !$useForMember && $membership_amount }
{ts 1=$membership_name}%1 Membership{/ts}: {$membership_amount|crmMoney}
{if $amount}
{if ! $is_separate_payment }
{ts}Contribution Amount{/ts}: {$amount|crmMoney}
{else}
{ts}Additional Contribution{/ts}: {$amount|crmMoney}
{/if}
{/if}
-------------------------------------------
{ts}Total{/ts}: {$amount+$membership_amount|crmMoney}
{elseif !$useForMember && $lineItem and $priceSetID}
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
{if $useForMember && $lineItem}
{foreach from=$lineItem item=value key=priceset}
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_total}{ts}Fee{/ts}{/capture}
{capture assign=ts_start_date}{ts}Membership Start Date{/ts}{/capture}
{capture assign=ts_end_date}{ts}Membership End Date{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_total|string_format:"%10s"} {$ts_start_date|string_format:"%20s"} {$ts_end_date|string_format:"%20s"}
--------------------------------------------------------------------------------------------------

{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.line_total|crmMoney|string_format:"%10s"} {$line.start_date|string_format:"%20s"} {$line.end_date|string_format:"%20s"}
{/foreach}
{/foreach}
--------------------------------------------------------------------------------------------------
{/if}
{ts}Amount{/ts}: {$amount|crmMoney} {if $amount_level } - {$amount_level} {/if}
{/if}
{elseif $membership_amount}
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{ts 1=$membership_name}%1 Membership{/ts}: {$membership_amount|crmMoney}
{/if}
{if $receive_date}

{ts}Date{/ts}: {$receive_date|crmDate}
{/if}
{if $is_monetary and $trxn_id}
{ts}Transaction #{/ts}: {$trxn_id}

{/if}
{if $membership_trx_id}
{ts}Membership Transaction #{/ts}: {$membership_trx_id}

{/if}
{if $is_recur}
{if $contributeMode eq 'notify' or $contributeMode eq 'directIPN'}
{ts 1=$cancelSubscriptionUrl}This membership will be renewed automatically. You can cancel the auto-renewal option by visiting this web page: %1.{/ts}

{ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}
{/if}
{/if}

{if $honor_block_is_active }
===========================================================
{$honor_type}
===========================================================
{$honor_prefix} {$honor_first_name} {$honor_last_name}
{if $honor_email}
{ts}Honoree Email{/ts}: {$honor_email}
{/if}

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
{if $is_pay_later}
===========================================================
{ts}Registered Email{/ts}

===========================================================
{$email}
{elseif $amount GT 0 OR $membership_amount GT 0 }
===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

{$email}
{/if} {* End ! is_pay_later condition. *}
{/if}
{if $contributeMode eq 'direct' AND !$is_pay_later AND ( $amount GT 0 OR $membership_amount GT 0 ) }

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

{ts 1=$price|crmMoney}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}{/if}
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
