{assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}{$greeting},{/if}

{ts}Your Event Registration has been cancelled.{/ts}


===========================================================
{ts}Event Information and Location{/ts}

===========================================================
{$event.event_title}
{$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|crmDate:"shortdate" == $event.event_start_date|crmDate:"shortdate"}{$event.event_end_date|crmDate:"Time"}{else}{$event.event_end_date|crmDate}{/if}{/if}

{ts}Participant Role{/ts}: {participant.role_id:label}

{if $isShowLocation}
{$event.location.address.1.display|strip_tags:false}
{/if}{*End of isShowLocation condition*}

{if !empty($event.location.phone.1.phone) || !empty($event.location.email.1.email)}

{ts}Event Contacts:{/ts}
{foreach from=$event.location.phone item=phone}
{if $phone.phone}

{if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone}{/if}
{/foreach}
{foreach from=$event.location.email item=eventEmail}
{if $eventEmail.email}

{ts}Email{/ts}: {$eventEmail.email}{/if}{/foreach}
{/if}

{if '{contact.email}'}

===========================================================
{ts}Registered Email{/ts}

===========================================================
{contact.email}
{/if}

{if !empty('{participant.register_date}')}
{ts}Registration Date{/ts}: {participant.register_date}
{/if}

{ts 1='{domain.phone}' 2='{domain.email}'}Please contact us at %1 or send email to %2 if you have questions.{/ts}

