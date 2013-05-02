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
 * Page for displaying list of site configuration tasks with links to each setting form
 */
class CRM_Admin_Page_ConfigTaskList extends CRM_Core_Page {
  function run() {

    CRM_Utils_System::setTitle(ts("Configuration Checklist"));
    $this->assign('recentlyViewed', FALSE);

    $destination = CRM_Utils_System::url('civicrm/admin/configtask',
      'reset=1',
      FALSE, NULL, FALSE
    );

    $destination = urlencode($destination);
    $this->assign('destination', $destination);

    CRM_Core_OptionValue::getValues(array('name' => 'from_email_address'), $optionValue);
    if (!empty($optionValue)) {
      list($id) = array_keys($optionValue);
      $this->assign('fromEmailId', $id);
    }
    
    $payPalProId = CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_PaymentProcessorType',
      'PayPal', 'id', 'name'
    );
    if ($payPalProId) {
      $this->assign('payPalProId', $payPalProId);
    }
    return parent::run();
  }
}

