<?php

/**
 * Tests for form buttons.
 * @group headless
 */
class CRM_Core_FormButtonsTest extends CiviUnitTestCase {

  /**
   * Test multiple submitOnce buttons.
   *
   * This test can be removed/changed if the mechanism for preventing
   * duplicate form submissions is improved in the future to work for
   * multiple submitOnce buttons.
   */
  public function testSubmitOnceTwice() {
    $form = new CRM_Core_Form();
    try {
      $form->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
          'submitOnce' => TRUE,
        ],
        [
          'type' => 'upload',
          'name' => ts('Save and New'),
          'subName' => 'new',
          'submitOnce' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    catch (Exception $e) {
      $this->assertEquals(ts('Multiple submitOnce buttons are not currently supported.'), $e->getMessage());
      return;
    }
    $this->fail('Exception should have been thrown for multiple submitOnce buttons.');
  }

}
