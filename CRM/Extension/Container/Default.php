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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * The default container is just a basic container which can be configured via
 * the web UI.
 */
class CRM_Extension_Container_Default extends CRM_Extension_Container_Basic {

  /**
   * @inheritDoc
   *
   * @return array
   */
  public function checkRequirements() {
    $errors = [];

    // In current configuration, we don't construct the default container
    // unless baseDir is set, so this error condition is more theoretical.
    if (empty($this->baseDir) || !is_dir($this->baseDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination={$civicrmDestination}");
      $errors[] = [
        'title' => ts('Invalid Base Directory'),
        'message' => ts('The extensions directory is not properly set. Please go to the <a href="%1">path setting page</a> and correct it.<br/>',
          [
            1 => $url,
          ]
        ),
      ];
    }
    if (empty($this->baseUrl)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/url', "reset=1&civicrmDestination={$civicrmDestination}");
      $errors[] = [
        'title' => ts('Invalid Base URL'),
        'message' => ts('The extensions URL is not properly set. Please go to the <a href="%1">URL setting page</a> and correct it.<br/>',
          [
            1 => $url,
          ]
        ),
      ];
    }

    return $errors;
  }

}
