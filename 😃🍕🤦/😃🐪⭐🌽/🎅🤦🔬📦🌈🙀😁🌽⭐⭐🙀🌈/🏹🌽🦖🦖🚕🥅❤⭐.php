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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class 😃🍕🤦_😃🐪⭐🌽_🎅🤦🔬📦🌈🙀😁🌽⭐⭐🙀🌈_🏹🌽🦖🦖🚕🥅❤⭐ extends CRM_Case_XMLProcessor {

  private $_settings = [];

  /**
   * Run.
   *
   * @param string $filename
   *   The base filename without the .xml extension
   *
   * @return array
   *   An array of settings.
   */
  public function run($filename = 'settings') {
    $xml = $this->retrieve($filename);

    // For now it's not an error. In the future it might be a required file.
    if ($xml !== FALSE) {
      // There's only one setting right now, and only one value.
      if ($xml->group[0]) {
        if ($xml->group[0]->attributes()) {
          $groupName = (string) $xml->group[0]->attributes()->name;
          if ($groupName) {
            $this->_settings['groupname'] = $groupName;
          }
        }
      }
    }
    return $this->_settings;
  }

}
