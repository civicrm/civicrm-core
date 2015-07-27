-- CRM-6451
{if $multilingual}
  -- add a name column, populate it from the name_xx_YY chosen in
  ALTER TABLE civicrm_membership_status ADD name VARCHAR(128) COMMENT 'Name for Membership Status';
  UPDATE      civicrm_membership_status SET name = name_{$seedLocale};
  -- add label_xx_YY columns and populate them from name_xx_YY, dropping the latter
  {foreach from=$locales item=loc}
    ALTER TABLE civicrm_membership_status ADD label_{$loc} VARCHAR(128) COMMENT 'Label for Membership Status';
    UPDATE      civicrm_membership_status SET label_{$loc} = name_{$loc};
  {/foreach}
  -- drop the column separately.
  {foreach from=$locales item=loc}
    ALTER TABLE civicrm_membership_status DROP name_{$loc};
  {/foreach}
{else}
  -- add a label column and populate it from the name column
  ALTER TABLE civicrm_membership_status ADD label VARCHAR(128) COMMENT 'Label for Membership Status';
  UPDATE      civicrm_membership_status SET label = name;
{/if}


-- CRM-6004
{if $multilingual && !$hasLocalizedPreHelpCols}
  {foreach from=$locales item=loc}
   ALTER TABLE civicrm_uf_field ADD help_pre_{$loc} text COMMENT 'Description and/or help text to display before this field.';
   UPDATE civicrm_uf_field SET help_pre_{$loc} = help_pre;
  {/foreach}
  ALTER TABLE civicrm_uf_field DROP help_pre;
{/if}

-- CRM-6472
DELETE civicrm_uf_field FROM civicrm_uf_field
INNER JOIN civicrm_uf_group
WHERE civicrm_uf_group.id = civicrm_uf_field.uf_group_id
AND civicrm_uf_group.name = 'summary_overlay';

DELETE civicrm_uf_join FROM civicrm_uf_join
INNER JOIN civicrm_uf_group
WHERE civicrm_uf_group.id = civicrm_uf_join.uf_group_id
AND civicrm_uf_group.name = 'summary_overlay';

DELETE FROM civicrm_uf_group WHERE name = 'summary_overlay';

INSERT INTO civicrm_uf_group
    (name, group_type, {localize field='title'}title{/localize}, is_reserved ) VALUES
    ('summary_overlay', 'Contact',  {localize}'Summary Overlay'{/localize}, 1 );

SELECT @uf_group_id_summary   := max(id) FROM civicrm_uf_group WHERE name = 'summary_overlay';

INSERT INTO civicrm_uf_join
   (is_active,module,entity_table,entity_id,weight,uf_group_id) VALUES
   (1, 'Profile', NULL, NULL, 6, @uf_group_id_summary );

INSERT INTO civicrm_uf_field
   ( uf_group_id, field_name, is_required, is_reserved, weight, visibility, in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize}, field_type) VALUES
   ( @uf_group_id_summary,           'phone'          ,1,          0,       1,       'User and User Admin Only',   0,     0,        1,           {localize}'Home Phone'{/localize},           'Contact' ),
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