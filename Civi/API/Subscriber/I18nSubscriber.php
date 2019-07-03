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
   * @param $lcMessagesRequest
   * @param int $requestId
   * @throws \API_Exception
   */
  public function setLocale($lcMessagesRequest, $requestId) {
    // We must validate whether the locale is valid, otherwise setting a bad
    // dbLocale could probably lead to sql-injection.
    $domain = new \CRM_Core_DAO_Domain();
    $domain->id = \CRM_Core_Config::domainID();
    $domain->find(TRUE);

    // are we in a multi-language setup?
    $multiLang = $domain->locales ? TRUE : FALSE;
    $lcMessages = NULL;

    // on multi-lang sites based on request and civicrm_uf_match
    if ($multiLang) {
      $config = \CRM_Core_Config::singleton();
      $languageLimit = [];
      if (isset($config->languageLimit) and $config->languageLimit) {
        $languageLimit = $config->languageLimit;
      }

      if (in_array($lcMessagesRequest, array_keys($languageLimit))) {
        $lcMessages = $lcMessagesRequest;
      }
      else {
        throw new \API_Exception(ts('Language not enabled: %1', [1 => $lcMessagesRequest]));
      }
    }

    if ($lcMessages) {
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
