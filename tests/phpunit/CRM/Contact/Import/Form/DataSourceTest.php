<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @file
 * File for the CRM_Contact_Import_Form_DataSourceTest class.
 */

/**
 *  Test contact import datasource.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Import_Form_DataSourceTest extends CiviUnitTestCase {

  /**
   * Test the form loads without error / notice and mappings are assigned.
   *
   * (Added in conjunction with fixed noting on mapping assignment).
   */
  public function testBuildForm() {
    $this->callAPISuccess('Mapping', 'create', ['name' => 'Well dressed ducks', 'mapping_type_id' => 'Import Contact']);
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource');
    $form->buildQuickForm();
    $this->assertEquals([1 => 'Well dressed ducks'], CRM_Core_Smarty::singleton()->get_template_vars('savedMapping'));
  }

}
