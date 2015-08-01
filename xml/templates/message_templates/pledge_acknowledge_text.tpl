{ts 1=$contact.display_name}Dear %1{/ts},

{ts}Thank you for your generous pledge. Please print this acknowledgment for your records.{/ts}

===========================================================
{ts}Pledge Information{/ts}

===========================================================
{ts}Pledge Received{/ts}: {$create_date|truncate:10:''|crmDate}
{ts}Total Pledge Amount{/ts}: {$total_pledge_amount|crmMoney:$currency}

===========================================================
{ts}Payment Schedule{/ts}

===========================================================
{ts 1=$scheduled_amount|crmMoney:$currency 2=$frequency_interval 3=$frequency_unit 4=$installments}%1 every %2 %3 for %4 installments.{/ts}

{if $frequency_day}

{ts 1=$frequency_day 2=$frequency_unit}Payments are due on day %1 of the %2.{/ts}
{/if}

{if $payments}
{assign var="count" value="1"}
{foreach from=$payments item=payment}

{ts 1=$count}Payment %1{/ts}: {$payment.amount|crmMoney:$currency} {if $payment.status eq 1}{ts}paid{/ts} {$payment.receive_date|truncate:10:''|crmDate}{else}{ts}due{/ts} {$payment.due_date|truncate:10:''|crmDate}{/if}
{assign var="count" value=`$count+1`}
{/foreach}
{/if}


{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions
or need to modify your payment schedule.{/ts}

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
