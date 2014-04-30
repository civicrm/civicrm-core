 {if $receipt_text}
{$receipt_text}
{/if}

{ts}Please print this receipt for your records.{/ts}

===========================================================
{ts}Grant Application Information{/ts}

===========================================================

{if $default_amount_hidden>0}
{ts}Requested Amount{/ts}: {$default_amount_hidden|crmMoney:$currency}   
{/if}
{if $application_received_date}
{ts}Date{/ts}: {$application_received_date|crmDate}
{/if}
{ts}Registered Email{/ts}:    {$email}

{if $onBehalfProfile}
===========================================================
{ts}On Behalf Of{/ts}

===========================================================
{foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
{$onBehalfName}: {$onBehalfValue}
{/foreach}
{/if}

{if $customPre}
===========================================================
        {$customPre_grouptitle}
===========================================================
{foreach from=$customPre item=customValue key=customName}
{if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:  {$customValue}
{/if}
{/foreach}
{/if}

{if $customPost}
===========================================================
        {$customPost_grouptitle}
===========================================================
{foreach from=$customPost item=customValue key=customName}
{if ($trackingFields and ! in_array($customName, $trackingFields)) or ! $trackingFields}
{$customName}:    {$customValue}
{/if}
{/foreach}
{/if}
