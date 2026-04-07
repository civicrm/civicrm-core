<?php

/**
 * Controller for civicrm/logout route
 */
class CRM_Core_Page_Logout extends CRM_Core_Page {

  public function run() {
    $userSystem = \CRM_Core_Config::singleton()->userSystem;

    if (!$userSystem->isUserLoggedIn()) {
      throw new \CRM_Core_Exception(ts('You cannot log out because you are not logged in.'));
    }

    $userSystem->logout();

    $postLogoutUrl = $userSystem->postLogoutUrl();
    \CRM_Utils_System::redirect($postLogoutUrl);
  }

}
