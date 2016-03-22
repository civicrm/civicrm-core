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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class summarizes the import results.
 *
 * TODO: CRM-11254 - if preProcess and postProcess functions can be reconciled between the 5 child classes,
 * those classes can be removed entirely and this class will not need to be abstract
 */
abstract class CRM_Import_Form_Summary extends CRM_Core_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Summary');
  }

}
