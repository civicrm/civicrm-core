-- CRM-6400
INSERT INTO civicrm_uf_group
    (name, group_type, {localize field='title'}title{/localize}, is_reserved ) VALUES
    ('summary_overlay', 'Contact',  {localize}'Summary Overlay'{/localize}, 1 );

SELECT @uf_group_id_summary   := max(id) from civicrm_uf_group where name = 'summary_overlay';

INSERT INTO civicrm_uf_join
   (is_active,module,entity_table,entity_id,weight,uf_group_id) VALUES
   (1, 'Profile', NULL, NULL, 6, @uf_group_id_summary );

INSERT INTO civicrm_uf_field
   ( uf_group_id, field_name, is_required, is_reserved, weight, visibility, in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize},field_type ) VALUES
   ( @uf_group_id_summary,           'phone'          ,1,          0,       1,       'User and User Admin Only',   0,     0,        1,           {localize}'Home Phone'{/localize},           'Contact'),
   ( @uf_group_id_summary,           'phone'          ,1,        0,       2,       'User and User Admin Only',   0,      0,        2,           {localize}'Home Mobile'{/localize},         'Contact' ),
   ( @uf_group_id_summary,        'street_address',       1,        0,       3,       'User and User Admin Only',   0,     0,        NULL,          {localize}'Primary Address'{/localize},            'Contact' ),
   ( @uf_group_id_summary,        'city',          1,        0,       4,       'User and User Admin Only',   0,     0,        NULL,          {localize}'City'{/localize},              'Contact' ),
   ( @uf_group_id_summary,        'state_province',       1,        0,       5,       'User and User Admin Only',   0,     0,        NULL,          {localize}'State'{/localize},              'Contact' ),
   ( @uf_group_id_summary,        'postal_code',       1,        0,       6,       'User and User Admin Only',   0,     0,        NULL,          {localize}'Postal Code'{/localize},          'Contact' ),
   ( @uf_group_id_summary,        'email',           1,        0,       7,       'User and User Admin Only',   0,     0,        NULL,          {localize}'Primary Email'{/localize},          'Contact' ),
   ( @uf_group_id_summary,        'group',           1,        0,       8,       'User and User Admin Only',   0,     0,        NULL,          {localize}'Groups'{/localize},            'Contact' ),
   ( @uf_group_id_summary,        'tag',           1,        0,       9,       'User and User Admin Only',   0,     0,          NULL,          {localize}'Tags'{/localize},             'Contact' ),
   ( @uf_group_id_summary,           'gender',          1,           0,        10,    'User and User Admin Only',   0,      0,         NULL,         {localize}'Gender'{/localize},            'Individual' ),
   ( @uf_group_id_summary,          'birth_date',         1,           0,       11,   'User and User Admin Only',   0,     0,        NULL,         {localize}'Date of Birth'{/localize},           'Individual' );

-- CRM-6416
SELECT @option_group_id_ceOpt := max(id) from civicrm_option_group where name = 'contact_edit_options';
SELECT @option_value_ceOpt    := max(round(value)) from civicrm_option_value where option_group_id = @option_group_id_ceOpt;
SELECT @option_weight_ceOpt   := max(round(weight)) from civicrm_option_value where option_group_id = @option_group_id_ceOpt;

INSERT INTO
       civicrm_option_value (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`)
       VALUES( @option_group_id_ceOpt, {localize}'Website'{/localize}, @option_value_ceOpt+1, 'Website', NULL, 1, NULL, @option_weight_ceOpt+1, 0, 0, 1, NULL, NULL, NULL );

UPDATE civicrm_preferences SET contact_edit_options  = CONCAT(contact_edit_options, @option_value_ceOpt+1, '');

-- CRM-6410
SELECT @bounceTypeID := MAX(id) FROM civicrm_mailing_bounce_type WHERE name = 'Invalid';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern) VALUES (@bounceTypeID, '^Validation failed for:');
