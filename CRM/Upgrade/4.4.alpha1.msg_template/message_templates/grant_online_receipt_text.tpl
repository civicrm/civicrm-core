 {if $receipt_text}
{$receipt_text}
{/if}

{ts}Please print this receipt for your records.{/ts}

===========================================================
{ts}Grant Application Information{/ts}

===========================================================

{if $default_amount_hidden>0}
{ts}Requested Amount{/ts}: {$default_amount_hidden|crmMoney:$currency}   
{/if}
{if $application_received_date}
{ts}Date{/ts}: {$application_received_date|crmDate}
{/if}
{ts}Registered Email{/ts}:    {$email}
{if $contributeMode eq 'direct' AND !$is_pay_later AND $amount GT 0}
{ts}Credit Card Information{/ts}:  {$credit_card_type}
					            {$credit_card_number}<br />
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{if $selectPremium}
{ts}Premium Information{/ts} : {$product_name}
{if $option}
{ts}Option{/ts} :      {$option}
{/if}
{if $sku}
{ts}SKU{/ts}:  {$sku}
{/if}
{if $start_date}
{ts}Start Date{/ts}:  {$start_date|crmDate}
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
{ts 1=$price|crmMoney:$currency}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}
{/if}
{/if}

{if $customPre}
===========================================================
        {$customPre_grouptitle}
===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:  {$customValue}
{/if}
{/foreach}
{/if}

{if $customPost}
===========================================================
        {$customPost_grouptitle}
===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:    {$customValue}
{/if}
{/foreach}
{/if}
