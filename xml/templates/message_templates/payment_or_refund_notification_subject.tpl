{if {financial_trxn.total_amount|raw} < 0}{ts}Refund Notification{/ts}{else}{ts}Payment Receipt{/ts}{/if}{if {event.title|boolean}} - {event.title}{/if} - {contact.display_name}
