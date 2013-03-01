{ts}Dear supporter{/ts},
{ts 1="$contribPageTitle"}Thanks for creating a personal campaign page in support of %1.{/ts}

{if $pcpStatus eq 'Approved'}
====================
{ts}Promoting Your Page{/ts}

====================
{if $isTellFriendEnabled}

{ts}You can begin your fundraising efforts using our "Tell a Friend" form{/ts}:

1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser and follow the prompts{/ts}:
{$pcpTellFriendURL}
{else}

{ts}Send email to family, friends and colleagues with a personal message about this campaign.{/ts}
{ts}Include this link to your fundraising page in your emails{/ts}:
{$pcpInfoURL}
{/if}

===================
{ts}Managing Your Page{/ts}

===================
{ts}Whenever you want to preview, update or promote your page{/ts}:
1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser to go to your page{/ts}:
{$pcpInfoURL}

{ts}When you view your campaign page WHILE LOGGED IN, the page includes links to edit
your page, tell friends, and update your contact info.{/ts}


{elseif $pcpStatus EQ 'Waiting Review'}
{ts}Your page requires administrator review before you can begin your fundraising efforts.{/ts}


{ts}A notification email has been sent to the site administrator, and you will receive another notification from them as soon as the review process is complete.{/ts}


{ts}You can still preview your page prior to approval{/ts}:
1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser{/ts}:
{$pcpInfoURL}

{/if}
{if $pcpNotifyEmailAddress}
{ts}Questions? Send email to{/ts}:
{$pcpNotifyEmailAddress}
{/if}
