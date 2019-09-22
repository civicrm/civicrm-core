<?php
namespace Civi\Api4\Action\Setting;

use Civi\Api4\Generic\Result;

/**
 * Get the value of one or more CiviCRM settings.
 *
 * @method array getSelect
 * @method $this addSelect(string $name)
 * @method $this setSelect(array $select)
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
        $result[] = [
          'name' => $name,
          'value' => $settingsBag->get($name),
          'domain_id' => $domain,
        ];
      }
    }
    else {
      foreach ($settingsBag->all() as $name => $value) {
        $result[] = [
          'name' => $name,
          'value' => $value,
          'domain_id' => $domain,
        ];
      }
    }
    foreach ($result as $name => &$setting) {
      if (isset($setting['value']) && !empty($meta[$name]['serialize'])) {
        $setting['value'] = \CRM_Core_DAO::unSerializeField($setting['value'], $meta[$name]['serialize']);
      }
    }
  }

}
