<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Page for displaying Parent Information Section tabs
 */
class CRM_Admin_Page_Persistent extends CRM_Core_Page {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  private static $_stringActionLinks;
  private static $_customizeActionLinks;

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */ function &stringActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_stringActionLinks)) {

      self::$_stringActionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/tplstrings/add',
          'qs' => 'reset=1&action=update&id=%%id%%',
          'title' => ts('Configure'),
        ),
      );
    }
    return self::$_stringActionLinks;
  }

  /**
   * @return array
   */
  function &customizeActionLinks() {
    // check if variable _actionsLinks is populated
    if (!isset(self::$_customizeActionLinks)) {

      self::$_customizeActionLinks = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/tplstrings/add',
          'qs' => 'reset=1&action=update&id=%%id%%&config=1',
          'title' => ts('Configure'),
        ),
      );
    }
    return self::$_customizeActionLinks;
  }

  /**
   * Run the basic page (run essentially starts execution for that page).
   *
   * @return void
   */
  function run() {
    CRM_Utils_System::setTitle(ts('DB Template Strings'));
    $this->browse();
    return parent::run();
  }

  /**
   * Browse all options
   *
   *
   * @return void
   * @access public
   * @static
   */
  function browse() {
    $permission = FALSE;
    $this->assign('editClass', FALSE);
    if (CRM_Core_Permission::check('access CiviCRM')) {
      $this->assign('editClass', TRUE);
      $permission = TRUE;
    }

    $daoResult = new CRM_Core_DAO_Persistent();
    $daoResult->find();
    $schoolValues = array();
    while ($daoResult->fetch()) {
      $values[$daoResult->id] = array();
      CRM_Core_DAO::storeValues($daoResult, $values[$daoResult->id]);
      if ($daoResult->is_config == 1) {
        $values[$daoResult->id]['action'] = CRM_Core_Action::formLink(self::customizeActionLinks(),
          NULL,
          array('id' => $daoResult->id),
          ts('more'),
          FALSE,
          'persistent.config.actions',
          'Persistent',
          $daoResult->id
        );
        $values[$daoResult->id]['data'] = implode(',', unserialize($daoResult->data));
        $configCustomization[$daoResult->id] = $values[$daoResult->id];
      }
      if ($daoResult->is_config == 0) {
        $values[$daoResult->id]['action'] = CRM_Core_Action::formLink(self::stringActionLinks(),
          NULL,
          array('id' => $daoResult->id),
          ts('more'),
          FALSE,
          'persistent.row.actions',
          'Persistent',
          $daoResult->id
        );
        $configStrings[$daoResult->id] = $values[$daoResult->id];
      }
    }
    $rows = array(
      'configTemplates' => $configStrings,
      'customizeTemplates' => $configCustomization,
    );
    $this->assign('rows', $rows);
  }
}

