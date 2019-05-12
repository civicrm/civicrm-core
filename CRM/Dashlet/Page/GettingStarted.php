<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
    $value = Civi::cache('community_messages')->get('dashboard_gettingStarted');

    if (!$value) {
      $value = $this->_getHtml($this->gettingStartedUrl());

      if ($value) {
        Civi::cache('community_messages')->set('dashboard_gettingStarted', $value, (60 * 60 * 24 * self::CACHE_DAYS));
      }
    }

    return $value;
  }

  /**
   * Get html.
   *
   * @param $url
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
