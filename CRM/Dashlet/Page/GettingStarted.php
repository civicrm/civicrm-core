<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Main page for getting started dashlet
 */
class CRM_Dashlet_Page_GettingStarted extends CRM_Core_Page {

  const CHECK_TIMEOUT = 5;
  const CACHE_DAYS = 5;
  const GETTING_STARTED_URL = 'https://alert.civicrm.org/welcome?prot=1&ver={ver}&uf={uf}&sid={sid}&lang={lang}&co={co}';

  /**
   * Define tokens available for getting started
   * @var array
   */
  public static $_tokens = [
    'crmurl' => [
      'configbackend' => 'civicrm/admin/configtask',
      'systemstatus' => 'civicrm/a/#/status',
    ],
  ];

  /**
   * Get the final, usable URL string (after interpolating any variables)
   *
   * @return FALSE|string
   */
  public function gettingStartedUrl() {
    // Note: We use "*default*" as the default (rather than self::GETTING_STARTED_URL) so that future
    // developers can change GETTING_STARTED_URL without needing to update {civicrm_setting}.
    $url = Civi::settings()->get('gettingStartedUrl');
    if ($url === '*default*') {
      $url = self::GETTING_STARTED_URL;
    }
    return CRM_Utils_System::evalUrl($url);
  }

  /**
   * List gettingStarted page as dashlet.
   */
  public function run() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'dashlet');

    // Assign smarty variables.
    $this->assign('context', $context);
    $this->assign('gettingStarted', $this->_gettingStarted());

    // Use smarty to generate page.
    return parent::run();
  }

  /**
   * Load gettingStarted page from cache.
   * Refresh cache if expired
   *
   * @return array
   */
  private function _gettingStarted() {
    $tsLocale = CRM_Core_I18n::getLocale();
    $key = 'dashboard_gettingStarted_' . $tsLocale;
    $value = Civi::cache('community_messages')->get($key);

    if (!$value) {
      $value = $this->_getHtml($this->gettingStartedUrl());

      if ($value) {
        Civi::cache('community_messages')->set($key, $value, (60 * 60 * 24 * self::CACHE_DAYS));
      }
    }

    return $value;
  }

  /**
   * Get html.
   *
   * @param string $url
   *
   * @return array|NULL
   *   array of gettingStarted items; or NULL if not available
   */
  public function _getHtml($url) {
    $httpClient = new CRM_Utils_HttpClient(self::CHECK_TIMEOUT);
    list ($status, $html) = $httpClient->get($url);

    if ($status !== CRM_Utils_HttpClient::STATUS_OK) {
      return NULL;
    }

    $tokensList = CRM_Utils_Token::getTokens($html);
    $this->replaceLinkToken($tokensList, $html);
    return $html;
  }

  /**
   * @param array $tokensList
   * @param string $str
   *
   */
  public function replaceLinkToken($tokensList, &$str) {
    foreach ($tokensList as $categories => $tokens) {
      foreach ($tokens as $token) {
        $value = '';
        if (!empty(self::$_tokens[$categories][$token])) {
          $value = self::$_tokens[$categories][$token];
          if ($categories == 'crmurl') {
            $value = CRM_Utils_System::url($value, "reset=1");
          }
        }
        CRM_Utils_Token::token_replace($categories, $token, $value, $str);
      }
    }
  }

}
