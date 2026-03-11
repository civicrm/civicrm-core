<?php

use CRM_Contribute_ExtensionUtil as E;

if (!\Civi::settings()->get('contribute_enable_afform_contributions')) {
  return [];
}

return [
  'type' => 'primary',
  'defaults' => "{
    data: {
      contact_id: 'user_contact_id',
      financial_type_id: 1
    },
    actions: {
      create: true,
      update: false,
    }
  }",
  'boilerplate' => [
    [
      '#tag' => 'af-field',
      'name' => 'default_contribution_amount.contribution_amount',
      'defn' => [
        'label' => E::ts('Contribution Amount'),
      ],
    ],
    [
      '#tag' => 'af-field',
      'name' => 'source',
    ],
  ],
];
