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
    return \Civi\Core\SettingsMetadata::getMetadata([], NULL, FALSE, $this->bootOnly);
  }

}
