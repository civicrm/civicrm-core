{if $title}
  {if $component}
    {if $component == 'event'}
      {ts 1=$title}Event Registration Invoice: %1{/ts}
    {else}
      {ts 1=$title}Contribution Invoice: %1{/ts}
    {/if}
  {/if}
{else}
  {ts}Invoice{/ts}
{/if}
