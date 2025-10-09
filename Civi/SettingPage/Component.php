<?php

namespace Civi\SettingPage;

use Civi\API\Events;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

class Component extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_alterSettingsPages' => ['registerSettingPage', Events::W_EARLY],
    ];
  }

  public function registerSettingPage(GenericHookEvent $event) {
    $event->settingsPages['component'] = [
      'title' => ts('Enable CiviCRM Components'),
      'adminGroup' => 'System Settings',
      'intro_text' => ts('CiviCRM includes several optional components which give you more tools to connect with and engage your constituents.'),
      'doc_url' => [
        'page' => 'user/introduction/components/',
      ],
      'sections' => [],
    ];
  }

}
