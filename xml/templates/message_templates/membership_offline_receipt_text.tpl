{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}

{if $userTextPlain}
{$userTextPlain}
{else}{ts}Thank you for this contribution.{/ts}{/if}

{if !$isShowLineItems}
===========================================================
{ts}Membership Information{/ts}

===========================================================
{ts}Membership Type{/ts}: {membership.membership_type_id:name}
{/if}
{if '{membership.status_id:name}' !== 'Cancelled'}
{if !$isShowLineItems}
{ts}Membership Start Date{/ts}: {membership.start_date|crmDate:"Full"}
{ts}Membership Expiration Date{/ts}: {membership.end_date|crmDate:"Full"}
{/if}

{if {contribution.total_amount|boolean}}
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{if {contribution.financial_type_id|boolean}}
{ts}Financial Type{/ts}: {contribution.financial_type_id:label}
{/if}
{if $isShowLineItems}
{capture assign=ts_item}{ts}Item{/ts}{/capture}
{capture assign=ts_total}{ts}Fee{/ts}{/capture}
{if $isShowTax && '{contribution.tax_amount|boolean}'}
{capture assign=ts_subtotal}{ts}Subtotal{/ts}{/capture}
{capture assign=ts_taxRate}{ts}Tax Rate{/ts}{/capture}
{capture assign=ts_taxAmount}{ts}Tax Amount{/ts}{/capture}
{capture assign=ts_total}{ts}Total{/ts}{/capture}
{/if}
{capture assign=ts_start_date}{ts}Membership Start Date{/ts}{/capture}
{capture assign=ts_end_date}{ts}Membership Expiration Date{/ts}{/capture}
{$ts_item|string_format:"%-30s"} {$ts_total|string_format:"%10s"} {if $isShowTax && {contribution.tax_amount|boolean}} {$ts_subtotal|string_format:"%10s"} {$ts_taxRate|string_format:"%10s"} {$ts_taxAmount|string_format:"%10s"} {$ts_total|string_format:"%10s"} {/if} {$ts_start_date|string_format:"%20s"} {$ts_end_date|string_format:"%20s"}
--------------------------------------------------------------------------------------------------

{foreach from=$lineItems item=line}
{line.title} {$line.line_total|crmMoney|string_format:"%10s"}  {if $isShowTax && {contribution.tax_amount|boolean}} {$line.line_total|crmMoney:'{contribution.currency}'|string_format:"%10s"} {if $line.tax_rate || $line.tax_amount != ""}  {$line.tax_rate|string_format:"%.2f"} %  {$line.tax_amount|crmMoney:'{contribution.currency}'|string_format:"%10s"}  {else}                  {/if}   {$line.line_total_inclusive|crmMoney|string_format:"%10s"} {/if} {$line.membership.start_date|string_format:"%20s"} {$line.membership.end_date|string_format:"%20s"}
{/foreach}

{if $isShowTax && {contribution.tax_amount|boolean}}
{ts}Amount before Tax:{/ts} {contribution.tax_exclusive_amount}

{foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else} {$taxTerm} {$taxDetail.percentage}%{/if}: {$taxDetail.amount|crmMoney:'{contribution.currency}'}
{/foreach}
{/if}
--------------------------------------------------------------------------------------------------
{/if}

{if {contribution.tax_amount|boolean}}
{ts}Total Tax Amount{/ts}: {contribution.tax_amount}
{/if}

{ts}Amount{/ts}: {contribution.total_amount}
{if {contribution.receive_date|boolean}}
{ts}Contribution Date{/ts}: {contribution.receive_date}
{/if}
{if {contribution.payment_instrument_id|boolean}}
{ts}Paid By{/ts}: {contribution.payment_instrument_id:label}
{if {contribution.check_number|boolean}}
{ts}Check Number{/ts}: {contribution.check_number|boolean}
{/if}
{/if}
{/if}
{/if}

{if !empty($isPrimary)}
{if !empty($billingName)}

===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}
{/if}

{if !empty($credit_card_type)}
===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{/if}

{if !empty($customValues)}
===========================================================
{ts}Membership Options{/ts}

===========================================================
{foreach from=$customValues item=value key=customName}
 {$customName} : {$value}
{/foreach}
{/if}
