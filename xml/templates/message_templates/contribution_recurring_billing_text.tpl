{ts 1=$contact.display_name}Dear %1{/ts},

{ts 1=$amount 2=$recur_frequency_interval 3=$recur_frequency_unit}Billing details for your recurring contribution of %1, every %2 %3 have been updated.{/ts}

===========================================================
{ts}Billing Name and Address{/ts}

===========================================================
{$billingName}
{$address}

{$email}

===========================================================
{ts}Credit Card Information{/ts}

===========================================================
{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}


{ts 1=$receipt_from_email}If you have questions please contact us at %1{/ts}