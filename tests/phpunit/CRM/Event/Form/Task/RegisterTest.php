<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Task_RegisterTest extends CiviUnitTestCase {

  /**
   * Initial test of form class.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGet() {
    /* @var CRM_Event_Form_Task_Register $form */
    $form = $this->getFormObject('CRM_Event_Form_Task_Register');
    $this->assertEquals(FALSE, $form->_single);
  }

}
