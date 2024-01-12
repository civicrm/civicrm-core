<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_ChangePassword extends CRM_Core_Page {

  public function run() {
    $this->assign('hibp', CIVICRM_HIBP_URL);
    $this->assign('loggedInUserID', CRM_Utils_System::getLoggedInUfID());
    Civi::service('angularjs.loader')->addModules('crmChangePassword');
    // @todo specify the user in the URL somehow.
    parent::run();
  }

}
