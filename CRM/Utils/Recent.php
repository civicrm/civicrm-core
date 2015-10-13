<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * Recent items utility class.
 */
class CRM_Utils_Recent {

  /**
   * Max number of items in queue.
   *
   * @var int
   */
  const MAX_ITEMS = 10, STORE_NAME = 'CRM_Utils_Recent';

  /**
   * The list of recently viewed items.
   *
   * @var array
   */
  static private $_recent = NULL;

  /**
   * Initialize this class and set the static variables.
   */
  public static function initialize() {
    if (!self::$_recent) {
      $session = CRM_Core_Session::singleton();
      self::$_recent = $session->get(self::STORE_NAME);
      if (!self::$_recent) {
        self::$_recent = array();
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
    $others = array()
  ) {
    self::initialize();
    $session = CRM_Core_Session::singleton();

    // make sure item is not already present in list
    for ($i = 0; $i < count(self::$_recent); $i++) {
      if (self::$_recent[$i]['url'] == $url) {
        // delete item from array
        array_splice(self::$_recent, $i, 1);
        break;
      }
    }

    if (!is_array($others)) {
      $others = array();
    }

    array_unshift(self::$_recent,
      array(
        'title' => $title,
        'url' => $url,
        'id' => $id,
        'type' => $type,
        'contact_id' => $contactId,
        'contactName' => $contactName,
        'subtype' => CRM_Utils_Array::value('subtype', $others),
        'isDeleted' => CRM_Utils_Array::value('isDeleted', $others, FALSE),
        'image_url' => CRM_Utils_Array::value('imageUrl', $others),
        'edit_url' => CRM_Utils_Array::value('editUrl', $others),
        'delete_url' => CRM_Utils_Array::value('deleteUrl', $others),
      )
    );
    if (count(self::$_recent) > self::MAX_ITEMS) {
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

    self::$_recent = '';

    // make sure item is not already present in list
    for ($i = 0; $i < count($tempRecent); $i++) {
      if (!($tempRecent[$i]['id'] == $recentItem['id'] &&
        $tempRecent[$i]['type'] == $recentItem['type']
      )
      ) {
        self::$_recent[] = $tempRecent[$i];
      }
    }

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

    self::$_recent = '';

    // rebuild recent.
    for ($i = 0; $i < count($tempRecent); $i++) {
      // don't include deleted contact in recent.
      if (CRM_Utils_Array::value('contact_id', $tempRecent[$i]) == $id) {
        continue;
      }
      self::$_recent[] = $tempRecent[$i];
    }

    $session = CRM_Core_Session::singleton();
    $session->set(self::STORE_NAME, self::$_recent);
  }

}
