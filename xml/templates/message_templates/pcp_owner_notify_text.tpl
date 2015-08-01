===========================================================
{ts}Personal Campaign Page Owner Notification{/ts}

===========================================================
{ts}You have received a donation at your personal page{/ts}: {$page_title}
>> {$pcpInfoURL}

{ts}Your fundraising total has been updated.{/ts}
{ts}The donor's information is listed below.  You can choose to contact them and convey your thanks if you wish.{/ts}
{if $is_honor_roll_enabled}
    {ts}The donor's name has been added to your honor roll unless they asked not to be included.{/ts}
{/if}

{ts}Received{/ts}: {$receive_date|crmDate}

{ts}Amount{/ts}: {$total_amount|crmMoney:$currency}

{ts}Name{/ts}: {$donors_display_name}

{ts}Email{/ts}: {$donors_email}
