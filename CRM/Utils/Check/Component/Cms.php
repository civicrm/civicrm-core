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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Cms extends CRM_Utils_Check_Component {

  /**
   * For sites running in WordPress, make sure the configured base page exists.
   *
   * @return array
   *   Instances of CRM_Utils_Check_Message
   */
  public static function checkWpBasePage() {
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework != 'WordPress') {
      return [];
    }
    if (is_multisite()) {
      // There are a lot potential configurations in a multisite context where
      // this could show a false positive.  This completely skips multisite for
      // now.
      return [];
    }
    $messages = [];

    $slug = $config->wpBasePage;
    $pageArgs = [
      'name' => $slug,
      'post_type' => 'page',
      'post_status' => 'publish',
      'numberposts' => 1,
    ];
    $basePage = get_posts($pageArgs);
    if (!$basePage) {
      $cmsSettings = CRM_Utils_System::url(
        'civicrm/admin/setting',
        $query = ['reset' => 1],
        FALSE,
        NULL,
        TRUE,
        FALSE,
        TRUE
      );
      $messageText = [
        ts(
          'CiviCRM relies upon a base page in WordPress at %1%2, but it is missing.',
          [
            1 => $config->userFrameworkBaseURL,
            2 => $slug,
          ]
        ),
      ];
      if ($slug == 'civicrm') {
        $messageText[] = ts(
          'If you have an alternative base page, it can be set in the <a href="%2">WordPress integration settings</a>.',
          [
            1 => $config->userFrameworkBaseURL,
            2 => $cmsSettings,
          ]
        );
      }
      else {
        $pageArgs['name'] = 'civicrm';
        $defaultBasePage = get_posts($pageArgs);
        if ($defaultBasePage) {
          $messageText[] = ts(
            'The default is %1civicrm, which <a href="%1civicrm">does exist on this site</a>.',
            [1 => $config->userFrameworkBaseURL]
          );
        }
        else {
          $messageText[] = ts(
            'The default is %1civicrm, but that does not exist on this site either.',
            [1 => $config->userFrameworkBaseURL]
          );
        }
        $messageText[] = ts(
          'You can set the correct base page in the <a href="%1">WordPress integration settings</a>.',
          [1 => $cmsSettings]
        );
      }
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        implode(' ', $messageText),
        ts('WordPress Base Page Missing'),
        \Psr\Log\LogLevel::ERROR,
        'fa-wordpress'
      );
    }

    return $messages;
  }

}
