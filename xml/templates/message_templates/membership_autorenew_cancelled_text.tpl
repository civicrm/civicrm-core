{ts 1=$membershipType}The automatic renewal of your %1 membership has been cancelled as requested. This does not affect the status of your membership - you will receive a separate notification when your membership is up for renewal.{/ts}

===========================================================
{ts}Membership Information{/ts}

===========================================================
{ts}Membership Status{/ts}: {$membership_status}
{if $mem_start_date}{ts}Membership Start Date{/ts}: {$mem_start_date|crmDate}
{/if}
{if $mem_end_date}{ts}Membership End Date{/ts}: {$mem_end_date|crmDate}
{/if}

