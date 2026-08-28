{if $group.help_pre}
  <div class="af-markup">{$group.help_pre}</div>
{/if}

{foreach from=$group.fields item=field}
  <af-field
    {* Don't prepend the group name for multi-record groups because it will be the form entity itself *}
    name="{if !$group.is_multiple}{$group.name}.{/if}{$field.name}"
    {* Default field label includes the custom group name. Override that with just the field name *}
    defn="{ldelim}label: {$field.label|@json_encode|escape}{rdelim}"
  ></af-field>
{/foreach}

{if $group.help_post}
  <div class="af-markup">{$group.help_post}</div>
{/if}
