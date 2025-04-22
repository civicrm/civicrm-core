{if $group.help_pre}
  <div class="af-markup">{$group.help_pre}</div>
{/if}

{foreach from=$group.field_names item=field_name}
  {* for multiple record fields there is no need to prepend
  the group name because it  will be the form entity itself *}
  <af-field name="{if !$group.is_multiple}{$group.name}.{/if}{$field_name}" />
{/foreach}

{if $group.help_post}
  <div class="af-markup">{$group.help_post}</div>
{/if}
