{if $group.help_pre}
  <div class="af-markup">{$group.help_pre}</div>
{/if}

{foreach from=$group.fields item=field}
  <af-field
    name="{$field.key}"
    {if $field.defn}defn="{$field.defn|json|escape}"{/if}
  ></af-field>
{/foreach}

{if $group.help_post}
  <div class="af-markup">{$group.help_post}</div>
{/if}
