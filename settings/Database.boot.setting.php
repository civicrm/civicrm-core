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

/**
 * Settings metadata file
 */
return [
  'civicrm_db_dsn' => [
    'name' => 'civicrm_db_dsn',
    'title' => ts('CiviCRM Database Connection String'),
    'description' => ts("The DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'String',
    'html_type' => 'text',
    //'default' => '',
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DSN',
  ],
  'civicrm_db_name' => [
    'name' => 'civicrm_db_name',
    'title' => ts('CiviCRM Database Name'),
    'description' => ts("The database name component of the DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'civicrm',
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DB_NAME',
  ],
  'civicrm_db_user' => [
    'name' => 'civicrm_db_user',
    'title' => ts('CiviCRM Database User'),
    'description' => ts("The database user component of the DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'civicrm',
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DB_USER',
  ],
  'civicrm_db_password' => [
    'name' => 'civicrm_db_password',
    'title' => ts('CiviCRM Database Password'),
    'description' => ts("The database password component of the DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'String',
    'html_type' => 'text',
    //'default' => '',
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DB_PASSWORD',
  ],
  'civicrm_db_host' => [
    'name' => 'civicrm_db_host',
    'title' => ts('CiviCRM Database Host'),
    'description' => ts("The database host component of the DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'String',
    'html_type' => 'text',
    'default' => 'civicrm',
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DB_HOST',
  ],
  'civicrm_db_port' => [
    'name' => 'civicrm_db_port',
    'title' => ts('CiviCRM Database Port'),
    'description' => ts("The database port component of the DSN for the CiviCRM Database."),
    'group' => 'database',
    'group_name' => 'CiviCRM Database',
    'type' => 'Integer',
    'html_type' => 'number',
    'default' => 3306,
    'add' => '5.76',
    'is_domain' => '1',
    'is_contact' => 0,
    'help_text' => NULL,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DB_PORT',
  ],
];
