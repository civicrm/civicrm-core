===========================================================
{ts}Activity Summary{/ts} - {$activityTypeName}
===========================================================
{if !empty($isCaseActivity)}
{ts}Your Case Role(s){/ts} : {$contact.role|default:''}
{if !empty($manageCaseURL)}
{ts}Manage Case{/ts} : {$manageCaseURL}
{/if}
{/if}

{if !empty($editActURL)}
{ts}Edit activity{/ts} : {$editActURL}
{/if}
{if !empty($viewActURL)}
{ts}View activity{/ts} : {$viewActURL}
{/if}

{foreach from=$activity.fields item=field}
{if $field.type eq 'Date'}
{$field.label}{if !empty($field.category)}({$field.category}){/if} : {$field.value|crmDate:$config->dateformatDatetime}
{else}
{$field.label}{if !empty($field.category)}({$field.category}){/if} : {$field.value}
{/if}
{/foreach}

{if !empty($activity.customGroups)}
{foreach from=$activity.customGroups key=customGroupName item=customGroup}
==========================================================
{$customGroupName}
==========================================================
{foreach from=$customGroup item=field}
{if $field.type eq 'Date'}
{$field.label} : {$field.value|crmDate:$config->dateformatDatetime}
{else}
{$field.label} : {$field.value}
{/if}
{/foreach}

{/foreach}
{/if}
