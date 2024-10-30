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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\OptionValue;
use Civi\Api4\Utils\CoreUtil;

/**
 * Recent items utility class.
 */
class CRM_Utils_Recent {

  /**
   * Store name
   *
   * @var string
   */
  const STORE_NAME = 'CRM_Utils_Recent';

  /**
   * Max number of recent items to store
   *
   * @var int
   */
  const MAX_ITEMS = 30;

  /**
   * The list of recently viewed items.
   *
   * @var array
   */
  static private $_recent = NULL;

  /**
   * Maximum stack size
   * @var int
   */
  static private $_maxItems = 10;

  /**
   * Initialize this class and set the static variables.
   */
  public static function initialize() {
    $maxItemsSetting = Civi::settings()->get('recentItemsMaxCount');
    if (isset($maxItemsSetting) && $maxItemsSetting > 0 && $maxItemsSetting < self::MAX_ITEMS) {
      self::$_maxItems = $maxItemsSetting;
    }
    if (!self::$_recent) {
      $session = CRM_Core_Session::singleton();
      self::$_recent = $session->get(self::STORE_NAME);
      if (!self::$_recent) {
        self::$_recent = [];
      }
    }
  }

  /**
   * Return the recently viewed array.
   *
   * @return array
   *   the recently viewed array
   */
  public static function &get() {
    self::initialize();
    return self::$_recent;
  }

  /**
   * Create function used by the API - supplies defaults
   *
   * @param array $params
   * @param Civi\Api4\Generic\AbstractAction $action
   */
  public static function create(array $params, Civi\Api4\Generic\AbstractAction $action) {
    if ($action->getCheckPermissions()) {
      $allowed = civicrm_api4($params['entity_type'], 'checkAccess', [
        'action' => 'get',
        'values' => ['id' => $params['entity_id']],
      ], 0);
      if (empty($allowed['access'])) {
        return [];
      }
    }
    $params['title'] ??= self::getTitle($params['entity_type'], $params['entity_id']);
    $params['view_url'] ??= self::getUrl($params['entity_type'], $params['entity_id'], 'view');
    $params['edit_url'] ??= self::getUrl($params['entity_type'], $params['entity_id'], 'update');
    $params['delete_url'] ??= (empty($params['is_deleted']) ? self::getUrl($params['entity_type'], $params['entity_id'], 'delete') : NULL);
    self::add($params['title'], $params['view_url'], $params['entity_id'], $params['entity_type'], $params['contact_id'] ?? NULL, NULL, $params);
    return $params;
  }

  /**
   * Add an item to the recent stack.
   *
   * @param string $title
   *   The title to display.
   * @param string $url
   *   The link for the above title.
   * @param string $entityId
   *   Object id.
   * @param string $entityType
   * @param int $contactId
   *   Deprecated, probably unused param
   * @param string $contactName
   *   Deprecated, probably unused param
   * @param array $others
   */
  public static function add(
    $title,
    $url,
    $entityId,
    $entityType,
    $contactId,
    $contactName,
    $others = []
  ) {
    $entityType = self::normalizeEntityType($entityType);

    // Abort if this entity type is not supported
    if (!self::isProviderEnabled($entityType)) {
      return;
    }

    // Ensure item is not already present in list
    self::removeItems(['entity_id' => $entityId, 'entity_type' => $entityType]);

    if (!is_array($others)) {
      $others = [];
    }

    array_unshift(self::$_recent,
      [
        'title' => $title,
        // TODO: deprecate & remove "url" in favor of "view_url"
        'url' => $url,
        'view_url' => $url,
        // TODO: deprecate & remove "id" in favor of "entity_id"
        'id' => $entityId,
        'entity_id' => (int) $entityId,
        // TODO: deprecate & remove "type" in favor of "entity_type"
        'type' => $entityType,
        'entity_type' => $entityType,
        // Deprecated param
        'contact_id' => $contactId,
        // Param appears to be unused
        'contactName' => $contactName,
        'subtype' => $others['subtype'] ?? NULL,
        // TODO: deprecate & remove "isDeleted" in favor of "is_deleted"
        'isDeleted' => $others['is_deleted'] ?? $others['isDeleted'] ?? FALSE,
        'is_deleted' => (bool) ($others['is_deleted'] ?? $others['isDeleted'] ?? FALSE),
        // imageUrl is deprecated
        'image_url' => $others['imageUrl'] ?? NULL,
        'edit_url' => $others['edit_url'] ?? $others['editUrl'] ?? NULL,
        'delete_url' => $others['delete_url'] ?? $others['deleteUrl'] ?? NULL,
        'icon' => $others['icon'] ?? self::getIcon($entityType, $entityId),
      ]
    );

    // Keep the list trimmed to max length
    while (count(self::$_recent) > self::$_maxItems) {
      array_pop(self::$_recent);
    }

    CRM_Utils_Hook::recent(self::$_recent);

    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Get default title for this item, based on the entity's `label_field`
   *
   * @param string $entityType
   * @param int $entityId
   * @return string|null
   */
  private static function getTitle($entityType, $entityId) {
    $labelField = CoreUtil::getInfoItem($entityType, 'label_field');
    $title = NULL;
    if ($labelField) {
      $record = civicrm_api4($entityType, 'get', [
        'where' => [['id', '=', $entityId]],
        'select' => [$labelField],
        'checkPermissions' => FALSE,
      ], 0);
      $title = $record[$labelField] ?? NULL;
    }
    return $title ?? (CoreUtil::getInfoItem($entityType, 'title'));
  }

  /**
   * Get a link to view/update/delete a given entity.
   *
   * @param string $entityType
   * @param int $entityId
   * @param string $action
   *   Either 'view', 'update', or 'delete'
   * @return string|null
   */
  private static function getUrl($entityType, $entityId, $action) {
    if ($action !== 'view') {
      $check = civicrm_api4($entityType, 'checkAccess', [
        'action' => $action,
        'values' => ['id' => $entityId],
      ], 0);
      if (empty($check['access'])) {
        return NULL;
      }
    }
    $paths = (array) CoreUtil::getInfoItem($entityType, 'paths');
    if (!empty($paths[$action])) {
      // Find tokens used in the path
      $tokens = self::getTokens($paths[$action]) ?: ['id' => '[id]'];
      // If the only token is id, no lookup needed
      if ($tokens === ['id' => '[id]']) {
        $record = ['id' => $entityId];
      }
      else {
        // Lookup values needed for tokens
        $record = civicrm_api4($entityType, 'get', [
          'checkPermissions' => FALSE,
          'select' => array_keys($tokens),
          'where' => [['id', '=', $entityId]],
        ])->first() ?: [];
      }
      ksort($tokens);
      ksort($record);
      return CRM_Utils_System::url(str_replace($tokens, $record, $paths[$action]));
    }
    return NULL;
  }

  /**
   * Get a list of square-bracket tokens from a path string
   *
   * @param string $str
   * @return array
   */
  private static function getTokens($str):array {
    $matches = $tokens = [];
    preg_match_all('/\\[([^]]+)\\]/', $str, $matches);
    foreach ($matches[1] as $match) {
      $tokens[$match] = '[' . $match . ']';
    }
    return $tokens;
  }

  /**
   * @param $entityType
   * @param $entityId
   * @return string|null
   */
  private static function getIcon($entityType, $entityId) {
    $icon = NULL;
    $daoClass = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($entityType);
    if ($daoClass) {
      $icon = CRM_Core_DAO_AllCoreTables::getBAOClassName($daoClass)::getEntityIcon($entityType, $entityId);
    }
    return $icon ?: 'fa-gear';
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->id && CRM_Core_Session::getLoggedInContactID()) {
      $entityType = self::normalizeEntityType($event->entity);
      if ($event->action === 'delete') {
        // Is this an entity that might be in the recent items list?
        $providersPermitted = Civi::settings()->get('recentItemsProviders') ?: array_keys(self::getProviders());
        if (in_array($entityType, $providersPermitted)) {
          self::del(['entity_id' => $event->id, 'entity_type' => $entityType]);
        }
      }
      elseif ($event->action === 'edit') {
        if (isset($event->object->is_deleted)) {
          \Civi\Api4\RecentItem::update(FALSE)
            ->addWhere('entity_type', '=', $entityType)
            ->addWhere('entity_id', '=', $event->id)
            ->addValue('is_deleted', (bool) $event->object->is_deleted)
            ->execute();
        }
      }
    }
  }

  /**
   * Remove items from the array that match given props
   * @param array $props
   */
  private static function removeItems(array $props) {
    self::initialize();

    self::$_recent = array_filter(self::$_recent, function($item) use ($props) {
      foreach ($props as $key => $val) {
        if (($item[$key] ?? NULL) != $val) {
          return TRUE;
        }
      }
      return FALSE;
    });
  }

  /**
   * Delete item(s) from the recently-viewed list.
   *
   * @param array $removeItem
   *   Item to be removed.
   */
  public static function del($removeItem) {
    self::removeItems($removeItem);
    CRM_Utils_Hook::recent(self::$_recent);
    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Delete an item from the recent stack.
   *
   * @param string $id
   * @deprecated
   */
  public static function delContact($id) {
    CRM_Core_Error::deprecatedFunctionWarning('del');
    self::del(['contact_id' => $id]);
  }

  /**
   * Check if a provider is allowed to add stuff.
   * If corresponding setting is empty, all are allowed
   *
   * @param string $providerName
   * @return bool
   */
  public static function isProviderEnabled($providerName) {
    $allowed = TRUE;

    // Use core setting recentItemsProviders if configured
    $providersPermitted = Civi::settings()->get('recentItemsProviders');
    if ($providersPermitted) {
      $allowed = in_array($providerName, $providersPermitted);
    }
    // Else allow
    return $allowed;
  }

  /**
   * @param string $entityType
   * @return string
   */
  private static function normalizeEntityType($entityType) {
    // Change Individual/Organization/Household to 'Contact'
    if (in_array($entityType, CRM_Contact_BAO_ContactType::basicTypes(TRUE), TRUE)) {
      return 'Contact';
    }
    return $entityType;
  }

  /**
   * Gets the list of available providers to civi's recent items stack
   *
   * @return array
   */
  public static function getProviders() {
    return OptionValue::get(FALSE)
      ->addWhere('option_group_id:name', '=', 'recent_items_providers')
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight', 'ASC')
      ->execute()
      ->column('label', 'value');
  }

}
