<?php

/**
 * This is a variant of CiviSeleniumSettings which autopopulates
 * using data from $_ENV.
 */
class CiviSeleniumSettings {
  public $publicSandbox = FALSE;
  public $browser = '*firefox';
  public $sandboxURL;
  public $sandboxPATH;
  public $username;
  public $password;
  public $adminUsername;
  public $adminPassword;
  public $adminApiKey;
  public $siteKey;
  public $UFemail = 'noreply@civicrm.org';
  public $cookies;

  public function __construct() {
    $required = array();
    foreach (array('CMS_URL', 'ADMIN_USER', 'ADMIN_PASS', 'DEMO_USER', 'DEMO_PASS') as $key) {
      if (empty($GLOBALS['_CV'][$key])) {
        $required[] = $key;
      }
    }
    if (!empty($required)) {
      throw new RuntimeException("CiviSeleniumSettings failed to find required values from cv: "
        . implode(' ', $required));
    }

    $path = parse_url($GLOBALS['_CV']['CMS_URL'], PHP_URL_PATH);
    $this->sandboxURL = substr($GLOBALS['_CV']['CMS_URL'], 0, strlen($GLOBALS['_CV']['CMS_URL']) - strlen($path));
    $this->sandboxPATH = $path;
    $this->fullSandboxPath = $GLOBALS['_CV']['CMS_URL'];
    $this->adminUsername = $GLOBALS['_CV']['ADMIN_USER'];
    $this->adminPassword = $GLOBALS['_CV']['ADMIN_PASS'];
    $this->username = $GLOBALS['_CV']['DEMO_USER'];
    $this->password = $GLOBALS['_CV']['DEMO_PASS'];
    $this->siteKey = CIVICRM_SITE_KEY;
    $this->adminApiKey = md5('apikeyadmin' . $GLOBALS['_CV']['CMS_DB_DSN'] . CIVICRM_SITE_KEY);
    $this->cookies = array();
  }

  //  /**
  //   * @return array
  //   */
  //  function createConstCookie() {
  //    global $civibuild;
  //    $now = time();
  //    $civiConsts = array(
  //      'CIVICRM_DSN' => CIVICRM_DSN,
  //      'CIVICRM_UF_DSN' => CIVICRM_UF_DSN,
  //      'ts' => $now,
  //      'sig' => md5(implode(';;', array(CIVICRM_DSN, CIVICRM_UF_DSN, $civibuild['SITE_TOKEN'], $now))),
  //    );
  //
  //    return array(
  //      'name' => 'civiConsts',
  //      'value' => urlencode(json_encode($civiConsts)),
  //    );
  //  }

}
