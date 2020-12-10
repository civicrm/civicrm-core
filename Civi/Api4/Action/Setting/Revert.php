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
 * Revert one or more CiviCRM settings to their default value.
 *
 * @method array getSelect
 * @method $this setSelect(array $settingNames) Set settings to be reverted
 */
class Revert extends AbstractSettingAction {

  /**
   * Names of settings to revert
   *
   * @var array
   * @required
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
    foreach ($this->select as $name) {
      $settingsBag->revert($name);
      $result[] = [
        'name' => $name,
        'value' => $settingsBag->get($name),
        'domain_id' => $domain,
      ];
    }
    foreach ($result as $name => &$setting) {
      if (isset($setting['value']) && !empty($meta[$name]['serialize'])) {
        $setting['value'] = \CRM_Core_DAO::unSerializeField($setting['value'], $meta[$name]['serialize']);
      }
    }
  }

  /**
   * Add one or more settings to be reverted
   * @param string ...$settingNames
   * @return $this
   */
  public function addSelect(string ...$settingNames) {
    $this->select = array_merge($this->select, $settingNames);
    return $this;
  }

}
