<?php

namespace Civi\Api4\Action\SettingEntry;

/**
 * Get defined SettingsMeta
 *
 * TODO: would like to be able to show set values + layer at which they are set
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * Only fetch settings available at boot
   * @var bool
   */
  protected $bootOnly = FALSE;

  /**
   * @return array
   */
  protected function getRecords() {
    $meta = \Civi\Core\SettingsMetadata::getMetadata([], NULL, FALSE, $this->bootOnly);

    if ($this->_isFieldSelected('current_value')) {
      $allValues = \Civi::settings()->all();

      foreach ($meta as $i => $record) {
        $name = $record['name'];

        $value = $allValues[$name] ?? NULL;

        if (is_array($value)) {
          $value = json_encode($value);
        }

        $meta[$i]['current_value'] = (string) $value;
      }
    }

    // TODO: SettingsManager doesn't expose the layer at which a setting
    // is set publicly, so this is a slightly rough implementation for now
    if ($this->_isFieldSelected('current_layer')) {
      foreach ($meta as $i => $record) {
        $meta[$i]['current_layer'] = $this->getLayer($record);
      }
    }

    return $meta;
  }

  protected function getLayer(array $record): string {
    $envVarName = $record['is_env_loadable'] ? $record['global_name'] : NULL;

    if ($envVarName && (getenv($envVarName) !== FALSE)) {
      return 'environment';
    }

    $settingName = $record['name'];

    if (!is_null(\Civi::settings()->getMandatory($settingName))) {
      return 'file';
    }

    if (\Civi::settings()->hasExplicit($settingName)) {
      return 'database';
    }

    return 'default';
  }

}
