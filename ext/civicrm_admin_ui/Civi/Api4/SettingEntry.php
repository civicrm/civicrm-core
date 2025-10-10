<?php

namespace Civi\Api4;

use CRM_CivicrmAdminUi_ExtensionUtil as E;

/**
 * SettingMeta entity.
 *
 * Abstract entity to allow inspection of available Settings
 *
 * @package Civi\Api4
 */
class SettingEntry extends Generic\AbstractEntity {

  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function ($getFieldsAction) {
      return [
        [
          'name' => 'title',
          'title' => E::ts('Setting'),
          'description' => E::ts('Public-facing name of the setting.'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'name',
          'title' => E::ts('Machine-name'),
          'description' => E::ts('Machine-name of the setting. Use for `\Civi::settings()->get([name]);`'),
        ],
        [
          'name' => 'group',
          'title' => E::ts('Group Key'),
          'description' => E::ts('Machine-name of the group this setting belongs to.'),
        ],
        [
          'name' => 'group_name',
          'title' => E::ts('Group'),
          'description' => E::ts('Public-facing name of the group this setting belongs to.'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'description',
          'title' => E::ts('Description'),
          'description' => E::ts('More details about this setting.'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'help_text',
          'title' => E::ts('Help text'),
          'description' => E::ts('Further help for setting this setting.'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'global_name',
          'title' => E::ts('Global Name'),
          'description' => E::ts('Used for PHP defines / environment variables.'),
          'input_type' => 'Text',
        ],
        [
          'name' => 'is_env_loadable',
          'title' => E::ts('Environment Variable?'),
          'description' => E::ts('Can this setting be set using an Environment Variable?'),
          'data_type' => 'Boolean',
          'input_type' => 'Radio',
        ],
        [
          'name' => 'is_constant',
          'title' => E::ts('PHP Constant?'),
          'description' => E::ts('Is there a php define corresponding to this setting'),
          'data_type' => 'Boolean',
          'input_type' => 'Radio',
        ],
        [
          'name' => 'is_domain',
          'title' => E::ts('Is domain-level?'),
          'description' => E::ts('Is this a contact or domain level setting?'),
          'data_type' => 'Boolean',
          'input_type' => 'Radio',
        ],
        [
          'name' => 'is_contact',
          'title' => E::ts('Is contact-level?'),
          'description' => E::ts('Is this a contact or domain level setting?'),
          'data_type' => 'Boolean',
          'input_type' => 'Radio',
        ],
        [
          'name' => 'current_value',
          'title' => E::ts('Current value'),
          'description' => E::ts('Current value of this setting'),
          'data_type' => 'String',
          'input_type' => 'Text',
        ],
        [
          'name' => 'current_layer',
          'title' => E::ts('Current layer'),
          'description' => E::ts('Where is the current value of this setting set? (e.g. environment variable, settings file, database)'),
          'data_type' => 'String',
          'input_type' => 'Select',
          'options' => [
            'environment' => 'Environment variable',
            'file' => 'PHP define or $civicrm_setting override (usually in civicrm.settings.php)',
            // we can't easily distinguish between these.. yet?
            //'define' => 'PHP Define (usually in civicrm.settings.php)',
            //'override' => '$civicrm_setting override (usually in civicrm.settings.php)',
            'database' => 'Database (civicrm_setting table)',
            'default' => 'Using the default value',
          ],
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

}
