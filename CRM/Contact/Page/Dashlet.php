<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * CiviCRM Dashlet.
 */
class CRM_Contact_Page_Dashlet extends CRM_Core_Page {

  /**
   * Run dashboard.
   */
  public function run() {
    CRM_Utils_System::setTitle(ts('Dashlets'));

    $this->assign('admin', CRM_Core_Permission::check('administer CiviCRM'));

    // get all dashlets
    $allDashlets = CRM_Core_BAO_Dashboard::getDashlets(FALSE);

    // get dashlets for logged in contact
    $currentDashlets = CRM_Core_BAO_Dashboard::getContactDashlets();
    $contactDashlets = $availableDashlets = array();

    foreach ($currentDashlets as $item) {
      $key = "{$item['dashboard_id']}-0";
      $contactDashlets[$item['column_no']][$key] = array(
        'label' => $item['label'],
        'is_reserved' => $allDashlets[$item['dashboard_id']]['is_reserved'],
      );
      unset($allDashlets[$item['dashboard_id']]);
    }

    foreach ($allDashlets as $dashletID => $values) {
      $key = "{$dashletID}-0";
      $availableDashlets[$key] = array(
        'label' => $values['label'],
        'is_reserved' => $values['is_reserved'],
      );
    }

    $this->assign('contactDashlets', $contactDashlets);
    $this->assign('availableDashlets', $availableDashlets);

    return parent::run();
  }

}
