{if $formValues.receipt_text_signup}
{$formValues.receipt_text_signup}
{elseif $formValues.receipt_text_renewal}
{$formValues.receipt_text_renewal}
{else}{ts}Thanks for your support.{/ts}{/if}

{if ! $cancelled}{ts}Please print this receipt for your records.{/ts}


{/if}
===========================================================
{ts}Membership Information{/ts}

===========================================================
{ts}Membership Type{/ts}: {$membership_name}
{if ! $cancelled}
{ts}Membership Start Date{/ts}: {$mem_start_date}
{ts}Membership End Date{/ts}: {$mem_end_date}
{if $formValues.total_amount}
===========================================================
{ts}Membership Fee{/ts}

===========================================================
{if $formValues.contributionType_name}
{ts}Contribution Type{/ts}: {$formValues.contributionType_name}
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
