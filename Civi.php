<?php

use Civi\Core\Format;

/**
 * Class Civi
 *
 * The "Civi" class provides a facade for accessing major subsystems,
 * such as the service-container and settings manager. It serves as a
 * bridge which allows procedural code to access important objects.
 *
 * General principles:
 *  - Each function provides access to a major subsystem.
 *  - Each function performs a simple lookup.
 *  - Each function returns an interface.
 *  - Whenever possible, interfaces should be well-known (e.g. based
 *    on a standard or well-regarded provider).
 */
class Civi {

  /**
   * A central location for static variable storage.
   * @var array
   * ```
   * `Civi::$statics[__CLASS__]['foo'] = 'bar';
   * ```
   */
  public static $statics = [];

  /**
   * Retrieve a named cache instance.
   *
   * @param string $name
   *   The name of the cache. The 'default' cache is biased toward
   *   high-performance caches (eg memcache/redis/apc) when
   *   available and falls back to single-request (static) caching.
   *   Ex: 'short' or 'default' is useful for high-speed, short-lived cache data.
   *       This is appropriate if you believe that latency (millisecond-level
   *       read time) is the main factor. For example: caching data from
   *       a couple SQL queries.
   *   Ex: 'long' can be useful for longer-lived cache data. It's appropriate if
   *       you believe that longevity (e.g. surviving for several hours or a day)
   *       is more important than  millisecond-level access time. For example:
   *       caching the result of a simple metadata-query.
   *
   * @return CRM_Utils_Cache_Interface
   *   NOTE: Beginning in CiviCRM v5.4, the cache instance complies with
   *   PSR-16 (\Psr\SimpleCache\CacheInterface).
   */
  public static function cache($name = 'default') {
    return \Civi\Core\Container::singleton()->get('cache.' . $name);
  }

  /**
   * Get the service container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public static function container() {
    return Civi\Core\Container::singleton();
  }

  /**
   * Get the event dispatcher.
   *
   * @return \Civi\Core\CiviEventDispatcherInterface
   */
  public static function dispatcher() {
    // NOTE: The dispatcher object is initially created as a boot service
    // (ie `dispatcher.boot`). For compatibility with the container (eg
    // `RegisterListenersPass` and `createEventDispatcher` addons),
    // it is also available as the `dispatcher` service.
    //
    // The 'dispatcher.boot' and 'dispatcher' services are the same object,
    // but 'dispatcher.boot' is resolvable earlier during bootstrap.
    return Civi\Core\Container::getBootService('dispatcher.boot');
  }

  /**
   * @return \Civi\Core\Lock\LockManager
   */
  public static function lockManager() {
    return \Civi\Core\Container::getBootService('lockManager');
  }

  /**
   * Find or create a logger.
   *
   * @param string $channel
   *   Symbolic name (or channel) of the intended log.
   *   This should correlate to a service "log.{NAME}".
   *
   * @return \Psr\Log\LoggerInterface
   */
  public static function log($channel = 'default') {
    return \Civi\Core\Container::singleton()->get('psr_log_manager')->getLog($channel);
  }

  /**
   * Obtain the core file/path mapper.
   *
   * @return \Civi\Core\Paths
   */
  public static function paths() {
    return \Civi\Core\Container::getBootService('paths');
  }

  /**
   * Fetch a queue object.
   *
   * Note: Historically, `CRM_Queue_Queue` objects were not persistently-registered. Persistence
   * is now encouraged. This facade has a bias towards persistently-registered queues.
   *
   * @param string $name
   *   The name of a persistent/registered queue (stored in `civicrm_queue`)
   * @param array{type: string, is_autorun: bool, reset: bool, is_persistent: bool, runner: string, error: string, retry_limit: int, retry_interval: int} $params
   *   Specification for a queue.
   *   This is not required for accessing an existing queue.
   *   Specify this if you wish to auto-create the queue or to include advanced options (eg `reset`).
   *   Example: ['type' => 'Sql', 'error' => 'abort']
   *   Example: ['type' => 'SqlParallel', 'error' => 'delete']
   *   Defaults: ['reset'=>FALSE, 'is_persistent'=>TRUE, 'is_autorun'=>FALSE]
   * @return \CRM_Queue_Queue
   * @see \CRM_Queue_Service
   */
  public static function queue(string $name, array $params = []): CRM_Queue_Queue {
    $defaults = ['reset' => FALSE, 'is_persistent' => TRUE, 'status' => 'active'];
    $params = array_merge($defaults, $params, ['name' => $name]);
    return CRM_Queue_Service::singleton()->create($params);
  }

  /**
   * Obtain the formatting object.
   *
   * @return \Civi\Core\Format
   */
  public static function format(): Format {
    return new Civi\Core\Format();
  }

  /**
   * Initiate a bidirectional pipe for exchanging a series of multiple API requests.
   *
   * @param string $negotiationFlags
   *   List of pipe initialization flags. Some combination of the following:
   *    - 'v': Report version in connection header.
   *    - 'j': Report JSON-RPC flavors in connection header.
   *    - 'l': Report on login support in connection header.
   *    - 't': Trusted session. Logins do not require credentials. API calls may execute with or without permission-checks.
   *    - 'u': Untrusted session. Logins require credentials. API calls may only execute with permission-checks.
   *
   *   The `Civi::pipe()` entry-point is designed to be amenable to shell orchestration (SSH/cv/drush/wp-cli/etc).
   *   The negotiation flags are therefore condensed to individual characters.
   *
   *   It is possible to preserve compatibility while adding new default-flags. However, removing default-flags
   *   is more likely to be a breaking-change.
   *
   *   When adding a new flag, consider whether mutable `option()`s may be more appropriate.
   * @see \Civi\Pipe\PipeSession
   */
  public static function pipe(string $negotiationFlags = 'vtl'): void {
    Civi::service('civi.pipe')
      ->setIO(STDIN, STDOUT)
      ->run($negotiationFlags);
  }

  /**
   * Fetch a service from the container.
   *
   * @param string $id
   *   The service ID.
   * @return mixed
   */
  public static function service($id) {
    return \Civi\Core\Container::singleton()->get($id);
  }

  /**
   * Reset all ephemeral system state, e.g. statics,
   * singletons, containers.
   */
  public static function reset() {
    self::$statics = [];
    Civi\Core\Container::singleton();
  }

  /**
   * Rebuild the system.
   *
   * This is more expansive than Civi::reset(). Where Civi::reset() targets ephemeral state within the current process,
   * the rebuild targets shared data-structures used by all processes.
   *
   * Ex: Rebuild everything
   *   Civi::rebuild('*')->execute();
   * Ex: Rebuild the temp SQL data and the system-caches (and nothing else))
   *   Civi::rebuild(['tables' => TRUE, 'system' => TRUE])->execute();
   * Ex: Rebuild everything except the menu
   *   Civi::rebuild(['*' => TRUE, 'router' => FALSE])->execute();
   *
   * @param string|array{ext:bool,files:bool,tables:bool,sessions:bool,metadata:bool,system:bool,userjob:bool,menu:bool,perms:bool,strings:bool,settings:bool,cases:bool,triggers:bool,entities:bool}|null $targets
   *   The special key '*' indicates that all flags should start as TRUE (but you may opt-out of specific ones).
   *   Keys:
   *     - ext: Rebuild list of extensions, their hooks/mixins, etc.
   *     - files: Reset any temporary files. Recreate any mandatory flag-files.
   *     - tables: Truncate and drop any SQL tables with expendable data (e.g. ACL caches and import-temp-tables).
   *     - sessions: Reset any form-state stored in user-sessions
   *     - metadata: Rebuild metadata about the available entities and fields
   *     - system: Reset any cache-services defined by the system.
   *     - userjob: Delete any expired UserJob records.
   *     - menu: (DEPRECATED 6.9+) Equivalent to 'router' + 'navigation' + 'system'.
   *     - navigation: (ADDED 6.9) Reset navigation indices for all users.
   *     - perms: Republish the list of available permissions. (Some CMS's need to be notified.)
   *     - router: (ADDED 6.9) Rebuild list of available HTTP routes.
   *     - strings: Reset caches involving visible strings (WordReplacements, JS ts()).
   *     - settings: Rebuild the index of available settings and their values.
   *     - cases: Somethingsomething.
   *     - triggers: Rebuild the SQL triggers.
   *     - entities: Reconcile the managed-entities.
   *
   * @return Civi\Core\Rebuilder
   */
  public static function rebuild($targets): Civi\Core\Rebuilder {
    return new Civi\Core\Rebuilder($targets);
  }

  /**
   * @return CRM_Core_Resources
   */
  public static function resources() {
    return CRM_Core_Resources::singleton();
  }

  /**
   * Obtain the contact's personal settings.
   *
   * @param NULL|int $contactID
   *   For the default/active user's contact, leave $domainID as NULL.
   * @param NULL|int $domainID
   *   For the default domain, leave $domainID as NULL.
   * @return \Civi\Core\SettingsBag
   * @throws CRM_Core_Exception
   *   If there is no contact, then there's no SettingsBag, and we'll throw
   *   an exception.
   */
  public static function contactSettings($contactID = NULL, $domainID = NULL) {
    return \Civi\Core\Container::getBootService('settings_manager')->getBagByContact($domainID, $contactID);
  }

  /**
   * Obtain the domain settings.
   *
   * @param int|null $domainID
   *   For the default domain, leave $domainID as NULL.
   * @return \Civi\Core\SettingsBag
   */
  public static function settings($domainID = NULL) {
    return \Civi\Core\Container::getBootService('settings_manager')->getBagByDomain($domainID);
  }

  /**
   * Construct a URL based on a logical service address. For example:
   *
   *   Civi::url('frontend://civicrm/user?reset=1');
   *
   *   Civi::url()
   *     ->setScheme('frontend')
   *     ->setPath(['civicrm', 'user'])
   *     ->setQuery(['reset' => 1])
   *
   * URL building follows a few rules:
   *
   * 1. You may initialize with a baseline URL.
   * 2. The scheme indicates the general type of URL ('frontend://', 'backend://', 'asset://', 'assetBuilder://').
   * 3. The result object provides getters, setters, and adders (e.g. `getScheme()`, `setPath(...)`, `addQuery(...)`)
   * 4. Strings are raw. Arrays are auto-encoded. (`addQuery('name=John+Doughnut')` or `addQuery(['name' => 'John Doughnut'])`)
   * 5. You may use variable expressions (`id=[contact]&gid=[profile]`).
   * 6. The URL can be cast to string (aka `__toString()`).
   *
   * If you are converting from `CRM_Utils_System::url()` to `Civi::url()`, then be sure to:
   *
   * - Pay attention to the scheme (eg 'current://' vs 'frontend://')
   * - Pay attention to HTML escaping, as the behavior changed:
   *      - Civi::url() returns plain URLs (eg "id=100&gid=200") by default
   *      - CRM_Utils_System::url() returns HTML-escaped URLs (eg "id=100&amp;gid=200") by default
   *
   * Here are several examples:
   *
   * Ex: Link to constituent's dashboard (on frontend UI or backend UI -- based on the active scheme of current page-view)
   *   $url = Civi::url('current://civicrm/user?reset=1');
   *   $url = Civi::url('//civicrm/user?reset=1');
   *
   * Ex: Link to constituent's dashboard (with method calls - good for dynamic options)
   *   $url = Civi::url('frontend:')
   *     ->setPath('civicrm/user')
   *     ->addQuery(['reset' => 1]);
   *
   * Ex: Link to constituent's dashboard (with quick flags: absolute URL, SSL required, HTML escaping)
   *   $url = Civi::url('frontend://civicrm/user?reset=1', 'ash');
   *
   * Ex: Link to constituent's dashboard (with method flags - good for dynamic options)
   *   $url = Civi::url('frontend://civicrm/user?reset=1')
   *     ->setPreferFormat('absolute')
   *     ->setSsl(TRUE)
   *     ->setHtmlEscape(TRUE);
   *
   * Ex: Link to a dynamically generated asset-file.
   *   $url = Civi::url('assetBuilder://crm-l10n.js?locale=en_US');
   *
   * Ex: Link to a static asset (resource-file) in one of core's configurable paths.
   *   $url = Civi::url('[civicrm.root]/js/Common.js');
   *
   * Ex: Link to a static asset (resource-file) in an extension.
   *   $url = Civi::url('ext://org.civicrm.search_kit/css/crmSearchTasks.css');
   *
   * Ex: Link with variable substitution
   *   $url = Civi::url('frontend://civicrm/ajax/api4/[entity]/[action]')
   *      ->addVars(['entity' => 'Foo', 'action' => 'bar']);
   *
   * @param string|null $logicalUri
   *   Logical URI. The scheme of the URI may be one of:
   *     - 'frontend://' (Front-end page-route for constituents)
   *     - 'backend://' (Back-end page-route for staff)
   *     - 'service://' (Web-service page-route for automated integrations; aka webhooks and IPNs)
   *     - 'current://' (Whichever UI is currently active)
   *     - 'default://' (Whichever UI is recorded in the metadata)
   *     - 'asset://' (Static asset-file; see \Civi::paths())
   *     - 'assetBuilder://' (Dynamically-generated asset-file; see \Civi\Core\AssetBuilder)
   *     - 'ext://' (Static asset-file provided by an extension)
   *   An empty scheme (`//hello.txt`) is equivalent to `current://hello.txt`.
   * @param string|null $flags
   *   List of flags. Some combination of the following:
   *   - 'a': absolute (aka `setPreferFormat('absolute')`)
   *   - 'r': relative (aka `setPreferFormat('relative')`)
   *   - 'h': html (aka `setHtmlEscape(TRUE)`)
   *   - 't': text (aka `setHtmlEscape(FALSE)`)
   *   - 's': ssl (aka `setSsl(TRUE)`)
   *   - 'c': cache code for resources (aka Civi::resources()->addCacheCode())
   * @return \Civi\Core\Url
   *   URL object which may be modified or rendered as text.
   */
  public static function url(?string $logicalUri = NULL, ?string $flags = NULL): \Civi\Core\Url {
    return new \Civi\Core\Url($logicalUri, $flags);
  }

  /**
   * Get the canonical entityProvider for a given entity type.
   *
   * @param string $entityName
   * @return \Civi\Schema\EntityProvider
   */
  public static function entity(string $entityName): \Civi\Schema\EntityProvider {
    return new \Civi\Schema\EntityProvider($entityName);
  }

  /**
   * Get the canonical entityProvider for a given entity table.
   *
   * @param string $tableName
   * @return \Civi\Schema\EntityProvider
   */
  public static function table(string $tableName): \Civi\Schema\EntityProvider {
    $entityName = \Civi\Schema\EntityRepository::getTableIndex()[$tableName];
    return new \Civi\Schema\EntityProvider($entityName);
  }

  /**
   * Get the schema-helper for CiviCRM (core-core).
   *
   * @param string $key
   *   Ex: 'civicrm' or 'org.example.myextension'
   * @return \CiviMix\Schema\SchemaHelperInterface
   */
  public static function schemaHelper(string $key = 'civicrm'): \CiviMix\Schema\SchemaHelperInterface {
    if (!isset(Civi::$statics['schemaHelper'])) {
      pathload()->loadPackage('civimix-schema@5');
      Civi::$statics['schemaHelper'] = TRUE;
    }
    return $GLOBALS['CiviMixSchema']->getHelper($key);
  }

}
