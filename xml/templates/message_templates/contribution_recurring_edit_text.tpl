{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}

{ts}Your recurring contribution has been updated as requested:{/ts}

{ts 1=$amount 2=$recur_frequency_interval 3=$recur_frequency_unit}Recurring contribution is for %1, every %2 %3(s){/ts}
{if $installments}{ts 1=$installments} for %1 installments.{/ts}{/if}

{ts 1=$receipt_from_email}If you have questions please contact us at %1.{/ts}
