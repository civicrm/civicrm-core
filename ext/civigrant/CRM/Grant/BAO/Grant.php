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
 * Class CRM_Grant_BAO_Grant
 */
class CRM_Grant_BAO_Grant extends CRM_Grant_DAO_Grant {

  /**
   * Get events Summary.
   *
   *
   * @param bool $admin
   *
   * @return array
   *   Array of event summary values
   */
  public static function getGrantSummary($admin = FALSE) {
    $query = "
      SELECT status_id, count(g.id) as status_total
      FROM civicrm_grant g
      JOIN civicrm_contact c
        ON g.contact_id = c.id
      WHERE c.is_deleted = 0
      GROUP BY status_id
    ";

    $dao = CRM_Core_DAO::executeQuery($query);

    $status = [];
    $summary = [];
    $summary['total_grants'] = NULL;
    $status = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'status_id');

    foreach ($status as $id => $name) {
      $stats[$id] = [
        'label' => $name,
        'total' => 0,
      ];
    }

    while ($dao->fetch()) {
      $stats[$dao->status_id] = [
        'label' => $status[$dao->status_id],
        'total' => $dao->status_total,
      ];
      $summary['total_grants'] += $dao->status_total;
    }

    $summary['per_status'] = $stats;
    return $summary;
  }

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
   * Add grant.
   *
   * @param array $params
   * @param array $ids
   *
   * @return object
   */
  public static function add($params, $ids = []) {
    $id = $ids['grant_id'] ?? $params['id'] ?? NULL;
    $hook = $id ? 'edit' : 'create';
    CRM_Utils_Hook::pre($hook, 'Grant', $id, $params);

    $grant = new CRM_Grant_DAO_Grant();
    $grant->id = $id;

    $grant->copyValues($params);

    // set currency for CRM-1496
    if (!isset($grant->currency)) {
      $config = CRM_Core_Config::singleton();
      $grant->currency = $config->defaultCurrency;
    }

    $result = $grant->save();

    $url = CRM_Utils_System::url('civicrm/contact/view/grant',
      "action=view&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
    );

    $grantTypes = CRM_Core_PseudoConstant::get('CRM_Grant_DAO_Grant', 'grant_type_id');
    if (empty($params['skipRecentView'])) {
      if (!isset($grant->contact_id) || !isset($grant->grant_type_id)) {
        $grant->find(TRUE);
      }
      $title = CRM_Contact_BAO_Contact::displayName($grant->contact_id) . ' - ' . ts('Grant') . ': ' . $grantTypes[$grant->grant_type_id];

      $recentOther = [];
      if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::UPDATE)) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
          "action=update&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
        );
      }
      if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::DELETE)) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
          "action=delete&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
        );
      }

      // add the recently created Grant
      CRM_Utils_Recent::add($title,
        $url,
        $grant->id,
        'Grant',
        $grant->contact_id,
        NULL,
        $recentOther
      );
    }

    CRM_Utils_Hook::post($hook, 'Grant', $grant->id, $grant);

    return $result;
  }

  /**
   * Adds a grant.
   *
   * @param array $params
   * @param array $ids
   *
   * @return object
   */
  public static function create($params, $ids = []) {
    $transaction = new CRM_Core_Transaction();

    $grant = self::add($params, $ids);

    if (is_a($grant, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $grant;
    }

    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $id = $params['contact_id'] ?? NULL;
    }
    if (!empty($params['note']) || CRM_Utils_Array::value('id', CRM_Utils_Array::value('note', $ids))) {
      $noteParams = [
        'entity_table' => 'civicrm_grant',
        'note' => $params['note'] = $params['note'] ? $params['note'] : "null",
        'entity_id' => $grant->id,
        'contact_id' => $id,
      ];

      CRM_Core_BAO_Note::add($noteParams, (array) CRM_Utils_Array::value('note', $ids));
    }
    // Log the information on successful add/edit of Grant
    $logParams = [
      'entity_table' => 'civicrm_grant',
      'entity_id' => $grant->id,
      'modified_id' => $id,
      'modified_date' => date('Ymd'),
    ];

    CRM_Core_BAO_Log::add($logParams);

    // add custom field values
    if (!empty($params['custom']) && is_array($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_grant', $grant->id);
    }

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params,
      'civicrm_grant',
      $grant->id
    );

    $transaction->commit();

    return $grant;
  }

  /**
   * Delete the Contact.
   *
   * @param int $id
   *   Contact id.
   *
   * @return bool
   *
   */
  public static function deleteContact($id) {
    $grant = new CRM_Grant_DAO_Grant();
    $grant->contact_id = $id;
    $grant->delete();
    return FALSE;
  }

  /**
   * Delete the grant.
   *
   * @param int $id
   *   Grant id.
   *
   * @return bool|mixed
   */
  public static function del($id) {
    CRM_Utils_Hook::pre('delete', 'Grant', $id);

    $grant = new CRM_Grant_DAO_Grant();
    $grant->id = $id;

    $grant->find();

    if ($grant->fetch()) {
      $results = $grant->delete();
      CRM_Utils_Hook::post('delete', 'Grant', $grant->id, $grant);
      return $results;
    }
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
        'title' => ts('Grant Note'),
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
