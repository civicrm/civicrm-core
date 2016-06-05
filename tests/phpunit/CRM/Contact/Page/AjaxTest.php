<?php

/**
 * @group headless
 */
class CRM_Contact_Page_AjaxTest extends CiviUnitTestCase {


  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  /**
   * Minimal test on the testGetDupes function to make sure it completes without error.
   */
  public function testGetDedupes() {
    $_REQUEST['gid'] = 1;
    $_REQUEST['rgid'] = 1;
    $_REQUEST['columns'] = array(
      array(
        'search' => array(
          'value' => array(
            'src' => 'first_name',
          ),
        ),
        'data' => 'src',
      ),
    );
    $_REQUEST['is_unit_test'] = TRUE;
    $result = CRM_Contact_Page_AJAX::getDedupes();
    $this->assertEquals(array('data' => array(), 'recordsTotal' => 0, 'recordsFiltered' => 0), $result);
  }

}
