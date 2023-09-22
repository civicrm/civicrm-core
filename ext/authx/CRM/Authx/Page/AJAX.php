<?php

use CRM_Authx_ExtensionUtil as E;

class CRM_Authx_Page_AJAX {

  /**
   * Identify the current user.
   *
   * GET /civicrm/authx/id
   */
  public static function getId() {
    $authxUf = _authx_uf();

    /** @var array $authx */
    $authx = CRM_Core_Session::singleton()->get('authx');
    $response = [
      'contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'user_id' => $authxUf->getCurrentUserId(),
      'flow' => $authx['flow'] ?? NULL,
      'cred' => $authx['credType'] ?? NULL,
    ];

    CRM_Utils_JSON::output($response);
  }

  /**
   * Present the outcome of an authx login.
   *
   * Note that the actual authentication is handled in the authentication layer.
   * This method just renders the response page after a successful login.
   */
  public static function login() {
    self::getId();
  }

  /**
   * Logout of Civi+CMS.
   *
   * GET /civicrm/authx/logout
   * POST /civicrm/authx/logout
   */
  public static function logout() {
    _authx_uf()->logoutSession();
    CRM_Utils_JSON::output([]);
  }

}
