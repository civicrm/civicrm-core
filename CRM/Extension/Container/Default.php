<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
    $errors = array();

    // In current configuration, we don't construct the default container
    // unless baseDir is set, so this error condition is more theoretical.
    if (empty($this->baseDir) || !is_dir($this->baseDir)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Invalid Base Directory'),
        'message' => ts('The extensions directory is not properly set. Please go to the <a href="%1">path setting page</a> and correct it.<br/>',
          array(
            1 => $url,
          )
        ),
      );
    }
    if (empty($this->baseUrl)) {
      $civicrmDestination = urlencode(CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1'));
      $url = CRM_Utils_System::url('civicrm/admin/setting/url', "reset=1&civicrmDestination=${civicrmDestination}");
      $errors[] = array(
        'title' => ts('Invalid Base URL'),
        'message' => ts('The extensions URL is not properly set. Please go to the <a href="%1">URL setting page</a> and correct it.<br/>',
          array(
            1 => $url,
          )
        ),
      );
    }

    return $errors;
  }

}
