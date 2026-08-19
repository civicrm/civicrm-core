{if empty($groups)}
  <p>{ts 1=$tagLabel}No records are currently tagged "%1".{/ts}</p>
{else}
  {foreach from=$groups item=group}
    <h3>{$group.title}</h3>
    <ul class="crm-tag-usage-list">
      {foreach from=$group.items item=item}
        <li>
          {if $item.url}
            <a href="{$item.url}">{$item.label}</a>
          {else}
            {$item.label}
          {/if}
        </li>
      {/foreach}
    </ul>
  {/foreach}
{/if}
