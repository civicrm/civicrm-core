{if $receiptType EQ 'contribution'}
{ts}Contribution Receipt{/ts}
{elseif $receiptType EQ 'membership signup'}
{ts}Membership Confirmation and Receipt{/ts}
{elseif $receiptType EQ 'membership renewal'}
{ts}Membership Renewal Confirmation and Receipt{/ts}
{else}
{ts}Receipt{/ts}
{/if}
