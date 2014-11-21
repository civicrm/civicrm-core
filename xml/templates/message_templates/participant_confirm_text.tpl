{ts 1=$contact.display_name}Dear %1{/ts},
{if !$isAdditional and $participant.id}

===========================================================
{ts}Confirm Your Registration{/ts}

===========================================================
{capture assign=confirmUrl}{crmURL p='civicrm/event/confirm' q="reset=1&participantId=`$participant.id`&cs=`$checksumValue`" a=true h=0 fe=1}{/capture}
Click this link to go to a web page where you can confirm your registration online:
{$confirmUrl}
{/if}

===========================================================
{ts}Event Information and Location{/ts}

===========================================================
{$event.event_title}
{$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate}{/if}{/if}
{if $conference_sessions}


{ts}Your schedule:{/ts}
{assign var='group_by_day' value='NA'}
{foreach from=$conference_sessions item=session}
{if $session.start_date|date_format:"%Y/%m/%d" != $group_by_day|date_format:"%Y/%m/%d"}
{assign var='group_by_day' value=$session.start_date}

{$group_by_day|date_format:"%m/%d/%Y"}


{/if}
{$session.start_date|crmDate:0:1}{if $session.end_date}-{$session.end_date|crmDate:0:1}{/if} {$session.title}
{if $session.location}    {$session.location}{/if}
{/foreach}
{/if}


{ts}Participant Role{/ts}: {$participant.role}

{if $isShowLocation}
{if $event.location.address.1.name}

{$event.location.address.1.name}
{/if}
{if $event.location.address.1.street_address}{$event.location.address.1.street_address}
{/if}
{if $event.location.address.1.supplemental_address_1}{$event.location.address.1.supplemental_address_1}
{/if}
{if $event.location.address.1.supplemental_address_2}{$event.location.address.1.supplemental_address_2}
{/if}
{if $event.location.address.1.city}{$event.location.address.1.city} {$event.location.address.1.postal_code}{if $event.location.address.1.postal_code_suffix} - {$event.location.address.1.postal_code_suffix}{/if}
{/if}

{/if}{*End of isShowLocation condition*}

{if $event.location.phone.1.phone || $event.location.email.1.email}

{ts}Event Contacts:{/ts}
{foreach from=$event.location.phone item=phone}
{if $phone.phone}

{if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone}{/if}
{/foreach}
{foreach from=$event.location.email item=eventEmail}
{if $eventEmail.email}

{ts}Email{/ts}: {$eventEmail.email}{/if}{/foreach}
{/if}

{if $event.is_public}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" h=0 a=1 fe=1}{/capture}
{ts}Download iCalendar File:{/ts} {$icalFeed}
{/if}

{if $contact.email}

===========================================================
{ts}Registered Email{/ts}

===========================================================
{$contact.email}
{/if}

{if $register_date}
{ts}Registration Date{/ts}: {$participant.register_date|crmDate}
{/if}

{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions.{/ts}

