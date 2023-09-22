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
 * Upgrade logic for FiveNine
 */
class CRM_Upgrade_Incremental_php_FiveNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '5.9.0') {
      $args = [
        1 => ts('Enable multiple bulk email address for a contact'),
        2 => ts('Email on Hold'),
      ];
      $postUpgradeMessage .= '<p>' . ts('If the setting "%1" is enabled, you should update any smart groups based on the "%2" field.', $args) . '</p>' .
        '<p>' . ts('If you were previously on version 5.8 and altered the WYSIWYG editor setting, you should visit the <a %1>Display Preferences</a> page and re-save the WYSIWYG editor setting.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/setting/preferences/display', 'reset=1') . '"']) . '</p>' .
        '<p>' . ts('CiviCRM v5.9+ adds a new search preference for certain custom-fields ("Money", "Integer", or "Float" data displayed as "Select" or "Radio" fields). For continuity, any old fields have been set to continue the old user-experience. However, you may want to review these settings. (You should especially review if this site used v5.9-alpha or v5.9-beta.)') . '</p>';
    }
  }

}
