<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Core_Page_AJAX_LocationTest extends CiviUnitTestCase {
  /**
   * @expectedException CRM_Utils_System_UnitTests_StatusBounceException
   */
  function testUserIdIsVerified() {
    $_GET['cid'] = $_REQUEST['cid'] = $this->individualCreate();
    $_GET['ufId'] = $_REQUEST['ufId'] = 1;
    $_GET['uid'] = $_REQUEST['uid'] = 1;
    ob_start();
    CRM_Core_Page_AJAX_Location::getPermissionedLocation();
    ob_end_clean();
  }
}

