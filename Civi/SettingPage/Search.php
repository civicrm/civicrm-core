<?php

namespace Civi\SettingPage;

use Civi\API\Events;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoSubscriber;

class Search extends AutoSubscriber {

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
      'title' => ts('Search Preferences'),
      'adminGroup' => 'Customize Data and Screens',
      'page_callback' => 'CRM_Admin_Form_Setting_Search',
      'intro_text' => ts('Customize and optimize CiviCRM search functionality.'),
      'doc_url' => [
        'page' => 'user/initial-set-up/customizing-the-user-interface/#customizing-search-preferences',
      ],
      'sections' => [
        'search' => [
          'title' => ts('Search Configuration'),
          'icon' => 'fa-search',
          'weight' => 0,
        ],
        'autocomplete' => [
          'title' => ts('Autocompletes'),
          'icon' => 'fa-keyboard',
          'weight' => 10,
        ],
        'legacy' => [
          'title' => ts('Legacy Search Settings'),
          'description' => ts('These settings do not apply to the new SearchKit search engine.'),
          'icon' => 'fa-clock-rotate-left',
          'weight' => 50,
        ],
      ],
    ];

    $event->settingsPages['search'] = $page;
  }

}
