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
 * Generate a pre-upgrade data-snapshot -- if the local policy supports them.
 *
 * (Rule of thumb: Small databases enable snapshots. Large databases and multi-lingual
 * databases do not. Some sysadmins may force-enable or force-disable snapshots.)
 *
 * Example: Before modifying `civicrm_foobar.some_field`, make a snapshot of that column
 *
 *   {crmUpgradeSnapshot name=foobar}
 *     SELECT id, some_field FROM civicrm_foobar
 *   {/crmUpgradeSnapshot}
 *   UPDATE civicrm_foobar SET some_field = 999 WHERE some_field = 666;
 *
 * TIP: If you are modifying a large table (like `civicrm_contact` or `civicrm_mailing_event_queue`),
 * then you probably shouldn't use `*.mysql.tpl` because it doesn't paginate operations. Similarly,
 * `{crmUpgradeSnapshot}` doesn't paginate. For pagination, use non-Smarty upgrade-tasks.
 *
 * @see \CRM_Upgrade_Snapshot
 *
 * @param array $params
 * @param string|null $text
 *   The SELECT query which supplies the interesting data to be stored in the snapshot.
 * @param CRM_Core_Smarty $smarty
 * @param bool $repeat
 *   Repeat is true for the opening tag, false for the closing tag
 * @return string|null
 * @throws \CRM_Core_Exception
 */
function smarty_block_crmUpgradeSnapshot($params, $text, &$smarty, &$repeat) {
  if ($repeat || $text === NULL) {
    return NULL;
  }

  if (empty($params['name'])) {
    throw new \CRM_Core_Exception('Failed to process {crmUpgradeSnapshot}: Missing name');
  }
  if (empty($smarty->getTemplateVars('upgradeRev'))) {
    throw new \CRM_Core_Exception('Failed to process {crmUpgradeSnapshot}: Upgrade context required. $upgradeRev missing.');
  }
  if (!preg_match(';^\s*select\s;i', $text)) {
    throw new \CRM_Core_Exception('Failed to process {crmUpgradeSnapshot}: Query does not look valid');
  }

  $owner = $params['owner'] ?? 'civicrm';
  $revParts = explode('.', $smarty->getTemplateVars('upgradeRev'));
  $queries = CRM_Upgrade_Snapshot::createSingleTask($owner, $revParts[0] . '.' . $revParts[1], $params['name'], $text);
  return $queries ? (implode(";\n", $queries) . ";\n") : "";
}
