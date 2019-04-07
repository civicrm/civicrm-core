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
class CRM_Batch_Form_Search extends CRM_Core_Form {

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    $status = CRM_Utils_Request::retrieve('status', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);

    $defaults['batch_status'] = $status;
    return $defaults;
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      CRM_Core_DAO::getAttribute('CRM_Batch_DAO_Batch', 'title')
    );

    $this->addButtons(
      [
        [
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ],
      ]
    );

    parent::buildQuickForm();
    $this->assign('suppressForm', TRUE);
  }

}
