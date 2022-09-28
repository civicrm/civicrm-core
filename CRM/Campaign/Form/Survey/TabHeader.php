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

/**
 * Helper class to build navigation links
 */
class CRM_Campaign_Form_Survey_TabHeader {

  /**
   * Build tab header.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = self::process($form);
      $form->set('tabHeader', $tabs);
    }
    $form->assign_by_ref('tabHeader', $tabs);
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/common/TabHeader.js', 1, 'html-header')
      ->addSetting([
        'tabSettings' => [
          'active' => self::getCurrentTab($tabs),
        ],
      ]);
    return $tabs;
  }

  /**
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function process(&$form) {
    if ($form->getVar('_surveyId') <= 0) {
      return NULL;
    }

    $tabs = [
      'main' => [
        'title' => ts('Main Information'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'questions' => [
        'title' => ts('Questions'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
      'results' => [
        'title' => ts('Results'),
        'link' => NULL,
        'valid' => FALSE,
        'active' => FALSE,
        'current' => FALSE,
      ],
    ];

    $surveyID = $form->getVar('_surveyId');
    $class = $form->getVar('_name');
    $class = CRM_Utils_String::getClassName($class);
    $class = strtolower($class);

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($surveyID) {
      $reset = !empty($_GET['reset']) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $tabs[$key]['link'] = CRM_Utils_System::url("civicrm/survey/configure/{$key}",
          "{$reset}action=update&id={$surveyID}{$tabs[$key]['qfKey']}"
        );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }
    }
    return $tabs;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function reset(&$form) {
    $tabs = self::process($form);
    $form->set('tabHeader', $tabs);
  }

  /**
   * @param $tabs
   *
   * @return int|string
   */
  public static function getCurrentTab($tabs) {
    static $current = FALSE;

    if ($current) {
      return $current;
    }

    if (is_array($tabs)) {
      foreach ($tabs as $subPage => $pageVal) {
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
          break;
        }
      }
    }

    $current = $current ? $current : 'main';
    return $current;
  }

  /**
   * @param $form
   *
   * @return int|string
   */
  public static function getNextTab(&$form) {
    static $next = FALSE;
    if ($next) {
      return $next;
    }

    $tabs = $form->get('tabHeader');
    if (is_array($tabs)) {
      $current = FALSE;
      foreach ($tabs as $subPage => $pageVal) {
        if ($current) {
          $next = $subPage;
          break;
        }
        if ($pageVal['current'] === TRUE) {
          $current = $subPage;
        }
      }
    }

    $next = $next ? $next : 'main';
    return $next;
  }

}
