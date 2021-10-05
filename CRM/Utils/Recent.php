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

/**
 * Recent items utility class.
 */
class CRM_Utils_Recent {

  /**
   * Store name
   *
   * @var string
   */
  const MAX_ITEMS = 30, STORE_NAME = 'CRM_Utils_Recent';

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
   * Add an item to the recent stack.
   *
   * @param string $title
   *   The title to display.
   * @param string $url
   *   The link for the above title.
   * @param string $id
   *   Object id.
   * @param $type
   * @param int $contactId
   * @param string $contactName
   * @param array $others
   */
  public static function add(
    $title,
    $url,
    $id,
    $type,
    $contactId,
    $contactName,
    $others = []
  ) {
    // Abort if this entity type is not supported
    if (!self::isProviderEnabled($type)) {
      return;
    }

    // Ensure item is not already present in list
    self::removeItems(['id' => $id, 'type' => $type]);

    if (!is_array($others)) {
      $others = [];
    }

    array_unshift(self::$_recent,
      [
        'title' => $title,
        'url' => $url,
        'id' => $id,
        'type' => $type,
        'contact_id' => $contactId,
        'contactName' => $contactName,
        'subtype' => $others['subtype'] ?? NULL,
        'isDeleted' => $others['isDeleted'] ?? FALSE,
        'image_url' => $others['imageUrl'] ?? NULL,
        'edit_url' => $others['editUrl'] ?? NULL,
        'delete_url' => $others['deleteUrl'] ?? NULL,
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
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function on_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'delete' && $event->id && CRM_Core_Session::getLoggedInContactID()) {
      // Is this an entity that might be in the recent items list?
      $providersPermitted = Civi::settings()->get('recentItemsProviders') ?: array_keys(self::getProviders());
      if (in_array($event->entity, $providersPermitted)) {
        self::del(['id' => $event->id, 'type' => $event->entity]);
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
        if (isset($item[$key]) && $item[$key] != $val) {
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

    // Join contact types to providerName 'Contact'
    $contactTypes = CRM_Contact_BAO_ContactType::contactTypes(TRUE);
    if (in_array($providerName, $contactTypes)) {
      $providerName = 'Contact';
    }
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
   * Gets the list of available providers to civi's recent items stack
   *
   * @return array
   */
  public static function getProviders() {
    $providers = [
      'Contact' => ts('Contacts'),
      'Relationship' => ts('Relationships'),
      'Activity' => ts('Activities'),
      'Note' => ts('Notes'),
      'Group' => ts('Groups'),
      'Case' => ts('Cases'),
      'Contribution' => ts('Contributions'),
      'Participant' => ts('Participants'),
      'Grant' => ts('Grants'),
      'Membership' => ts('Memberships'),
      'Pledge' => ts('Pledges'),
      'Event' => ts('Events'),
      'Campaign' => ts('Campaigns'),
    ];

    return $providers;
  }

}
