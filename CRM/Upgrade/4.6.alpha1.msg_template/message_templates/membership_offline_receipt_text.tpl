{if $formValues.receipt_text_signup}
{$formValues.receipt_text_signup}
{elseif $formValues.receipt_text_renewal}
{$formValues.receipt_text_renewal}
{else}{ts}Thank you for your support.{/ts}{/if}

{if ! $cancelled}{ts}Please print this receipt for your records.{/ts}


{/if}
{if !$lineItem}
===========================================================
{ts}Membership Information{/ts}

===========================================================
{ts}Membership Type{/ts}: {$membership_name}
{/if}
{if ! $cancelled}
{if !$lineItem}
{ts}Membership Start Date{/ts}: {$mem_start_date}
{ts}Membership End Date{/ts}: {$mem_end_date}
{/if}

{if $formValues.total_amount OR $formValues.total_amount eq 0 }
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{if $formValues.contributionType_name}
{ts}Financial Type{/ts}: {$formValues.contributionType_name}
{/if}
{if $lineItem}
{foreach from=$lineItem item=value key=priceset}
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_total}{ts}Fee{/ts}{/capture}
{if $dataArray}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{/if}
{capture assign=ts_start_date}{ts}Membership Start Date{/ts}{/capture}
{capture assign=ts_end_date}{ts}Membership End Date{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_total|string_format:"%10s"} {if $dataArray} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {$ts_total|string_format:"%10s"} {/if} {$ts_start_date|string_format:"%20s"} {$ts_end_date|string_format:"%20s"}
--------------------------------------------------------------------------------------------------

{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.line_total|crmMoney|string_format:"%10s"}  {if $dataArray} {$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate != "" || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:$currency|string_format:"%10s"}  {else}                  {/if}   {$line.line_total+$line.tax_amount|crmMoney|string_format:"%10s"} {/if} {$line.start_date|string_format:"%20s"} {$line.end_date|string_format:"%20s"}
{/foreach}
{/foreach}

{if $dataArray}
{ts}Amount before Tax{/ts}: {$formValues.total_amount-$totalTaxAmount|crmMoney:$currency}

{foreach from=$dataArray item=value key=priceset}
{if $priceset}
{$taxTerm} {$priceset|string_format:"%.2f"} %: {$value|crmMoney:$currency}
{elseif  $priceset == 0}
{ts}No{/ts} {$taxTerm}: {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}
--------------------------------------------------------------------------------------------------
{/if}

{if isset($totalTaxAmount)}
{ts}Total Tax Amount{/ts}: {$totalTaxAmount|crmMoney:$currency}
{/if}

{ts}Amount{/ts}: {$formValues.total_amount|crmMoney}
{if $receive_date}
{ts}Received Date{/ts}: {$receive_date|truncate:10:''|crmDate}
{/if}
{if $formValues.paidBy}
{ts}Paid By{/ts}: {$formValues.paidBy}
{if $formValues.check_number}
{ts}Check Number{/ts}: {$formValues.check_number}
{/if}
{/if}
{/if}
{/if}

{if $isPrimary }
{if $contributeMode ne 'notify' and !$isAmountzero and !$is_pay_later  }

===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}
{/if}

{if $contributeMode eq 'direct' and !$isAmountzero and !$is_pay_later}
===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}

{if $customValues}
===========================================================
{ts}Membership Options{/ts}

===========================================================
{foreach from=$customValues item=value key=customName}
 {$customName} : {$value}
{/foreach}
{/if}
