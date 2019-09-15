<?php
namespace Civi\Api4\Action\Setting;

use Civi\Api4\Generic\Result;

/**
 * Revert one or more CiviCRM settings to their default value.
 *
 * @method array getSelect
 * @method $this addSelect(string $name)
 * @method $this setSelect(array $select)
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

}
