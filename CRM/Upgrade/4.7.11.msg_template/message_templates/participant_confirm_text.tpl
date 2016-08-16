{ts 1=$contact.display_name}Dear %1{/ts},
{if !$isAdditional and $participant.id}

===========================================================
{ts}Confirm Your Registration{/ts}

===========================================================
{capture assign=confirmUrl}{crmURL p='civicrm/event/confirm' q="reset=1&participantId=`$participant.id`&cs=`$checksumValue`" a=true h=0 fe=1}{/capture}
Click this link to go to a web page where you can confirm your registration online:
{$confirmUrl}
{/if}
{if $event.allow_selfcancelxfer }
{ts 1=$event.selfcancelxfer_time}You may transfer your registration to another participant or cancel your registration up to %1 hours before the event.{/ts} {if $totalAmount}{ts}Cancellations are not refundable.{/ts}{/if}
   {capture assign=selfService}{crmURL p='civicrm/event/selfsvcupdate' q="reset=1&pid=`$participant.id`&{contact.checksum}"  h=0 a=1 fe=1}{/capture}
{ts}Transfer or cancel your registration:{/ts} {$selfService}
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
{$event.location.address.1.display|strip_tags:false}
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

