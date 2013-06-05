-- CRM-8356 added missing option values
SELECT @option_group_id_acConRef := max(id) from civicrm_option_group where name = 'contact_reference_options';

INSERT INTO civicrm_option_value
   (option_group_id, {localize field='label'}label{/localize}, value, name,  filter,  weight, is_active )
VALUES
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}Email Address{/ts}'{/localize}   , 2, 'email'         , 0, 2,  1 ),
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}Phone{/ts}'{/localize}           , 3, 'phone'         , 0, 3,  1 ),
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}Street Address{/ts}'{/localize}  , 4, 'street_address', 0, 4,  1 ),
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}City{/ts}'{/localize}            , 5, 'city'          , 0, 5,  1 ),
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}State/Province{/ts}'{/localize}  , 6, 'state_province', 0, 6,  1 ),
  (@option_group_id_acConRef, {localize}'{ts escape="sql"}Country{/ts}'{/localize}         , 7, 'country'       , 0, 7,  1 );
