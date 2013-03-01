{ts 1=$displayName}Dear %1{/ts},

{if $recur_txnType eq 'START'}

{ts}Thanks for your recurring contribution sign-up.{/ts}


{ts 1=$recur_frequency_interval 2=$recur_frequency_unit 3=$recur_installments}This recurring contribution will be automatically processed every %1 %2(s) for a total %3 installment(s).{/ts}


{ts}Start Date{/ts}:  {$recur_start_date|crmDate}


{ts 1=$receipt_from_name 2=$receipt_from_email}You have pledged to make this recurring donation. You will be charged periodically (per frequency listed above), and you will receive an email receipt from %1 following each charge. These recurring donations will continue until you explicitly cancel the donation. You may change or cancel your recurring donation at anytime by logging into your account. If you have questions about recurring donations please contact us at %2.{/ts}

{elseif $recur_txnType eq 'END'}

{ts}Your recurring contribution term has ended.{/ts}


{ts 1=$recur_installments}You have successfully completed %1 recurring contributions. Thank you for your support.{/ts}


==================================================
{ts 1=$recur_installments}Interval of Subscription for %1 installment(s){/ts}

==================================================
{ts}Start Date{/ts}: {$recur_start_date|crmDate}

{ts}End Date{/ts}: {$recur_end_date|crmDate}

{/if}
