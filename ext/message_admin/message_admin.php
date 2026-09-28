<?php

require_once 'message_admin.civix.php';
// phpcs:disable
use CRM_MessageAdmin_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function message_admin_civicrm_config(&$config) {
  _message_admin_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function message_admin_civicrm_install() {
  _message_admin_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function message_admin_civicrm_enable() {
  _message_admin_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_searchKitTasks().
 *
 * Exposes the message-template actions that have no generic equivalent, so a
 * SearchKit listing can offer them as row actions and as bulk actions.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchKitTasks
 */
function message_admin_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userID) {
  // Translations are only meaningful once the site offers more than one language,
  // which is the same test the editor uses to decide whether to show them at all.
  if ((!$checkPermissions || CRM_Core_Permission::check('translate CiviCRM'))
    && count(CRM_MessageAdmin_Settings::getAvailableLanguages()) > 1
  ) {
    $tasks['MessageTemplate']['add_translation'] = [
      'title' => E::ts('Add Translation'),
      'icon' => 'fa-language',
      'module' => 'crmMsgadmTasks',
      'uiDialog' => ['templateUrl' => '~/crmMsgadmTasks/AddTranslation.html'],
      // A translation belongs to one template, and the dialog opens the editor for it.
      'number' => '=== 1',
    ];
  }

  // `revert` has no entry in getEntityActionPermissions(), so it falls back to the
  // stricter default rather than the permissions `update` uses. Ask the API which
  // actions this user has rather than restating that fallback here.
  $canRevert = civicrm_api4('MessageTemplate', 'getActions', [
    'checkPermissions' => $checkPermissions,
    'where' => [['name', '=', 'revert']],
  ]);
  if ($canRevert->count()) {
    $tasks['MessageTemplate']['revert'] = [
      'title' => E::ts('Revert to Default'),
      'icon' => 'fa-undo',
      'apiBatch' => [
        'action' => 'revert',
        'params' => NULL,
        'confirmMsg' => E::ts('Are you sure you want to discard local changes to %1 %2?'),
        'runMsg' => E::ts('Reverting %1 %2...'),
        'successMsg' => E::ts('Successfully reverted %1 %2.'),
        'errorMsg' => E::ts('An error occurred while attempting to revert %1 %2.'),
      ],
    ];
  }

}
