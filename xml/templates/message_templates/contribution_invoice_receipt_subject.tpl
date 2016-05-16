{if $title}{if $component}{if $component == 'event'} {ts}Event Registration Invoice:- {$title}{/ts}{else}{ts}Contribution Invoice :
 {$title}{/ts}{/if}{/if} {else} {ts}Invoice {/ts} {/if}
