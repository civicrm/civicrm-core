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

namespace Civi\Api4\Action\Setting;

/**
 * Get information about CiviCRM settings.
 *
 * @method int getDomainId()
 * @method $this setDomainId(int $domainId)
 */
class GetFields extends \Civi\Api4\Generic\BasicGetFieldsAction {

  /**
   * Domain id of settings. Leave NULL for default domain.
   *
   * @var int
   */
  protected $domainId;

  protected function getRecords() {
    $names = $this->_itemsToGet('name');
    $filter = $names ? ['name' => $names] : [];
    $settings = \Civi\Core\SettingsMetadata::getMetadata($filter, $this->domainId, $this->loadOptions);
    $getReadonly = $this->_isFieldSelected('readonly');
    foreach ($settings as $index => $setting) {
      // Unserialize default value
      if (!empty($setting['serialize']) && !empty($setting['default']) && is_string($setting['default'])) {
        $setting['default'] = \CRM_Core_DAO::unSerializeField($setting['default'], $setting['serialize']);
      }
      if (!isset($setting['options'])) {
        $setting['options'] = !empty($setting['pseudoconstant']);
      }
      if ($getReadonly) {
        $setting['readonly'] = \Civi::settings()->getMandatory($setting['name']) !== NULL;
      }
      // Filter out deprecated properties
      $settings[$index] = array_intersect_key($setting, array_column($this->fields(), NULL, 'name'));
    }
    return $settings;
  }

  public function fields() {
    return [
      [
        'name' => 'name',
        'data_type' => 'String',
      ],
      [
        'name' => 'title',
        'data_type' => 'String',
      ],
      [
        'name' => 'description',
        'data_type' => 'String',
      ],
      [
        'name' => 'help_text',
        'data_type' => 'String',
      ],
      [
        'name' => 'default',
        'data_type' => 'String',
      ],
      [
        'name' => 'options',
        'data_type' => 'Array',
      ],
      [
        'name' => 'html_type',
        'data_type' => 'String',
      ],
      [
        'name' => 'add',
        'data_type' => 'String',
      ],
      [
        'name' => 'serialize',
        'data_type' => 'Integer',
      ],
      [
        'name' => 'data_type',
        'data_type' => 'Integer',
      ],
      [
        'name' => 'readonly',
        'data_type' => 'Boolean',
        'description' => 'True if value is set in code and cannot be overridden.',
      ],
    ];
  }

}
