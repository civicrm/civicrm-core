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

use Civi\Api4\Generic\Result;

/**
 * Set the value of one or more CiviCRM settings.
 *
 * @method array getValues
 * @method $this setValues(array $value)
 */
class Set extends AbstractSettingAction {

  use \Civi\Api4\Generic\Traits\GetSetValueTrait;

  /**
   * Setting names/values to set.
   *
   * @var mixed
   * @required
   */
  protected $values = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @param \Civi\Core\SettingsBag $settingsBag
   * @param array $meta
   * @param int $domain
   * @throws \Exception
   */
  protected function processSettings(Result $result, $settingsBag, $meta, $domain) {
    foreach ($this->values as $name => $value) {
      if (isset($value) && !empty($meta[$name]['serialize'])) {
        $value = \CRM_Core_DAO::serializeField($value, $meta[$name]['serialize']);
      }
      $settingsBag->set($name, $value);
      $result[] = [
        'name' => $name,
        'value' => $this->values[$name],
        'domain_id' => $domain,
      ];
    }
  }

}
