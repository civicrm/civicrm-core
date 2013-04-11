<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
 * This class generates form components
 * for previewing Civicrm Profile Group
 *
 */
class CRM_UF_Form_Inline_PreviewById extends CRM_UF_Form_AbstractPreview {

  /**
   * pre processing work done here.
   *
   * gets session variables for group or field id
   *
   * @param
   *
   * @return void
   *
   * @access public
   *
   */
  function preProcess() {
    // Inline forms don't get menu-level permission checks
    if (!CRM_Core_Permission::check('access CiviCRM')) {
      CRM_Core_Error::fatal(ts('Permission denied'));
    }
    $gid = CRM_Utils_Request::retrieve('id', 'Positive');
    $fields = CRM_Core_BAO_UFGroup::getFields($gid);
    $this->setProfile($fields);
  }

}

