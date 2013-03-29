<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Manage the download, validation, and rendering of community messages
 */
class CRM_Core_CommunityMessages {

  /**
   * Default time to wait before retrying
   */
  const DEFAULT_RETRY = 7200; // 2 hours

  /**
   * @var CRM_Utils_HttpClient
   */
  protected $client;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @param CRM_Utils_Cache_Interface $cache
   * @param CRM_Utils_HttpClient $client
   */
  public function __construct($cache, $client) {
    $this->cache = $cache;
    $this->client = $client;
  }

  /**
   * Get the messages document
   *
   * @return NULL|array
   */
  public function getDocument() {
    // FIXME register in settings
    $url = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'communityMessagesUrl', NULL, TRUE);
    if (empty($url)) {
      return NULL;
    }

    $isChanged = FALSE;
    $document = $this->cache->get('communityMessages');

    if (empty($document) || !is_array($document)) {
      $document = array(
        'messages' => array(),
        'expires' => 0, // ASAP
        'ttl' => self::DEFAULT_RETRY,
        'retry' => self::DEFAULT_RETRY,
      );
      $isChanged = TRUE;
    }

    if ($document['expires'] <= CRM_Utils_Time::getTimeRaw()) {
      $newDocument = $this->fetchDocument($url);
      if ($newDocument) {
        $document = $newDocument;
        $document['expires'] = CRM_Utils_Time::getTimeRaw() + $document['ttl'];
      } else {
        $document['expires'] = CRM_Utils_Time::getTimeRaw() + $document['retry'];
      }
      $isChanged = TRUE;
    }

    if ($isChanged) {
      $this->cache->set('communityMessages', $document);
    }

    return $document;
  }

  /**
   * Download document from URL and parse as JSON
   *
   * @param string $url
   * @return NULL|array parsed JSON
   */
  public function fetchDocument($url) {
    list($status, $json) = $this->client->get(self::evalUrl($url));
    if ($status != CRM_Utils_HttpClient::STATUS_OK || empty($json)) {
      return NULL;
    }
    $doc = json_decode($json, TRUE);
    if (empty($doc) || json_last_error() != JSON_ERROR_NONE) {
      return NULL;
    }
    return $doc;
  }

  /**
   * Pick one message
   *
   * @param callable $permChecker
   * @param array $components
   * @return NULL|array
   */
  public function pick($permChecker, $components) {
    throw new Exception('not implemented');
  }

  /**
   * @param string $markup
   * @return string
   */
  public static function evalMarkup($markup) {
    throw new Exception('not implemented');
  }

  /**
   * @param string $markup
   * @return string
   */
  public static function evalUrl($url) {
    return $url; // FIXME
  }
}
