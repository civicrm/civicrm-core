<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
   */
  static $_tokens = array(
    'crmurl' => array(
      'configbackend' => 'civicrm/admin/configtask',
    ),
  );

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
    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'dashlet');

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
    // Fetch data from cache
    $cache = CRM_Core_DAO::executeQuery("SELECT data, created_date FROM civicrm_cache
      WHERE group_name = 'dashboard' AND path = 'gettingStarted'");
    if ($cache->fetch()) {
      $expire = time() - (60 * 60 * 24 * self::CACHE_DAYS);
      // Refresh data after CACHE_DAYS
      if (strtotime($cache->created_date) < $expire) {
        $new_data = $this->_getHtml($this->gettingStartedUrl());
        // If fetching the new html was successful, return it
        // Otherwise use the old cached data - it's better than nothing
        if ($new_data) {
          return $new_data;
        }
      }
      return unserialize($cache->data);
    }
    return $this->_getHtml($this->gettingStartedUrl());
  }

  /**
   * Get html and cache results.
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
    if ($html) {
      CRM_Core_BAO_Cache::setItem($html, 'dashboard', 'gettingStarted');
    }
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
            $value = CRM_Utils_System::url($value, "reset=1", FALSE, NULL, TRUE, TRUE);
          }
        }
        CRM_Utils_Token::token_replace($categories, $token, $value, $str);
      }
    }
  }

}
