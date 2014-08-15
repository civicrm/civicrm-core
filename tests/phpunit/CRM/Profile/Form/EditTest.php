<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Profile_Form_EditTest extends CiviUnitTestCase {
  function testDoesNotUseCancelURLFromPost() {
    $cancel_url = 'http://testdoesnotusecancel.local';
    $args = array('civicrm', 'profile', 'edit');
    $_GET['gid'] = '1';
    $_POST['_qf_Edit_cancel'] = 'Cancel';
    $_REQUEST['cancelURL'] = $_POST['cancelURL'] = $cancel_url;
    $exception_thrown = FALSE;
    try {
      CRM_Core_Invoke::profile($args);
    } catch (CRM_Utils_System_UnitTests_CiviExitException $e) {
      $exception_thrown = TRUE;
    }
    $config = CRM_Core_Config::singleton();
    $this->assertThat($cancel_url, $this->logicalNot($this->equalTo($config->userSystem->redirected_url)));
    $this->assertFalse($exception_thrown);
  }
}
