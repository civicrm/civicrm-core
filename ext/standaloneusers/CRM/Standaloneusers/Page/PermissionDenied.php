<?php
use CRM_Standaloneusers_ExtensionUtil as E;

/**
 * Display an "Access Denied" screen.
 *
 * This is only one of three ways in which "Access Denied" may be communicated.
 * This specific variant is used for authenticated, stateless requests. See
 * the main `permissionDenied()` controller for more complete explanation.
 *
 * @see CRM_Utils_System_Standalone::permissionDenied()
 */
class CRM_Standaloneusers_Page_PermissionDenied extends CRM_Core_Page {

  public function run() {
    http_response_code(403);
    CRM_Utils_System::setTitle(ts('Access denied'));
    parent::run();
  }

}
