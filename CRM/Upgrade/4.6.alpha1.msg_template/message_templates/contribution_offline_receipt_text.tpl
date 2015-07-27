{if $formValues.receipt_text}
{$formValues.receipt_text}
{else}{ts}Thank you for your support.{/ts}{/if}

{ts}Please print this receipt for your records.{/ts}


===========================================================
{ts}Contribution Information{/ts}

===========================================================
{ts}Financial Type{/ts}: {$formValues.contributionType_name}
{if $lineItem}
{foreach from=$lineItem item=value key=priceset}
---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $getTaxDetails}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $getTaxDetails} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$value item=line}
{capture assign=ts_item}{if $line.html_type eq 'Text'}{$line.label}{else}{$line.field_title} - {$line.label}{/if} {if $line.description} {$line.description}{/if}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:$currency|string_format:"%10s"} {if $getTaxDetails}{$line.unit_price*$line.qty|crmMoney:$currency|string_format:"%10s"} {if $line.tax_rate != "" || $line.tax_amount != ""} {$line.tax_rate|string_format:"%.2f"} %   {$line.tax_amount|crmMoney:$currency|string_format:"%10s"} {else}                  {/if} {/if}   {$line.line_total+$line.tax_amount|crmMoney:$currency|string_format:"%10s"}
{/foreach}
{/foreach}
{/if}

{if $getTaxDetails && $dataArray}
{ts}Amount before Tax{/ts} : {$formValues.total_amount-$totalTaxAmount|crmMoney:$currency}

{foreach from=$dataArray item=value key=priceset}
{if $priceset ||  $priceset == 0 || $value != ''}
{$taxTerm} {$priceset|string_format:"%.2f"}% : {$value|crmMoney:$currency}
{else}
{ts}No{/ts} {$taxTerm} : {$value|crmMoney:$currency}
{/if}
{/foreach}
{/if}

{if isset($totalTaxAmount) && $totalTaxAmount !== 'null'}
{ts}Total Tax Amount{/ts} : {$totalTaxAmount|crmMoney:$currency}
{/if}
{ts}Total Amount{/ts} : {$formValues.total_amount|crmMoney:$currency}
{if $receive_date}
{ts}Received Date{/ts}: {$receive_date|truncate:10:''|crmDate}
{/if}
{if $receipt_date}
{ts}Receipt Date{/ts}: {$receipt_date|truncate:10:''|crmDate}
{/if}
{if $formValues.paidBy and !$formValues.hidden_CreditCard}
{ts}Paid By{/ts}: {$formValues.paidBy}
{if $formValues.check_number}
{ts}Check Number{/ts}: {$formValues.check_number}
{/if}
{/if}
{if $formValues.trxn_id}
{ts}Transaction ID{/ts}: {$formValues.trxn_id}
{/if}

{if $ccContribution}
===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{if $customGroup}
{foreach from=$customGroup item=value key=customName}
===========================================================
{$customName}
===========================================================
{foreach from=$value item=v key=n}
{$n}: {$v}
{/foreach}
{/foreach}
{/if}

{if $softCreditTypes and $softCredits}
{foreach from=$softCreditTypes item=softCreditType key=n}
===========================================================
{$softCreditType}
===========================================================
{foreach from=$softCredits.$n item=value key=label}
{$label}: {$value}
{/foreach}
{/foreach}
{/if}

{if $formValues.product_name}
===========================================================
{ts}Premium Information{/ts}

===========================================================
{$formValues.product_name}
{if $formValues.product_option}
{ts}Option{/ts}: {$formValues.product_option}
{/if}
{if $formValues.product_sku}
{ts}SKU{/ts}: {$formValues.product_sku}
{/if}
{if $fulfilled_date}
{ts}Sent{/ts}: {$fulfilled_date|crmDate}
{/if}
{/if}
