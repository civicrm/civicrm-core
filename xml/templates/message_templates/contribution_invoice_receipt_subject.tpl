<<<<<<< HEAD
{if $title}{if $component}{if $component == 'event'} {ts}Event Registration Invoice:- {$title}{/ts}{else}{ts}Contribution Invoice :
 {$title}{/ts}{/if}{/if} {else} {ts}Invoice {/ts} {/if}
=======
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
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
