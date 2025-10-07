<?php

namespace Civi\SettingPage;

use Civi\API\Events;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

class Misc extends AutoSubscriber {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_alterSettingsPages' => ['registerSettingPage', Events::W_EARLY],
    ];
  }

  public function registerSettingPage(GenericHookEvent $event) {
    $page = [
      'title' => ts('Misc (Undelete, PDFs, Limits, Logging, etc.)'),
      'adminGroup' => 'System Settings',
      'page_callback' => 'CRM_Admin_Form_Setting_Miscellaneous',
      'sections' => [
        'history' => [
          'title' => ts('History'),
          'icon' => 'fa-hourglass',
          'weight' => 10,
        ],
        'performance' => [
          'title' => ts('Performance'),
          'icon' => 'fa-gauge',
          'weight' => 20,
        ],
        'security' => [
          'title' => ts('Security'),
          'icon' => 'fa-lock',
          'weight' => 30,
        ],
        'files' => [
          'title' => ts('File Attachments'),
          'icon' => 'fa-paperclip',
          'weight' => 40,
        ],
        'pdf' => [
          'title' => ts('PDF Settings'),
          'icon' => 'fa-file-pdf',
          'weight' => 50,
        ],
      ],
    ];

    $event->settingsPages['misc'] = $page;
  }

}
