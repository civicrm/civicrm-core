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
class CRM_Upgrade_DispatchPolicy {

  /**
   * Create an auto-clean object which temporarily applies the preferred policy.
   *
   * @code
   * $cleanup = CRM_Upgrade_DispatchPolicy::useTemporarily('upgrade.finish');
   * doStuff();
   * unset($cleanup);
   * @endCode
   *
   * @param string $name
   * @return \CRM_Utils_AutoClean
   */
  public static function useTemporarily(string $name): CRM_Utils_AutoClean {
    Civi::dispatcher()->setDispatchPolicy(\CRM_Upgrade_DispatchPolicy::get($name));
    return \CRM_Utils_AutoClean::with(function() {
      Civi::dispatcher()->setDispatchPolicy(\CRM_Upgrade_DispatchPolicy::get('upgrade.main'));
    });
  }

  /**
   * Determine the dispatch policy
   *
   * @return array
   * @see \Civi\Core\CiviEventDispatcher::setDispatchPolicy()
   */
  public static function pick(): ?array {
    if (!\CRM_Core_Config::isUpgradeMode()) {
      return NULL;
    }

    // Have we run CRM_Upgrade_Form::doCoreFinish() for this version?
    $codeVer = CRM_Utils_System::version();
    $isCoreCurrent = CRM_Core_DAO::singleValueQuery('
        SELECT count(*) as count
        FROM civicrm_log
        WHERE entity_table = "civicrm_domain"
        AND data LIKE %1
        ', [1 => ['upgrade:%->' . $codeVer, 'String']]);

    return CRM_Upgrade_DispatchPolicy::get($isCoreCurrent < 1 ? 'upgrade.main' : 'upgrade.finish');
  }

  /**
   * Read the dispatch policy.
   *
   * @param string $phase
   *   Ex: 'upgrade.main' or 'upgrade.finish'.
   * @return array
   * @see \Civi\Core\CiviEventDispatcher::setDispatchPolicy()
   */
  public static function get($phase) {

    // Should hooks dispatch while applying CiviCRM DB upgrades? The answer is
    // mixed: it depends on the specific hook and the specific upgrade-step.
    //
    // Some example considerations:
    //
    // - If the "log_civicrm_*" tables and triggers are to be reconciled during
    //   the upgrade, then one probably needs access to the list of tables and
    //   triggers defined by extensions. These are provided by hooks.
    // - If a hook fires while the DB has stale schema, and if the hook's logic
    //   has a direct (SQL) or indirect (BAO/API) dependency on the schema, then
    //   the hook is prone to fail. (Ex: CiviCRM 4.x and the migration from
    //   civicrm_domain.config_backend to civicrm_setting.)
    // - If *any* hook from an extension is called, then it may use classes
    //   from the same extension, so the classloader / include-path / hook_config
    //   should be operational.
    // - If there is a general system flush at the end of the upgrade (to rebuild
    //   important data-structures -- routing tables, container cache, metadata
    //   cache, etc), then there's a huge number of hooks that should fire.
    // - When hooks (or variations like "rules") are used to define business-logic,
    //   they probably are not intended to fire during DB upgrade. Then again,
    //   upgrade-logic is usually written with lower-level semantics that avoid firing hooks.
    //
    // To balance these mixed considerations, the upgrade runs in two phases:
    //
    // - Defensive/conservative/closed phase ("upgrade.main"): Likely mismatch
    //   between schema+code. Low-confidence in most services (APIs/hooks/etc).
    //   Ignore caches/indices/etc. Only perform low-level schema revisions.
    // - Constructive/liberal/open phase ("upgrade.finish"): Schema+code match.
    //   Higher confidence in most services (APIs/hooks/etc).
    //   Rehydrate caches/indices/etc.
    //
    // Related discussions:
    //
    // - https://github.com/civicrm/civicrm-core/pull/17126
    // - https://github.com/civicrm/civicrm-core/pull/13551
    // - https://lab.civicrm.org/dev/core/issues/1449
    // - https://lab.civicrm.org/dev/core/issues/1460

    $strict = getenv('CIVICRM_UPGRADE_STRICT') || CRM_Utils_Constant::value('CIVICRM_UPGRADE_STRICT');
    $policies = [];

    // The "upgrade.main" policy applies during the planning and incremental revisions.
    // It's more restrictive, preventing interference from unexpected callpaths.
    $policies['upgrade.main'] = [
      'hook_civicrm_config' => 'run',
      // cleanupPermissions() in some UF's can be destructive. Running prematurely could be actively harmful.
      'hook_civicrm_permission' => 'fail',
      'hook_civicrm_crypto' => 'drop',
      '/^hook_civicrm_(pre|post)$/' => 'drop',
      '/^hook_civicrm_/' => $strict ? 'warn-drop' : 'drop',
      '/^civi\./' => 'run',
      '/./' => $strict ? 'warn-drop' : 'drop',
    ];

    // The "upgrade.finish" policy applies at the end while performing the final clear/rebuild.
    // It's more permissive, allowing more data-structures to rehydrate correctly.
    $policies['upgrade.finish'] = [
      '/^hook_civicrm_(pre|post)$/' => 'drop',
      '/./' => 'run',
    ];

    // For comparison, "upgrade.old" is an estimation of the previous policy. It
    // was applied at all times during the upgrade.
    $policies['upgrade.old'] = [
      'hook_civicrm_alterSettingsFolders' => 'run',
      'hook_civicrm_alterSettingsMetaData' => 'run',
      'hook_civicrm_triggerInfo' => 'run',
      'hook_civicrm_alterLogTables' => 'run',
      'hook_civicrm_container' => 'run',
      'hook_civicrm_permission' => 'run',
      'hook_civicrm_managed' => 'run',
      'hook_civicrm_config' => 'run',
      '/^hook_civicrm_(pre|post)$/' => 'drop',
      '/^hook_civicrm_/' => 'drop',
      '/^civi\./' => 'run',
      '/./' => 'run',
    ];

    // return $policies['upgrade.old'];
    return $policies[$phase];
  }

  /**
   * Assert that a specific policy is currently active.
   *
   * @param string $name
   *   Ex: 'upgrade.main' or 'upgrade.finish'
   * @throws \RuntimeException
   */
  public static function assertActive(string $name) {
    $expected = static::get($name);
    $actual = Civi::dispatcher()->getDispatchPolicy();
    if ($expected != $actual) {
      throw new \RuntimeException("Task can not execute correctly. The wrong dispatch policy is active. Expected to find \"$name\".");
    }
  }

  /**
   * Before running upgrade tasks, ensure that we apply the current dispatch-policy.
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function onRunTask(\Civi\Core\Event\GenericHookEvent $event) {
    if ($event->taskCtx->queue->getName() === \CRM_Upgrade_Form::QUEUE_NAME) {
      Civi::dispatcher()->setDispatchPolicy(static::pick());
    }
  }

}
