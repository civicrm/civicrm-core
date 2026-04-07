<?php

namespace Civi\Riverlea;

use CRM_Riverlea_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service riverlea.checks
 */
class Checks extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      '&hook_civicrm_check' => ['checkRiverleaDrupalTheme', 0],
    ];
  }

  /**
   * @see \CRM_Utils_Hook::check()
   */
  public function checkRiverleaDrupalTheme(&$messages, $statusNames = [], $includeDisabled = FALSE) {
    if ($statusNames && !in_array(__FUNCTION__, $statusNames)) {
      return;
    }
    if (CIVICRM_UF !== 'Drupal') {
      return;
    }

    global $theme_key;

    $bad = ['bartik' => 'Drupal Bartik'];
    // $neutral = ['garland' => 'Garland'];
    $good = ['seven' => 'Drupal Seven'];

    if (isset($bad[$theme_key])) {
      $body = ts('The current theme (%1) integrates poorly with CiviCRM. For a better experience, evaluate alternative themes (such as %2).', [
        1 => $bad[$theme_key],
        2 => implode('", "', $good),
      ]);

      $body .= '<br/><br/>';

      if (function_exists('civicrmtheme_custom_theme')) {
        $body .= ts('TIP: You may set the Drupal theme for CiviCRM without changing the entire site. See: "<a %1>Administration: Appearance</a>"', [
          1 => 'href="' . htmlentities(url('admin/appearance')) . '"',
        ]);
      }
      else {
        $body .= ts('TIP: For more theming options, enable the module "CiviCRM Theme". See: "<a %1>Administration: Modules</a>"', [
          1 => 'href="' . htmlentities(url('admin/modules')) . '"',
        ]);
      }

      $messages[] = new \CRM_Utils_Check_Message(__FUNCTION__, $body, ts('Theme Compatibility'), LogLevel::WARNING, 'fa-diamond');
    }

  }

}
