<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @file
 * File for the CRM_Contact_Import_Form_MapFieldTest class.
 */

/**
 *  Test contact import mapfield.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Import_Form_MapFieldTest extends CiviUnitTestCase {

  /**
   * Test the form loads without error / notice and mappings are assigned.
   *
   * (Added in conjunction with fixed noting on mapping assignment).
   */
  public function testSubmit() {
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField');
    $form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
    $form->set('fields', array());
    $form->_columnNames = array();
    $form->_importTableName = 'civicrm_cache';
    $form->submit(array(), array());
  }

}
