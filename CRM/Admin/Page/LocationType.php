<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of location types
 */
class CRM_Admin_Page_LocationType extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Core_BAO_LocationType';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/locationType',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Location Type'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_LocationType' . '\',\'' . 'enable-disable' . '\' );"',
          'ref' => 'disable-action',
          'title' => ts('Disable Location Type'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'extra' => 'onclick = "enableDisable( %%id%%,\'' . 'CRM_Core_BAO_LocationType' . '\',\'' . 'disable-enable' . '\' );"',
          'ref' => 'enable-action',
          'title' => ts('Enable Location Type'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/locationType',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Location Type'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_Admin_Form_LocationType';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Location Types';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/locationType';
  }
}

