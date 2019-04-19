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
 * Page for displaying list of site configuration tasks with links to each setting form.
 */
class CRM_Admin_Page_ConfigTaskList extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');

    CRM_Utils_System::setTitle(ts("Configuration Checklist"));
    $this->assign('recentlyViewed', FALSE);

    $destination = CRM_Utils_System::url('civicrm/admin/configtask',
      'reset=1',
      FALSE, NULL, FALSE
    );

    $destination = urlencode($destination);
    $this->assign('destination', $destination);

    $this->assign('registerSite', htmlspecialchars('https://civicrm.org/register-your-site?src=iam&sid=' . CRM_Utils_System::getSiteID()));

    return parent::run();
  }

}
