<?php

class CRM_OAuth_Angular {

  public static function getSettings() {
    $s = [];

    $s['redirectUrl'] = \CRM_OAuth_BAO_OAuthClient::getRedirectUri();
    $s['providers'] = civicrm_api4('OAuthProvider', 'get', [])->indexBy('name');

    return $s;
  }

}
