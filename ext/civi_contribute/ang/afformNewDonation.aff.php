<?php
use CRM_Contribute_ExtensionUtil as E;

if (!\Civi::settings()->get('contribute_enable_afform_contributions')) {
  return [];
}

return [
  'type' => 'form',
  'title' => E::ts('New Donation'),
  'description' => E::ts('A basic donation form for your site. To use this form, change the Submission status to Open and pick a Checkout Option on the Contribution record.'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/donate',
  'is_public' => TRUE,
  'permission' => [
    'make online contributions',
  ],
  // disable submissions out of the box - the admin
  // should save an override of this form to enable
  'submit_enabled' => FALSE,
  'create_submission' => TRUE,
  'confirmation_type' => 'show_confirmation_message',
  'confirmation_message' => E::ts('Thank you for your support!'),
];
