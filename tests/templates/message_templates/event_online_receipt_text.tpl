{assign var="greeting" value="{contact.email_greeting}"}{if $greeting}{$greeting},{/if}

event.event_title:::{event.title}
event.event_end_date:::{event.end_date|crmDate:"%Y%m%d"}
event.is_monetary:::{event.is_monetary|boolean}
event.fee_label:::{event.fee_label}
event.participant_role:::{event.participant_role_id:label}
location.phone.1.phone:::{event.loc_block_id.phone_id.phone}
location.phone.1.phone_type_display:::{event.loc_block_id.phone_id.phone_type_id:label}
location.phone.1.phone_ext:::{event.loc_block_id.phone_id.phone_ext}
location.email.1.email:::{event.loc_block_id.email_id.email}

