{ts 1=$contact.display_name}Dear %1{/ts},

{ts 1=$next_payment|truncate:10:''|crmDate}This is a reminder that the next payment on your pledge is due on %1.{/ts}

===========================================================
{ts}Payment Due{/ts}

===========================================================
{ts}Amount Due{/ts}: {$amount_due|crmMoney:$currency}
{ts}Due Date{/ts}: {$scheduled_payment_date|truncate:10:''|crmDate}

{if $contribution_page_id}
{capture assign=contributionUrl}{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$contribution_page_id`&cid=`$contact.contact_id`&pledgeId=`$pledge_id`&cs=`$checksumValue`" a=true h=0}{/capture}
Click this link to go to a web page where you can make your payment online:
{$contributionUrl}
{else}
{ts}Please mail your payment to{/ts}:
{$domain.address}
{/if}

===========================================================
{ts}Pledge Information{/ts}

===========================================================
{ts}Pledge Received{/ts}: {$create_date|truncate:10:''|crmDate}
{ts}Total Pledge Amount{/ts}: {$amount|crmMoney:$currency}
{ts}Total Paid{/ts}: {$amount_paid|crmMoney:$currency}

{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions
or need to modify your payment schedule.{/ts}


{ts}Thank your for your generous support.{/ts}
