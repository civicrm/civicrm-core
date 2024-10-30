<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Contact_Form_Basic
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_BasicTest extends CiviUnitTestCase {

  /**
   * Test the BasicSearch form.
   *
   * @todo - this test passes but it tests a scenario we would
   * ideally deprecate - ie coming up with a search that won't work,
   * trying it, logging an error, and then trying something that does work.
   */
  public function testBasicSearch(): void {
    /* @var CRM_Contact_Form_Search_Basic $form */
    $form = $this->getFormObject('CRM_Contact_Form_Search_Basic');
    $form->setAction(CRM_Core_Action::BASIC);
    // Order by the 5th field (ie country) descending.
    $_GET['crmSID'] = '5_d';
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
  }

}
