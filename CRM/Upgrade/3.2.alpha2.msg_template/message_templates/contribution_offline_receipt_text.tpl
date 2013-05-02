{if $formValues.receipt_text}
{$formValues.receipt_text}
{else}{ts}Thanks for your support.{/ts}{/if}

{ts}Please print this receipt for your records.{/ts}


===========================================================
{ts}Contribution Information{/ts}

===========================================================
{ts}Contribution Type{/ts}: {$formValues.contributionType_name}
{if $lineItem}
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
{/if}

{ts}Total Amount{/ts}: {$formValues.total_amount|crmMoney}
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
{if $formValues.honor_first_name}

===========================================================
{$formValues.honor_type}
===========================================================
{$formValues.honor_prefix} {$formValues.honor_first_name} {$formValues.honor_last_name}
{if $formValues.honor_email}
{ts}Honoree Email{/ts}: {$formValues.honor_email}
{/if}
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
