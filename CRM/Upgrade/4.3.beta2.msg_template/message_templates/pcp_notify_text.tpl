===========================================================
{ts}Personal Campaign Page Notification{/ts}

===========================================================
{ts}Action{/ts}: {if $mode EQ 'Update'}{ts}Updated personal campaign page{/ts}{else}{ts}New personal campaign page{/ts}{/if}
{ts}Personal Campaign Page Title{/ts}: {$pcpTitle}
{ts}Current Status{/ts}: {$pcpStatus}
{capture assign=pcpURL}{crmURL p="civicrm/pcp/info" q="reset=1&id=`$pcpId`" h=0 a=1}{/capture}
{ts}View Page{/ts}:
>> {$pcpURL}

{ts}Supporter{/ts}: {$supporterName}
>> {$supporterUrl}

{ts}Linked to Contribution Page{/ts}: {$contribPageTitle}
>> {$contribPageUrl}

{ts}Manage Personal Campaign Pages{/ts}:
>> {$managePCPUrl}

