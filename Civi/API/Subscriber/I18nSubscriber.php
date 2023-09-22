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
use Civi\Core\Locale;
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
   *   Array(string $requestId => \Civi\Core\Locale $locale).
   */
  protected $originalLocale = [];

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', Events::W_MIDDLE],
      'civi.api.respond' => ['onApiRespond', Events::W_LATE],
    ];
  }

  /**
   * Support multi-lingual requests
   *
   * @param \Civi\API\Event\Event $event
   *   API preparation event.
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiPrepare(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    $params = $apiRequest['params'];
    if ($apiRequest['version'] < 4) {
      $language = $params['options']['language'] ?? $params['option.language'] ?? NULL;
    }
    else {
      $language = $params['language'] ?? NULL;
    }
    if ($language) {
      $newLocale = Locale::negotiate($language);
      if ($newLocale) {
        $this->originalLocale[$apiRequest['id']] = Locale::detect();
        $newLocale->apply();
      }
    }
  }

  /**
   * Reset language to the default.
   *
   * @param \Civi\API\Event\Event $event
   *
   * @throws \CRM_Core_Exception
   */
  public function onApiRespond(\Civi\API\Event\Event $event) {
    $apiRequest = $event->getApiRequest();

    if (!empty($this->originalLocale[$apiRequest['id']])) {
      $this->originalLocale[$apiRequest['id']]->apply();
      unset($this->originalLocale[$apiRequest['id']]);
    }
  }

}
