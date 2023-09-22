<?php

namespace Civi\Authx;

use CRM_Authx_ExtensionUtil as E;

class Meta {

  /**
   * @return array
   */
  public static function getCredentialTypes() {
    return [
      'jwt' => E::ts('JSON Web Token'),
      'api_key' => E::ts('API Key'),
      'pass' => E::ts('User Password'),
    ];
  }

  /**
   * @return array
   */
  public static function getUserModes() {
    return [
      'ignore' => E::ts('Ignore user accounts'),
      'optional' => E::ts('Optionally load user accounts'),
      'require' => E::ts('Require user accounts'),
    ];
  }

  /**
   * @return array
   */
  public static function getGuardTypes() {
    return [
      'perm' => E::ts('User Permission'),
      'site_key' => E::ts('Site Key'),
    ];
  }

  /**
   * @return array
   */
  public static function getFlowTypes() {
    return [
      'param' => E::ts('Ephemeral: Paramter'),
      'header' => E::ts('Ephemeral: Common Header'),
      'xheader' => E::ts('Ephemeral: X-Header'),
      'login' => E::ts('Persistent: Login session'),
      'auto' => E::ts('Persistent: Auto session'),
    ];
  }

}
