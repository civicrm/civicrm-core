contactID:::{contact.id}
eventID:::{event.id}
participantID:::{participant.id}
contact.id:::{contact.id}
event.id:::{event.id}
participant.id:::{participant.id}
event.title:::{event.title}
event.end_date|crmDate:"%Y%m%d"::{event.end_date|crmDate:"%Y%m%d"}
event.loc_block_id.phone_id.phone:::{event.loc_block_id.phone_id.phone}
event.loc_block_id.phone_id.phone_type_id:label:::{event.loc_block_id.phone_id.phone_type_id:label}
event.loc_block_id.phone_id.phone_ext:::{event.loc_block_id.phone_id.phone_ext}
event.loc_block_id.phone_2_id.phone:::{event.loc_block_id.phone_id.phone_2}
event.loc_block_id.phone_2_id.phone_type_id:label:::{event.loc_block_id.phone_id.phone_type_id_2:label}
event.loc_block_id.phone_2_id.phone_ext:::{event.loc_block_id.phone_id.phone_ext_2}
event.confirm_email_text::{event.confirm_email_text}
event.is_show_location|boolean::{event.is_show_location|boolean}
event.is_public|boolean::{event.is_public|boolean}
participant.participant_role_id:name::{participant.participant_role_id:name}
participant.status_id:name:::{participant.status_id:name}
email:::{contact.email_primary.email}
event.pay_later_receipt:::{event.pay_later_receipt}
contribution.total_amount:::{contribution.total_amount|crmMoney}
contribution.total_amount|raw:::{contribution.total_amount|raw}
contribution.balance_amount:::{contribution.balance_amount}
contribution.balance_amount|raw:::{contribution.balance_amount|raw}
contribution.paid_amount:::{contribution.paid_amount}
contribution.paid_amount|raw:::{contribution.paid_amount|raw}
contribution.balance_amount|raw is zero:::{if {contribution.balance_amount|raw} === 0.00}Yes{/if}
contribution.balance_amount|raw string is zero:::{if '{contribution.balance_amount|raw}' === '0.00'}Yes{/if}
contribution.balance_amount|boolean:::{if {contribution.balance_amount|boolean}}Yes{else}No{/if}
contribution.paid_amount|boolean:::{if {contribution.paid_amount|boolean}}Yes{/if}
