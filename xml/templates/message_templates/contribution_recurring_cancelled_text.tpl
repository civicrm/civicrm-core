{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}

{ts 1=$amount 2=$recur_frequency_interval 3=$recur_frequency_unit}Your recurring contribution of %1, every %2 %3 has been cancelled as requested.{/ts}
