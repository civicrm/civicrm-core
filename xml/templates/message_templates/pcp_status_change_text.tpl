{if $pcpStatus eq 'Approved'}
============================
{ts}Your Personal Campaign Page{/ts}

============================

{ts}Your personal campaign page has been approved and is now live.{/ts}

{ts}Whenever you want to preview, update or promote your page{/ts}:
1. {ts}Login to your account at{/ts}:
{$loginUrl}

2. {ts}Click or paste this link into your browser to go to your page{/ts}:
{$pcpInfoURL}

{ts}When you view your campaign page WHILE LOGGED IN, the page includes links to edit
your page, tell friends, and update your contact info.{/ts}

{if $isTellFriendEnabled}

{ts}After logging in, you can use this form to promote your fundraising page{/ts}:
{$pcpTellFriendURL}

{/if}

{if $pcpNotifyEmailAddress}
{ts}Questions? Send email to{/ts}:
{$pcpNotifyEmailAddress}
{/if}

{* Rejected message *}
{elseif $pcpStatus eq 'Not Approved'}
============================
{ts}Your Personal Campaign Page{/ts}

============================

{ts}Your personal campaign page has been reviewed. There were some issues with the content
which prevented us from approving the page. We are sorry for any inconvenience.{/ts}

{if $pcpNotifyEmailAddress}

{ts}Please contact our site administrator for more information{/ts}:
{$pcpNotifyEmailAddress}
{/if}

{/if}
