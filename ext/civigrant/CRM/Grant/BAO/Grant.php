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

use CRM_Grant_ExtensionUtil as E;

/**
 * Class CRM_Grant_BAO_Grant
 */
class CRM_Grant_BAO_Grant extends CRM_Grant_DAO_Grant implements \Civi\Core\HookInterface {

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve(array $params, array &$defaults = []) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   */
  public static function create($params) {
    return self::add($params);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if ($event->action === 'create') {
      // set currency for CRM-1496
      if (empty($event->params['currency'])) {
        $event->params['currency'] = Civi::settings()->get('defaultCurrency');
      }
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $e
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $e): void {
    /** @var CRM_Grant_DAO_Grant $grant */
    $grant = $e->object;
    $params = $e->params;
    if (in_array($e->action, ['create', 'edit'])) {
      $grant->find(TRUE);
      $cid = CRM_Core_Session::getLoggedInContactID() ?: $grant->contact_id;

      // Log the information on successful add/edit of Grant
      $logParams = [
        'entity_table' => 'civicrm_grant',
        'entity_id' => $grant->id,
        'modified_id' => $cid,
        'modified_date' => date('Ymd'),
      ];
      CRM_Core_BAO_Log::add($logParams);

      // Add to recent items list
      if (empty($params['skipRecentView'])) {
        $grantTypes = self::buildOptions('grant_type_id');
        $title = CRM_Contact_BAO_Contact::displayName($grant->contact_id) . ' - ' . E::ts('Grant: %1', [1 => $grantTypes[$grant->grant_type_id]]);
        civicrm_api4('RecentItem', 'create', [
          'checkPermissions' => FALSE,
          'values' => [
            'entity_type' => 'Grant',
            'entity_id' => $grant->id,
            'title' => $title,
          ],
        ]);
      }
    }
  }

  /**
   * @deprecated
   */
  public static function deleteContact($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    $grant = new CRM_Grant_DAO_Grant();
    $grant->contact_id = $id;
    $grant->delete();
    return FALSE;
  }

  /**
   * @deprecated
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    self::deleteRecord(['id' => $id]);
    return FALSE;
  }

  /**
   * Combine all the exportable fields from the lower levels object.
   *
   * @return array
   *   array of exportable Fields
   */
  public static function &exportableFields() {
    $fields = CRM_Grant_DAO_Grant::export();
    $grantNote = [
      'grant_note' => [
        'title' => E::ts('Grant Note'),
        'name' => 'grant_note',
        'data_type' => CRM_Utils_Type::T_TEXT,
      ],
    ];
    $fields = array_merge($fields, $grantNote,
      CRM_Core_BAO_CustomField::getFieldsForImport('Grant')
    );

    return $fields;
  }

  /**
   * Get grant record count for a Contact.
   *
   * @param int $contactID
   *
   * @return int
   *   count of grant records
   */
  public static function getContactGrantCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_grant WHERE civicrm_grant.contact_id = {$contactID} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }

}
