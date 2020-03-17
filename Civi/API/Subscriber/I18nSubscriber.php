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

namespace Civi\API\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class I18nSubscriber
 * @package Civi\API\Subscriber
 */
class I18nSubscriber implements EventSubscriberInterface {

  /**
   * Used for rolling back language to its original setting after the api call.
   *
   * @var array
   */
  public $originalLang = [];

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::PREPARE => ['onApiPrepare', Events::W_MIDDLE],
      Events::RESPOND => ['onApiRespond', Events::W_LATE],
    ];
  }

  /**
   * Support multi-lingual requests
   *
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \API_Exception
   */
  public function onApiPrepare(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    $params = $apiRequest['params'];
    if ($apiRequest['version'] < 4) {
      $language = !empty($params['options']['language']) ? $params['options']['language'] : \CRM_Utils_Array::value('option.language', $params);
    }
    else {
      $language = \CRM_Utils_Array::value('language', $params);
    }
    if ($language) {
      $this->setLocale($language, $apiRequest['id']);
    }
  }

  /**
   * Reset language to the default.
   *
   * @param \Civi\API\Event\Event $event
   *
   * @throws \API_Exception
   */
  public function onApiRespond(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    if (!empty($this->originalLang[$apiRequest['id']])) {
      global $tsLocale;
      global $dbLocale;
      $tsLocale = $this->originalLang[$apiRequest['id']]['tsLocale'];
      $dbLocale = $this->originalLang[$apiRequest['id']]['dbLocale'];
    }
  }

  /**
   * Sets the tsLocale and dbLocale for multi-lingual sites.
   * Some code duplication from CRM/Core/BAO/ConfigSetting.php retrieve()
   * to avoid regressions from refactoring.
   * @param string $lcMessages
   * @param int $requestId
   * @throws \API_Exception
   */
  public function setLocale($lcMessages, $requestId) {
    $domain = new \CRM_Core_DAO_Domain();
    $domain->id = \CRM_Core_Config::domainID();
    $domain->find(TRUE);

    // Check if the site is multi-lingual
    if ($domain->locales && $lcMessages) {
      // Validate language, otherwise a bad dbLocale could probably lead to sql-injection.
      if (!array_key_exists($lcMessages, \Civi::settings()->get('languageLimit'))) {
        throw new \API_Exception(ts('Language not enabled: %1', [1 => $lcMessages]));
      }

      global $dbLocale;
      global $tsLocale;

      // Store original value to be restored in $this->onApiRespond
      $this->originalLang[$requestId] = [
        'tsLocale' => $tsLocale,
        'dbLocale' => $dbLocale,
      ];

      // Set suffix for table names - use views if more than one language
      $dbLocale = "_{$lcMessages}";

      // Also set tsLocale - CRM-4041
      $tsLocale = $lcMessages;
    }
  }

}
