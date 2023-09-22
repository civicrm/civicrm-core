<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
/*
 * Settings metadata file
 */

return [
  'default_renewal_contribution_page' => [
    'group_name' => 'Member Preferences',
    'group' => 'member',
    'name' => 'default_renewal_contribution_page',
    'type' => 'Integer',
    'html_type' => 'select',
    'default' => NULL,
    'pseudoconstant' => [
      // @todo - handle table style pseudoconstants for settings & avoid deprecated function.
      'callback' => 'CRM_Contribute_PseudoConstant::contributionPage',
    ],
    'add' => '4.1',
    'title' => ts('Default online membership renewal page'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('If you select a default online contribution page for self-service membership renewals, a "renew" link pointing to that page will be displayed on the Contact Dashboard for memberships which were entered offline. You will need to ensure that the membership block for the selected online contribution page includes any currently available memberships.'),
    'help_text' => NULL,
  ],
];
