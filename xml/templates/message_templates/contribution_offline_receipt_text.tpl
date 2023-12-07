{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}

{if {contribution.contribution_page_id.receipt_text|boolean}}
{contribution.contribution_page_id.receipt_text}
{elseif {contribution.paid_amount|boolean}} {ts}Below you will find a receipt for this contribution.{/ts}
{/if}

===========================================================
{ts}Contribution Information{/ts}

===========================================================
{ts}Contributor{/ts}: {contact.display_name}
{if '{contribution.financial_type_id}'}
{ts}Financial Type{/ts}: {contribution.financial_type_id:label}
{/if}
{if $isShowLineItems}
---------------------------------------------------------
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_qty}{ts}Qty{/ts}{/capture}
{capture assign=ts_each}{ts}Each{/ts}{/capture}
{if $isShowTax && {contribution.tax_amount|boolean}}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{/if}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_qty|string_format:"%5s"} {$ts_each|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate} {$ts_taxAmount|string_format:"%10s"} {/if} {$ts_total|string_format:"%10s"}
----------------------------------------------------------
{foreach from=$lineItems item=line}
{capture assign=ts_item}{$line.title}{/capture}{$ts_item|truncate:30:"..."|string_format:"%-30s"} {$line.qty|string_format:"%5s"} {$line.unit_price|crmMoney:'{contribution.currency}'|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}}{$line.line_total|crmMoney:'{contribution.currency}'|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""} {$line.tax_rate|string_format:"%.2f"} %   {$line.tax_amount|crmMoney:'{contribution.currency}'|string_format:"%10s"} {else}                  {/if} {/if}   {$line.line_total_inclusive|crmMoney:'{contribution.currency}'|string_format:"%10s"}
{/foreach}
{/if}


{if $isShowTax && {contribution.tax_amount|boolean}}
{ts}Amount before Tax{/ts} : {contribution.tax_exclusive_amount}
{/if}
{foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else}{$taxTerm} {$taxDetail.percentage}%{/if} : {$taxDetail.amount|crmMoney:'{contribution.currency}'}
{/foreach}

{if $isShowTax}
{ts}Total Tax Amount{/ts} : {contribution.tax_amount}
{/if}
{ts}Total Amount{/ts} : {contribution.total_amount}
{if '{contribution.receive_date}'}
{ts}Contribution Date{/ts}: {contribution.receive_date|crmDate:"shortdate"}
{/if}
{if '{contribution.receipt_date}'}
{ts}Receipt Date{/ts}: {contribution.receipt_date|crmDate:"shortdate"}
{/if}
{if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
{ts}Paid By{/ts}: {contribution.payment_instrument_id:label}
{if '{contribution.check_number}'}
{ts}Check Number{/ts}: {contribution.check_number}
{/if}
{/if}
{if '{contribution.trxn_id}'}
{ts}Transaction ID{/ts}: {contribution.trxn_id}
{/if}

{if !empty($ccContribution)}
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
{if !empty($customGroup)}
{foreach from=$customGroup item=value key=customName}
===========================================================
{$customName}
===========================================================
{foreach from=$value item=v key=n}
{$n}: {$v}
{/foreach}
{/foreach}
{/if}

{if !empty($softCreditTypes) and !empty($softCredits)}
{foreach from=$softCreditTypes item=softCreditType key=n}
===========================================================
{$softCreditType}
===========================================================
{foreach from=$softCredits.$n item=value key=label}
{$label}: {$value}
{/foreach}
{/foreach}
{/if}

{if !empty($formValues.product_name)}
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
{if !empty($fulfilled_date)}
{ts}Sent{/ts}: {$fulfilled_date|crmDate}
{/if}
{/if}
