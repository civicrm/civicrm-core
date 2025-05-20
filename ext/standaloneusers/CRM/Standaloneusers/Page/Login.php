<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_Page_Login extends CRM_Core_Page {

  public function run() {
    if (CRM_Core_Config::singleton()->userSystem->isUserLoggedIn()) {
      // Already logged in.
      CRM_Utils_System::redirect('/civicrm/home?reset=1');
    }
    if (isset($_GET['justLoggedOut'])) {
      // When the user has just logged out their session is destroyed
      // so we are unable to use setStatus in that request. Here we
      // add the session message and redirect so the user doesn't keep getting
      // the message when they press Back.
      CRM_Core_Session::setStatus(
        E::ts('You have been logged out.'),
        E::ts('Successfully signed out.'),
        'success');
      CRM_Utils_System::redirect('/civicrm/login');
    }

    CRM_Utils_System::setTitle(E::ts('Log In'));
    $this->assign('pageTitle', '');
    $this->assign('forgottenPasswordURL', CRM_Utils_System::url('civicrm/login/password'));
    // Remove breadcrumb for login page.
    $this->assign('breadcrumb', NULL);

    // Add the jQuery notify library because this library is only loaded whne the user is logged in. And we need this for CRM.alert
    CRM_Core_Resources::singleton()->addScriptFile('civicrm.packages', "jquery/plugins/jquery.notify.min.js", ['region' => 'html-header']);

    parent::run();
  }

}
