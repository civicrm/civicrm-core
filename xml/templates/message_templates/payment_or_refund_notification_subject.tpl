{if $isRefund}{ts}Refund Notification{/ts}{else}{ts}Payment Receipt{/ts}{/if}{if $component eq 'event'} - {$event.title}{/if} - {contact.display_name}
