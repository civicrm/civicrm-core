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
function cors_settings() {
  $weight = 10;
  $base = [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'cors',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '5.56',
  ];

  $s = [];

  $s["cors_rules"] = $base + [
    'name' => 'cors_rules',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'textarea',
    'html_attributes' => [
      'rows' => '10',
      'cols' => '50',
    ],
    'default' => json_encode([]),
    'title' => ts('CORS rules'),
    'description' => ts('A JSON list of CORS rules. Each rule has the following keys:<br />
      - "pattern" - a civicrm URL to match against, e.g. "civicrm/ajax/api4/*"<br />
      - "origins" - a comma separated list of origins or "*", e.g. "https://app.example.org, https://dash.example.org"<br />
      - "headers" (optional) - a comma separated list of headers or "*", e.g. "X-Civi-Auth"<br />
      - "methods" (optional) - a comma separated list of methods or "*", e.g. "GET, POST"<br />
      See the <a href="https://docs.civicrm.org/dev/en/latest/framework/cors/">CORS documentation</a> for more details.'),
    'settings_pages' => ['cors' => ['weight' => $weight]],
    'validate_callback'    => '\Civi\Cors\Cors::validateRules',
  ];
  $s["cors_max_age"] = $base + [
    'name' => 'cors_max_age',
    'type' => 'Number',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => '',
    'title' => ts('CORS max age'),
    'description' => ts('Value for the \'Access-Control-Max-Age\' header.'),
    'settings_pages' => ['cors' => ['weight' => $weight]],
    'validate_callback'    => '\Civi\Cors\Cors::validateMaxAge',

  ];

  return $s;
};

/**
 * Settings metadata file
 */
return cors_settings();
