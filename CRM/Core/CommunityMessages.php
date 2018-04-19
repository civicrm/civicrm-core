<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

  const DEFAULT_MESSAGES_URL = 'https://alert.civicrm.org/alert?prot=1&ver={ver}&uf={uf}&sid={sid}&lang={lang}&co={co}';
  const DEFAULT_PERMISSION = 'administer CiviCRM';

  /**
   * Default time to wait before retrying.
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
   * @var FALSE|string
   */
  protected $messagesUrl;

  /**
   * Create default instance.
   *
   * @return CRM_Core_CommunityMessages
   */
  public static function create() {
    return new CRM_Core_CommunityMessages(
      Civi::cache('community_messages'),
      CRM_Utils_HttpClient::singleton()
    );
  }

  /**
   * @param CRM_Utils_Cache_Interface $cache
   * @param CRM_Utils_HttpClient $client
   * @param null $messagesUrl
   */
  public function __construct($cache, $client, $messagesUrl = NULL) {
    $this->cache = $cache;
    $this->client = $client;
    if ($messagesUrl === NULL) {
      $this->messagesUrl = Civi::settings()->get('communityMessagesUrl');
    }
    else {
      $this->messagesUrl = $messagesUrl;
    }
    if ($this->messagesUrl === '*default*') {
      $this->messagesUrl = self::DEFAULT_MESSAGES_URL;
    }
  }

  /**
   * Get the messages document (either from the cache or by downloading)
   *
   * @return NULL|array
   */
  public function getDocument() {
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
      $newDocument = $this->fetchDocument();
      if ($newDocument && $this->validateDocument($newDocument)) {
        $document = $newDocument;
        $document['expires'] = CRM_Utils_Time::getTimeRaw() + $document['ttl'];
      }
      else {
        // keep the old messages for now, try again later
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
   * Download document from URL and parse as JSON.
   *
   * @return NULL|array
   *   parsed JSON
   */
  public function fetchDocument() {
    list($status, $json) = $this->client->get($this->getRenderedUrl());
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
   * Get the final, usable URL string (after interpolating any variables)
   *
   * @return FALSE|string
   */
  public function getRenderedUrl() {
    return CRM_Utils_System::evalUrl($this->messagesUrl);
  }

  /**
   * @return bool
   */
  public function isEnabled() {
    return $this->messagesUrl !== FALSE && $this->messagesUrl !== 'FALSE';
  }

  /**
   * Pick a message to display.
   *
   * @return NULL|array
   */
  public function pick() {
    $document = $this->getDocument();
    $messages = array();
    foreach ($document['messages'] as $message) {
      if (!isset($message['perms'])) {
        $message['perms'] = array(self::DEFAULT_PERMISSION);
      }
      if (!CRM_Core_Permission::checkAnyPerm($message['perms'])) {
        continue;
      }

      if (isset($message['components'])) {
        $enabled = array_keys(CRM_Core_Component::getEnabledComponents());
        if (count(array_intersect($enabled, $message['components'])) == 0) {
          continue;
        }
      }

      $messages[] = $message;
    }
    if (empty($messages)) {
      return NULL;
    }

    $idx = rand(0, count($messages) - 1);
    return $messages[$idx];
  }

  /**
   * @param string $markup
   * @return string
   */
  public static function evalMarkup($markup) {
    $config = CRM_Core_Config::singleton();
    $vals = array(
      'resourceUrl' => rtrim($config->resourceBase, '/'),
      'ver' => CRM_Utils_System::version(),
      'uf' => $config->userFramework,
      'php' => phpversion(),
      'sid' => CRM_Utils_System::getSiteID(),
      'baseUrl' => $config->userFrameworkBaseURL,
      'lang' => $config->lcMessages,
      'co' => $config->defaultContactCountry,
    );
    $vars = array();
    foreach ($vals as $k => $v) {
      $vars['%%' . $k . '%%'] = $v;
      $vars['{{' . $k . '}}'] = urlencode($v);
    }
    return strtr($markup, $vars);
  }

  /**
   * Ensure that a document is well-formed
   *
   * @param array $document
   * @return bool
   */
  public function validateDocument($document) {
    if (!isset($document['ttl']) || !is_int($document['ttl'])) {
      return FALSE;
    }
    if (!isset($document['retry']) || !is_int($document['retry'])) {
      return FALSE;
    }
    if (!isset($document['messages']) || !is_array($document['messages'])) {
      return FALSE;
    }
    foreach ($document['messages'] as $message) {
      // TODO validate $message['markup']
    }

    return TRUE;
  }

}
