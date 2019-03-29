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
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      Events::PREPARE => ['onApiPrepare', Events::W_MIDDLE],
    ];
  }

  /**
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \API_Exception
   */
  public function onApiPrepare(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    // support multi-lingual requests
    if ($language = \CRM_Utils_Array::value('option.language', $apiRequest['params'])) {
      $this->setLocale($language);
    }
  }

  /**
   * Sets the tsLocale and dbLocale for multi-lingual sites.
   * Some code duplication from CRM/Core/BAO/ConfigSetting.php retrieve()
   * to avoid regressions from refactoring.
   * @param $lcMessagesRequest
   * @throws \API_Exception
   */
  public function setLocale($lcMessagesRequest) {
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

    global $dbLocale;

    // set suffix for table names - use views if more than one language
    if ($lcMessages) {
      $dbLocale = $multiLang && $lcMessages ? "_{$lcMessages}" : '';

      // FIXME: an ugly hack to fix CRM-4041
      global $tsLocale;
      $tsLocale = $lcMessages;
    }
  }

}
