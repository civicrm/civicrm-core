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
 * Get the value of one or more CiviCRM settings.
 *
 * @method array getSelect
 * @method $this setSelect(array $settingNames)
 */
class Get extends AbstractSettingAction {

  /**
   * Names of settings to retrieve
   *
   * @var array
   */
  protected $select = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @param \Civi\Core\SettingsBag $settingsBag
   * @param array $meta
   * @param int $domain
   * @throws \Exception
   */
  protected function processSettings(Result $result, $settingsBag, $meta, $domain) {
    if ($this->select) {
      foreach ($this->select as $name) {
        [$name, $suffix] = array_pad(explode(':', $name), 2, NULL);
        $value = $settingsBag->get($name);
        if (isset($value) && !empty($meta[$name]['serialize'])) {
          $value = \CRM_Core_DAO::unSerializeField($value, $meta[$name]['serialize']);
        }
        if ($suffix) {
          $value = $this->matchPseudoconstant($name, $value, 'id', $suffix, $domain);
        }
        $result[] = [
          'name' => $suffix ? "$name:$suffix" : $name,
          'value' => $value,
          'domain_id' => $domain,
        ];
      }
    }
    else {
      foreach ($settingsBag->all() as $name => $value) {
        if (isset($value) && !empty($meta[$name]['serialize'])) {
          $value = \CRM_Core_DAO::unSerializeField($value, $meta[$name]['serialize']);
        }
        $result[] = [
          'name' => $name,
          'value' => $value,
          'domain_id' => $domain,
        ];
      }
    }
  }

  /**
   * Add one or more settings to be selected
   * @param string ...$settingNames
   * @return $this
   */
  public function addSelect(string ...$settingNames) {
    $this->select = array_merge($this->select, $settingNames);
    return $this;
  }

}
