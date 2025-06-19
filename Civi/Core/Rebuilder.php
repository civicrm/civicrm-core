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

namespace Civi\Core;

use Civi;
use Civi\Api4\UserJob;
use CRM_ACL_BAO_Cache;
use CRM_Case_XMLRepository;
use CRM_Contact_BAO_Contact;
use CRM_Contribute_BAO_Contribution;
use CRM_Core_BAO_Navigation;
use CRM_Core_BAO_WordReplacement;
use CRM_Core_Config;
use CRM_Core_DAO;
use CRM_Core_DAO_AllCoreTables;
use CRM_Core_ManagedEntities;
use CRM_Core_Menu;
use CRM_Core_OptionGroup;
use CRM_Core_Resources;
use CRM_Core_Session;
use CRM_Extension_System;
use CRM_Pledge_BAO_Pledge;
use CRM_Utils_Cache;
use CRM_Utils_PseudoConstant;

/**
 * The Rebuilder can identify, select, execute any tasks needed to destroy/create any
 * ephemeral data-structures (caches, etc) in the system.
 *
 * The canonical way to access this is is \Civi::rebuild()
 * @see \Civi::rebuild()
 *
 * NOTE: The initial API emphasizes an array of targets. However, in the future, we may need changes.
 * The Rebuilder class-design should be amenable to phasing-in fluent helpers.
 */
class Rebuilder {

  private array $targets;

  /**
   * @param array $targets
   */
  public function __construct($targets) {
    if (is_string($targets)) {
      $targets = [$targets => TRUE];
    }

    $this->targets = $targets;
  }

  public function execute(): void {
    // This is a rebuild-super-function. It was produced by merging three prior rebuild-super-functions.
    // These three prior super-functions were all entwined. By merging them, we get a clearer view of
    // what's going-on. Of course, "what's going-on" includes... confusing things. Have fun!

    $targets = $this->targets;

    $all = [
      'ext' => TRUE,
      'files' => TRUE,
      'tables' => TRUE,
      'sessions' => TRUE,
      'metadata' => TRUE,
      'system' => TRUE,
      'userjob' => TRUE,
      'menu' => TRUE,
      'perms' => TRUE,
      'strings' => TRUE,
      'settings' => TRUE,
      'cases' => TRUE,
      'triggers' => TRUE,
      'entities' => TRUE,
    ];
    if (!empty($targets['*'])) {
      $targets = array_merge($all, $targets);
      unset($targets['*']);
    }

    $config = CRM_Core_Config::singleton();

    if (!empty($targets['ext'])) {
      $config->clearModuleList();

      // dev/core#3660 - Activate any new classloaders/mixins/etc before re-hydrating any data-structures.
      CRM_Extension_System::singleton()->getClassLoader()->refresh();
      CRM_Extension_System::singleton()->getMixinLoader()->run(TRUE);
    }

    if (!empty($targets['files'])) {
      $config->cleanup(1, FALSE);
    }
    if (!empty($targets['tables'])) {
      // Truncate and drop various tables that track replaceable data (e.g. ACL caches and temp-tables).

      // This is fun and confusing:
      // - On systems with Memcache/Redis, 'tables' and 'system' are mostly independent.
      // - On systems with SQL-based caches, 'tables' and 'system' are overlapping rebuilds,
      //   but neither is strictly redundant with the other.
      CRM_Core_Config::clearDBCache();
    }
    if (!empty($targets['sessions'])) {
      Civi::cache('session')->clear();
    }
    if (!empty($targets['metadata'])) {
      Civi::cache('metadata')->clear();
      CRM_Core_DAO_AllCoreTables::flush();
    }
    if (!empty($targets['system'])) {
      // flush out all cache entries so we can reload new data
      // a bit aggressive, but livable for now
      CRM_Utils_Cache::singleton()->flush();

      if (Container::isContainerBooted()) {
        Civi::cache('long')->flush();
        Civi::cache('settings')->flush();
        Civi::cache('js_strings')->flush();
        Civi::cache('angular')->clear();
        Civi::cache('community_messages')->flush();
        Civi::cache('groups')->flush();
        Civi::cache('navigation')->flush();
        Civi::cache('customData')->flush();
        Civi::cache('contactTypes')->clear();
        Civi::cache('metadata')->clear(); /* Again? Huh. */
        ClassScanner::cache('index')->flush();
        CRM_Extension_System::singleton()->getCache()->flush();
      }

      // also reset the various static memory caches

      // reset the memory or array cache
      Civi::cache('fields')->flush();

      // reset ACL cache
      CRM_ACL_BAO_Cache::resetCache();

      // clear asset builder folder
      Civi::service('asset_builder')->clear(FALSE);
      // ^^ This really doesn't make sense in this section, does it?

      // reset various static arrays used here
      CRM_Contact_BAO_Contact::$_importableFields = CRM_Contact_BAO_Contact::$_exportableFields
        = CRM_Contribute_BAO_Contribution::$_importableFields
          = CRM_Contribute_BAO_Contribution::$_exportableFields
            = CRM_Pledge_BAO_Pledge::$_exportableFields
              = CRM_Core_DAO::$_dbColumnValueCache = NULL;

      CRM_Core_OptionGroup::flushAll();
      CRM_Utils_PseudoConstant::flushAll();

      if (Container::isContainerBooted()) {
        Civi::dispatcher()->dispatch('civi.core.clearcache');
      }
    }
    if (!empty($targets['userjob'])) {
      // (1) note this used to be earlier, but was crashing because of api4 instability
      // during extension install
      // (2) I'm not sure this belongs at such a low level...
      UserJob::delete(FALSE)->addWhere('expires_date', '<', 'now')->execute();
    }
    if (!empty($targets['sessions'])) {
      $session = CRM_Core_Session::singleton();
      $session->reset(2);
    }
    if (!empty($targets['menu'])) {
      CRM_Core_Menu::store();
      CRM_Core_BAO_Navigation::resetNavigation();
    }
    if (!empty($targets['perms'])) {
      $config->cleanupPermissions();
    }
    if (!empty($targets['strings'])) {
      // rebuild word replacement cache - pass false to prevent operations redundant with this fn
      CRM_Core_BAO_WordReplacement::rebuild(FALSE);
    }
    if (!empty($targets['settings'])) {
      Civi::service('settings_manager')->flush();
    }
    if (!empty($targets['strings'])) {
      CRM_Core_Resources::singleton()->flushStrings()->resetCacheCode();
    }
    if (!empty($targets['cases'])) {
      CRM_Case_XMLRepository::singleton(TRUE);
    }
    if (!empty($targets['triggers'])) {
      Civi::service('sql_triggers')->rebuild();
      // (1) Rebuild Drupal 8/9/10 route cache only if "triggerRebuild" is set to TRUE as it's
      // computationally very expensive and only needs to be done when routes change on the Civi-side.
      // For example - when uninstalling an extension. We already set "triggerRebuild" to true for these operations.
      // (2) FIXME: That ^^ seems silly now. Shouldn't it go under $targets['menu']?
      $config->userSystem->invalidateRouteCache();
    }
    if (!empty($targets['entities'])) {
      CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    }
  }

}
