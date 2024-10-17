<?php

require_once 'tellafriend.civix.php';

use CRM_Tellafriend_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function tellafriend_civicrm_config(&$config): void {
  _tellafriend_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function tellafriend_civicrm_install(): void {
  _tellafriend_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function tellafriend_civicrm_enable(): void {
  _tellafriend_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_tabset().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_tabset
 */
function tellafriend_civicrm_tabset($tabsetName, &$tabs, $context) {
  // This hook behaves in very different ways depending on the $context
  // and if we add our links here, the classic "Manage Contribution Pages"
  // will fatal because of odd logic on the 'bit' attribute implicitly set
  if (!empty($context['urlParams'])) {
    return;
  }
  if ($tabsetName === 'civicrm/event/manage' || $tabsetName === 'civicrm/admin/contribute') {
    $default = [
      'link' => NULL,
      'valid' => TRUE,
      'active' => TRUE,
      'current' => FALSE,
      'class' => FALSE,
      'extra' => FALSE,
      'template' => FALSE,
      'count' => FALSE,
      'icon' => FALSE,
      'url' => $tabsetName === 'civicrm/event/manage' ? 'civicrm/event/manage/friend' : '',
      'field' => 'friend',
    ];
    $tabs['friend'] = ['title' => ts('Tell a Friend')] + $default;
  }
}

/**
 * Implements hook_civicrm_check().
 */
function tellafriend_civicrm_check(&$messages) {
  $messages[] = new CRM_Utils_Check_Message(
    'tellafriend',
    ts('The Tell-a-Friend feature will be removed from CiviCRM in a future release. This feature allows users to send an email to their friends in order to promote an event or a contribution page. The feature was seldom used and it does not work well because of how email works today. It has also largely been replaced by social media links. If you do use this feature, please share your opinion on the CiviCRM Gitlab <a %1>issue dev/core#1036</a> or email info@civicrm.org. If the feature is not used, you can <a %1>disable this extension</a>. Otherwise, you can hide this alert for now. Depending on the feedback, the feature might simply be removed or it might be moved to a community-managed (third-party) extension.', [1 => 'href="https://lab.civicrm.org/dev/core/-/issues/1036" target="_blank"']),
    ts('Tell-a-Friend feature removal'),
    \Psr\Log\LogLevel::WARNING,
    'fa-flag'
  );
}
