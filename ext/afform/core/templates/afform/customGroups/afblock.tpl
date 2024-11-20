{if $custom.help_pre}
  <div class="af-markup">{$custom.help_pre}</div>
{/if}

{foreach from=$custom.fields item=field}
  {* for multiple record fields there is no need to prepend
  the group name because it is provided as the join_entity above *}
  <af-field name="{if !$custom.is_multiple}{$custom.name}.{/if}{$field.name}" />
{/foreach}

{if $custom.help_post}
  <div class="af-markup">{$custom.help_post}</div>
{/if}

