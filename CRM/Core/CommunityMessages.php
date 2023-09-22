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
 * Manage the download, validation, and rendering of community messages
 */
class CRM_Core_CommunityMessages {

  const DEFAULT_MESSAGES_URL = 'https://alert.civicrm.org/alert?prot=1&ver={ver}&uf={uf}&sid={sid}&lang={lang}&co={co}';
  const DEFAULT_PERMISSION = 'administer CiviCRM';

  /**
   * Default time to wait before retrying.
   */
  // 2 hours
  const DEFAULT_RETRY = 7200;

  /**
   * @var CRM_Utils_HttpClient
   */
  protected $client;

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * Url to retrieve community messages from.
   *
   * False means a retrieval will not be attempted.
   *
   * @var false|string
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
   * Class constructor.
   *
   * @param CRM_Utils_Cache_Interface $cache
   * @param CRM_Utils_HttpClient $client
   * @param string|false $messagesUrl
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
   * Get the messages document (either from the cache or by downloading).
   *
   * @return NULL|array
   */
  public function getDocument() {
    $isChanged = FALSE;
    $document = $this->cache->get('communityMessages');

    if (empty($document) || !is_array($document)) {
      $document = [
        'messages' => [],
        // ASAP
        'expires' => 0,
        'ttl' => self::DEFAULT_RETRY,
        'retry' => self::DEFAULT_RETRY,
      ];
      $isChanged = TRUE;
    }

    $refTime = CRM_Utils_Time::getTimeRaw();
    if ($document['expires'] <= $refTime) {
      $newDocument = $this->fetchDocument();
      if ($newDocument && $this->validateDocument($newDocument)) {
        $document = $newDocument;
        $document['expires'] = $refTime + $document['ttl'];
      }
      else {
        // keep the old messages for now, try again later
        $document['expires'] = $refTime + $document['retry'];
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
    [$status, $json] = $this->client->get($this->getRenderedUrl());
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
    $messages = [];
    foreach ($document['messages'] as $message) {
      if (!isset($message['perms'])) {
        $message['perms'] = [self::DEFAULT_PERMISSION];
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
    $vals = [
      'resourceUrl' => rtrim($config->resourceBase, '/'),
      'ver' => CRM_Utils_System::version(),
      'uf' => $config->userFramework,
      'php' => phpversion(),
      'sid' => CRM_Utils_System::getSiteID(),
      'baseUrl' => $config->userFrameworkBaseURL,
      'lang' => $config->lcMessages,
      'co' => $config->defaultContactCountry ?? '',
    ];
    $vars = [];
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
