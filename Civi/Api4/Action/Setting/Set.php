<?php
namespace Civi\Api4\Action\Setting;

use Civi\Api4\Generic\Result;

/**
 * Set the value of one or more CiviCRM settings.
 *
 * @method array getValues
 * @method $this setValues(array $value)
 * @method $this addValue(string $name, mixed $value)
 */
class Set extends AbstractSettingAction {

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
