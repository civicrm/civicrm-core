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
 * Helper class to build navigation links.
 */
class CRM_Contribute_Form_ContributionPage_TabHeader {

  /**
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function build(&$form) {
    $tabs = $form->get('tabHeader');
    if (!$tabs || empty($_GET['reset'])) {
      $tabs = self::process($form) ?? [];
      $form->set('tabHeader', $tabs);
    }
    $tabs = \CRM_Core_Smarty::setRequiredTabTemplateKeys($tabs);
    $form->assign('tabHeader', $tabs);
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
   * @return array|null
   */
  public static function process(&$form) {
    if ($form->getVar('_id') <= 0) {
      return NULL;
    }

    $default = [
      'link' => NULL,
      'valid' => FALSE,
      'active' => FALSE,
      'current' => FALSE,
      'class' => FALSE,
      'extra' => FALSE,
      'template' => FALSE,
      'count' => FALSE,
      'icon' => FALSE,
    ];

    $tabs = [
      'settings' => [
        'title' => ts('Title'),
      ] + $default,
      'amount' => [
        'title' => ts('Amounts'),
      ] + $default,
      'membership' => [
        'title' => ts('Memberships'),
      ] + $default,
      'thankyou' => [
        'title' => ts('Receipt'),
      ] + $default,
      'custom' => [
        'title' => ts('Profiles'),
      ] + $default,
      'premium' => [
        'title' => ts('Premiums'),
      ] + $default,
      'widget' => [
        'title' => ts('Widgets'),
      ] + $default,
      'pcp' => [
        'title' => ts('Personal Campaigns'),
      ] + $default,
    ];

    $contribPageId = $form->getVar('_id');
    // Call tabset hook to add/remove custom tabs
    CRM_Utils_Hook::tabset('civicrm/admin/contribute', $tabs, ['contribution_page_id' => $contribPageId]);
    $fullName = $form->getVar('_name');
    $className = CRM_Utils_String::getClassName($fullName);

    // Hack for special cases.
    switch ($className) {
      case 'Contribute':
        $attributes = $form->getVar('_attributes');
        $class = CRM_Utils_Request::retrieveComponent($attributes);
        break;

      case 'MembershipBlock':
        $class = 'membership';
        break;

      default:
        $class = strtolower($className);
        break;
    }

    if (array_key_exists($class, $tabs)) {
      $tabs[$class]['current'] = TRUE;
      $qfKey = $form->get('qfKey');
      if ($qfKey) {
        $tabs[$class]['qfKey'] = "&qfKey={$qfKey}";
      }
    }

    if ($contribPageId) {
      $reset = !empty($_GET['reset']) ? 'reset=1&' : '';

      foreach ($tabs as $key => $value) {
        if (!isset($tabs[$key]['qfKey'])) {
          $tabs[$key]['qfKey'] = NULL;
        }

        $tabs[$key]['link'] = CRM_Utils_System::url(
            "civicrm/admin/contribute/{$key}",
            "{$reset}action=update&id={$contribPageId}{$tabs[$key]['qfKey']}"
          );
        $tabs[$key]['active'] = $tabs[$key]['valid'] = TRUE;
      }
      //get all section info.
      $contriPageInfo = CRM_Contribute_BAO_ContributionPage::getSectionInfo([$contribPageId]);

      foreach ($contriPageInfo[$contribPageId] as $section => $info) {
        if (!$info) {
          $tabs[$section]['valid'] = FALSE;
        }
      }
    }
    return $tabs;
  }

  /**
   * @param $form
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

    $current = $current ?: 'settings';
    return $current;
  }

}
