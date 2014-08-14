{ts}A contribution / membership signup was made on behalf of the organization listed below.{/ts}
{ts}The information provided matched multiple existing database records based on the configured
Duplicate Matching Rules for your site.{/ts}

{ts}Organization Name{/ts}: {$onBehalfName}
{ts}Organization Email{/ts}: {$onBehalfEmail}
{ts}Organization Contact ID{/ts}: {$onBehalfID}

{ts}If you think this may be a duplicate contact which should be merged with an existing record -
Go to "CiviCRM >> Administer CiviCRM >> Find and Merge Duplicate Contacts". Use the strict
rule for Organizations to find the potential duplicates and merge them if appropriate.{/ts}

{if $receiptMessage}
###########################################################
{ts}Copy of Contribution Receipt{/ts}

###########################################################
{$receiptMessage}

{/if}
