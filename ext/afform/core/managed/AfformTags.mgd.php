<?php

use CRM_Afform_ExtensionUtil as E;

// This file:
// - adds option value to `tag_used_for`, allowing Afforms to be tagged
//   NOTE: this is unusual in that afform is not a db entity
//   (normally the value normally corresponds to a physical table name in the
//   database - here it is just the entity name)
return [
  [
    'name' => 'AfformTags:tag_used_for',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'tag_used_for',
        'value' => 'Afform',
        'name' => 'Afform',
        'label' => E::ts('Afforms'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        // this excludes it from being selected in EntityTag entity_table
        'filter' => 1,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
