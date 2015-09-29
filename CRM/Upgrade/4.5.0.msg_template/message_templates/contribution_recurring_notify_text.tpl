{ts 1=$displayName}Dear %1{/ts},

{if $recur_txnType eq 'START'}
{if $auto_renew_membership}
{ts}Thanks for your auto renew membership sign-up.{/ts}


{ts 1=$recur_frequency_interval 2=$recur_frequency_unit}This membership will be automatically renewed every %1 %2(s).{/ts}

{ts 1=$cancelSubscriptionUrl}This membership will be renewed automatically. You can cancel the auto-renewal option by <a href="%1">visiting this web page</a>.{/ts}

{ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}

{else}
{ts}Thanks for your recurring contribution sign-up.{/ts}


{ts 1=$recur_frequency_interval 2=$recur_frequency_unit 3=$recur_installments}This recurring contribution will be automatically processed every %1 %2(s){/ts}{if $recur_installments } {ts 1=$recur_installments} for a total of %1 installment(s){/ts}{/if}.

{ts}Start Date{/ts}:  {$recur_start_date|crmDate}

{ts 1=$cancelSubscriptionUrl}You can cancel the recurring contribution option by <a href="%1">visiting this web page</a>.{/ts}

{ts 1=$updateSubscriptionBillingUrl}You can update billing details for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}

{ts 1=$updateSubscriptionUrl}You can update recurring contribution amount or change the number of installments for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}
{/if}

{elseif $recur_txnType eq 'END'}
{if $auto_renew_membership}
{ts}Your auto renew membership sign-up has ended and your membership will not be automatically renewed.{/ts}


{else}
{ts}Your recurring contribution term has ended.{/ts}


{ts 1=$recur_installments}You have successfully completed %1 recurring contributions. Thank you for your support.{/ts}


==================================================
{ts 1=$recur_installments}Interval of Subscription for %1 installment(s){/ts}

==================================================
{ts}Start Date{/ts}: {$recur_start_date|crmDate}

{ts}End Date{/ts}: {$recur_end_date|crmDate}

{/if}
{/if}
