<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for 4.7
 */
class CRM_Upgrade_Incremental_php_FourSeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   * @return void
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.7.alpha1') {
      $config = CRM_Core_Config::singleton();
      // FIXME: Performing an upgrade step during postUpgrade message phase is probably bad
      $editor_id = self::updateWysiwyg();
      $msg = NULL;
      $ext_href = 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1') . '"';
      $dsp_href = 'href="' . CRM_Utils_System::url('civicrm/admin/setting/preferences/display', 'reset=1') . '"';
      $blog_href = 'href="https://civicrm.org/blogs/colemanw/big-changes-wysiwyg-editing-47"';
      switch ($editor_id) {
        // TinyMCE
        case 1:
          $msg = ts('Your configured editor "TinyMCE" is no longer part of the main CiviCRM download. To continue using it, visit the <a %1>Manage Extensions</a> page to download and install the TinyMCE extension.', array(1 => $ext_href));
          break;

        // Drupal/Joomla editor
        case 3:
        case 4:
          $msg = ts('CiviCRM no longer integrates with the "%1 Default Editor." Your wysiwyg setting has been reset to the built-in CKEditor. <a %2>Learn more...</a>', array(1 => $config->userFramework, 2 => $blog_href));
          break;
      }
      if ($msg) {
        $postUpgradeMessage .= '<p>' . $msg . '</p>';
      }
      $postUpgradeMessage .= '<p>' . ts('CiviCRM now includes the easy-to-use CKEditor Configurator. To customize the features and display of your wysiwyg editor, visit the <a %1>Display Preferences</a> page. <a %2>Learn more...</a>', array(1 => $dsp_href, 2 => $blog_href)) . '</p>';

      $postUpgradeMessage .= '<br /><br />' . ts('Default version of the following System Workflow Message Templates have been modified: <ul><li>Personal Campaign Pages - Owner Notification</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_7_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * CRM-16354
   *
   * @return int
   */
  public static function updateWysiwyg() {
    $editorID = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'editor_id');
    // Previously a numeric value indicated one of 4 wysiwyg editors shipped in core, and no value indicated 'Textarea'
    // Now the options are "Textarea", "CKEditor", and the rest have been dropped from core.
    $newEditor = $editorID ? "CKEditor" : "Textarea";
    CRM_Core_BAO_Setting::setItem($newEditor, CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'editor_id');

    return $editorID;
  }

}
