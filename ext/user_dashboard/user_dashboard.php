<?php

require_once 'user_dashboard.civix.php';
use CRM_UserDashboard_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function user_dashboard_civicrm_config(&$config): void {
  _user_dashboard_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function user_dashboard_civicrm_install(): void {
  _user_dashboard_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function user_dashboard_civicrm_enable(): void {
  _user_dashboard_civix_civicrm_enable();
}

/**
 * Tag SavedSearches with the "UserDashboard" tag.
 *
 * The reason for using this hook is that it's write-once (just during insert),
 * and after that the user can freely tag and untag searches.
 *
 * If the tag was part of the .mgd.php file for each search then it would "stick" and
 * the user would not be able to remove tags without their changes reverting on every managed reconcile.
 * Also, adding it to the .mgd.php file requires hacking the exported api call with a 'chain', etc.
 *
 * @implements CRM_Utils_Hook::post()
 */
function user_dashboard_civicrm_post($action, $entity, $id, $savedSearch) {
  if ($entity !== 'SavedSearch' || $action !== 'create' || !str_starts_with($savedSearch->name, 'UserDashboard_')) {
    return;
  }

  // Transition note: the legacy dashboard used a setting ('user_dashboard_options')
  // to control which panes are enabled.
  // This new extension uses a tag.
  // For the next year or so, we'll conditionally tag the searches in this extension based on that setting.
  // When the transition is complete, the setting can be deleted and this class can be simplified to unconditionally
  // tag all SavedSearches in this extension.
  $legacySetting = Civi\Api4\Setting::get(FALSE)
    ->addSelect('user_dashboard_options:name')
    ->execute()
    ->first();

  // If the legacy setting corresponding to this pane is enabled, tag it
  $settingNames = [
    'UserDashboard_Activities' => 'Assigned Activities',
    'UserDashboard_Groups' => 'Groups',
    'UserDashboard_Pledges' => 'CiviPledge',
    'UserDashboard_Contributions' => 'CiviContribute',
    'UserDashboard_Events' => 'CiviEvent',
    'UserDashboard_Memberships' => 'CiviMember',
    'UserDashboard_PCPs' => 'PCP',
    'UserDashboard_Relationships' => 'Permissioned Orgs',
  ];

  $settingName = $settingNames[$savedSearch->name] ?? NULL;

  if (!$settingName || in_array($settingName, $legacySetting['value'], TRUE)) {
    Civi\Api4\EntityTag::save(FALSE)
      ->addRecord(['entity_table' => 'civicrm_saved_search', 'entity_id' => $id, 'tag_id:name' => 'UserDashboard'])
      ->execute();
  }
}

/**
 * @param \Civi\Angular\Manager $angular
 * @see \CRM_Utils_Hook::alterAngular()
 */
Civi::dispatcher()->addListener('&hook_civicrm_alterAngular', function(\Civi\Angular\Manager $angular) {
  $changeSet = \Civi\Angular\ChangeSet::create('afsearchUserDashboard')
    ->alterHtml('~/afsearchUserDashboard/afsearchUserDashboard.aff.html', function (phpQueryObject $doc) {
      $f = require __DIR__ . '/ang/afsearchUserDashboard.alterLayout.php';
      $f($doc);
    });
  $angular->add($changeSet);
}, 1500);
