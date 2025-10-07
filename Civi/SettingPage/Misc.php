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

  public static function loggingMetadataCallback(array &$setting) {
    // Disable field on setting form if system does not meet requirements
    if (\CRM_Core_I18n::isMultilingual()) {
      $setting['description'] = ts('Logging is not supported in multilingual environments.');
      $setting['html_attributes']['disabled'] = 'disabled';
    }
    elseif (!\CRM_Core_DAO::checkTriggerViewPermission(FALSE)) {
      $setting['description'] = ts("In order to use this functionality, the installation's database user must have privileges to create triggers (in MySQL 5.0 – and in MySQL 5.1 if binary logging is enabled – this means the SUPER privilege). This install either does not seem to have the required privilege enabled.");
      $setting['html_attributes']['disabled'] = 'disabled';
    }
  }

}
