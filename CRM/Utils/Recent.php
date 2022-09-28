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
    self::initialize();

    if (!self::isProviderEnabled($type)) {
      return;
    }

    $session = CRM_Core_Session::singleton();

    // make sure item is not already present in list
    for ($i = 0; $i < count(self::$_recent); $i++) {
      if (self::$_recent[$i]['type'] === $type && self::$_recent[$i]['id'] == $id) {
        // delete item from array
        array_splice(self::$_recent, $i, 1);
        break;
      }
    }

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

    if (count(self::$_recent) > self::$_maxItems) {
      array_pop(self::$_recent);
    }

    CRM_Utils_Hook::recent(self::$_recent);

    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Delete an item from the recent stack.
   *
   * @param array $recentItem
   *   Array of the recent Item to be removed.
   */
  public static function del($recentItem) {
    self::initialize();
    $tempRecent = self::$_recent;

    self::$_recent = [];

    // make sure item is not already present in list
    for ($i = 0; $i < count($tempRecent); $i++) {
      if (!($tempRecent[$i]['id'] == $recentItem['id'] &&
        $tempRecent[$i]['type'] == $recentItem['type']
      )
      ) {
        self::$_recent[] = $tempRecent[$i];
      }
    }

    CRM_Utils_Hook::recent(self::$_recent);
    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }

  /**
   * Delete an item from the recent stack.
   *
   * @param string $id
   *   Contact id that had to be removed.
   */
  public static function delContact($id) {
    self::initialize();

    $tempRecent = self::$_recent;

    self::$_recent = [];

    // rebuild recent.
    for ($i = 0; $i < count($tempRecent); $i++) {
      // don't include deleted contact in recent.
      if (CRM_Utils_Array::value('contact_id', $tempRecent[$i]) == $id) {
        continue;
      }
      self::$_recent[] = $tempRecent[$i];
    }

    CRM_Utils_Hook::recent(self::$_recent);
    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
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
