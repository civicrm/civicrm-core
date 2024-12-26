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

/**
 * The basic class that interfaces with the external user framework.
 */
class CRM_Core_BAO_UFMatch extends CRM_Core_DAO_UFMatch {

  /**
   * Create UF Match, Note that this function is here in it's simplest form @ the moment
   *
   * @param $params
   *
   * @return \CRM_Core_DAO_UFMatch
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'UFMatch', $params['id'] ?? NULL, $params);
    if (empty($params['domain_id'])) {
      $params['domain_id'] = CRM_Core_Config::domainID();
    }
    $dao = new CRM_Core_DAO_UFMatch();
    $dao->copyValues($params);
    // Fixme: this function cannot update records
    if (!$dao->find(TRUE)) {
      $dao->save();
      Civi::$statics[__CLASS__][$params['domain_id']][(int) $dao->contact_id] = (int) $dao->uf_id;
      CRM_Utils_Hook::post($hook, 'UFMatch', $dao->id, $dao);
    }
    return $dao;
  }

  /**
   * Given a UF user object, make sure there is a contact
   * object for this user. If the user has new values, we need
   * to update the CRM DB with the new values.
   *
   * @param Object $user
   *   The user object.
   * @param bool $update
   *   Has the user object been edited.
   * @param $uf
   *
   * @param $ctype
   * @param bool $isLogin
   *
   * @throws CRM_Core_Exception
   */
  public static function synchronize(&$user, $update, $uf, $ctype, $isLogin = FALSE) {
    $userSystem = CRM_Core_Config::singleton()->userSystem;
    $session = CRM_Core_Session::singleton();
    if (!is_object($session)) {
      throw new CRM_Core_Exception('wow, session is not an object?');
      return;
    }

    $userSystemID = $userSystem->getBestUFID($user);
    $uniqId = $userSystem->getBestUFUniqueIdentifier($user);

    // If the id of the object is zero (true for anon users in Drupal),
    // have we already processed this user? If so return early.
    $userID = $session->get('userID');
    $ufID = $session->get('ufID');

    if (!$update && $ufID == $userSystemID) {
      return;
    }

    // Check do we have logged in user.
    $isUserLoggedIn = CRM_Utils_System::isUserLoggedIn();

    // Reset the session if we are a different user.
    if ($ufID && $ufID != $userSystemID) {
      $session->reset();

      // Get logged in user ids, and set to session.
      if ($isUserLoggedIn) {
        $userIds = self::getUFValues();
        $session->set('ufID', CRM_Utils_Array::value('uf_id', $userIds, ''));
        $session->set('userID', CRM_Utils_Array::value('contact_id', $userIds, ''));
      }
    }

    // Return early.
    if ($userSystemID == 0) {
      return;
    }

    $ufmatch = self::synchronizeUFMatch($user, $userSystemID, $uniqId, $uf, NULL, $ctype, $isLogin);
    if (!$ufmatch) {
      return;
    }

    // Make sure we have session w/ consistent ids.
    $ufID = $ufmatch->uf_id;
    $userID = $ufmatch->contact_id;
    if ($isUserLoggedIn) {
      $loggedInUserUfID = CRM_Utils_System::getLoggedInUfID();
      // Are we processing logged in user.
      if ($loggedInUserUfID && $loggedInUserUfID != $ufID) {
        $userIds = self::getUFValues($loggedInUserUfID);
        $ufID = $userIds['uf_id'] ?? '';
        $userID = $userIds['contact_id'] ?? '';
      }
    }

    // Set user ids to session.
    $session->set('ufID', $ufID);
    $session->set('userID', $userID);

    // Add current contact to recently viewed.
    if ($ufmatch->contact_id) {
      [$displayName, $contactImage, $contactType, $contactSubtype, $contactImageUrl]
        = CRM_Contact_BAO_Contact::getDisplayAndImage($ufmatch->contact_id, TRUE, TRUE);

      $otherRecent = [
        'imageUrl' => $contactImageUrl,
        'subtype' => $contactSubtype,
        'editUrl' => CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$ufmatch->contact_id}"),
      ];

      CRM_Utils_Recent::add($displayName,
        CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$ufmatch->contact_id}"),
        $ufmatch->contact_id,
        $contactType,
        $ufmatch->contact_id,
        $displayName,
        $otherRecent
      );
    }
  }

  /**
   * Synchronize the object with the UF Match entry.
   *
   * @param Object $user
   *   The user object.
   * @param string $userKey
   *   The id of the user from the uf object.
   * @param string $uniqId
   *   The OpenID of the user.
   * @param string $uf
   *   The name of the user framework.
   * @param int $status
   *   Returns the status if user created or already exits (used for CMS sync).
   * @param string $ctype
   *   contact type
   * @param bool $isLogin
   *
   * @return CRM_Core_DAO_UFMatch|bool
   */
  public static function &synchronizeUFMatch(&$user, $userKey, $uniqId, $uf, $status = NULL, $ctype = NULL, $isLogin = FALSE) {
    $config = CRM_Core_Config::singleton();
    $newContact = FALSE;

    // Make sure that a contact id exists for this user id.
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    $ufmatch->uf_id = $userKey;

    if (!$ufmatch->find(TRUE)) {
      $transaction = new CRM_Core_Transaction();

      $dao = NULL;
      if (!empty($_POST) && !$isLogin) {
        $dedupeParameters = $_POST;
        $dedupeParameters['email'] = $uniqId;

        $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($dedupeParameters, 'Individual', 'Unsupervised', [], FALSE);

        if (!empty($ids) && Civi::settings()->get('uniq_email_per_site')) {
          // Restrict dupeIds to ones that belong to current domain/site.
          $siteContacts = CRM_Core_BAO_Domain::getContactList();
          foreach ($ids as $index => $dupeId) {
            if (!in_array($dupeId, $siteContacts)) {
              unset($ids[$index]);
            }
          }
          // Re-index the array.
          $ids = array_values($ids);
        }
        if (!empty($ids)) {
          $dao = new CRM_Core_DAO();
          $dao->contact_id = $ids[0];
        }
      }
      else {
        $dao = CRM_Contact_BAO_Contact::matchContactOnEmail($uniqId, $ctype);
      }

      $found = FALSE;
      if ($dao) {
        // Ensure there does not exists a contact_id / uf_id pair in the DB.
        // This might be due to multiple emails per contact CRM-9091.
        $sql = '
SELECT id
FROM   civicrm_uf_match
WHERE  contact_id = %1
AND    domain_id = %2
';

        $conflict = CRM_Core_DAO::singleValueQuery($sql, [
          1 => [$dao->contact_id, 'Integer'],
          2 => [CRM_Core_Config::domainID(), 'Integer'],
        ]);

        if (!$conflict) {
          $found = TRUE;
          $ufmatch->contact_id = $dao->contact_id;
          $ufmatch->uf_name = $uniqId;
        }
      }

      if (!$found) {
        $contactParameters = $config->userSystem->getContactDetailsFromUser([
          'user' => $user,
          'uniqId' => $uniqId,
        ]);
        // dev/core#1858 Ensure that if we have a contactID parameter
        // set in the Create user Record contact task form that this contactID
        // value is passed through as the contact_id to the contact create.
        // This is necessary because for Drupal 8 synchronizeUFMatch gets
        // invoked before the civicrm_uf_match record is added whereas in D7
        // it isn't called until later.
        // Note this is taken from our dedupeParameters from earlier.
        if (empty($contactParameters['contact_id']) && !empty($dedupeParameters['contactID'])) {
          $contactParameters['contact_id'] = $dedupeParameters['contactID'];
        }

        if ($ctype === 'Organization') {
          $contactParameters['organization_name'] = $uniqId;
        }
        elseif ($ctype === 'Household') {
          $contactParameters['household_name'] = $uniqId;
        }

        $contactParameters['contact_type'] = $ctype ?? 'Individual';

        $contactID = civicrm_api3('Contact', 'create', $contactParameters)['id'];
        $ufmatch->contact_id = $contactID;
        $ufmatch->uf_name = $uniqId;
      }

      // Check that there are not two CMS IDs matching the same CiviCRM contact.
      // This happens when a CiviCRM user has two e-mails and there is a cms
      // match for each of them the gets rid of the nasty fata error but still
      // reports the error.
      $sql = "
SELECT uf_id
FROM   civicrm_uf_match
WHERE  ( contact_id = %1
OR     uf_name      = %2
OR     uf_id        = %3 )
AND    domain_id    = %4
";

      $conflict = CRM_Core_DAO::singleValueQuery($sql, [
        1 => [$ufmatch->contact_id, 'Integer'],
        2 => [$ufmatch->uf_name, 'String'],
        3 => [$ufmatch->uf_id, 'Integer'],
        4 => [$ufmatch->domain_id, 'Integer'],
      ]);

      if (!$conflict) {
        $ufmatch = CRM_Core_BAO_UFMatch::create((array) $ufmatch);
        $newContact = TRUE;
        $transaction->commit();
      }
      else {
        $msg = ts("Contact ID %1 is a match for %2 user %3 but has already been matched to %4",
          [
            1 => $ufmatch->contact_id,
            2 => $uf,
            3 => $ufmatch->uf_id,
            4 => $conflict,
          ]
        );
        unset($conflict);
      }
    }

    if ($status) {
      return $newContact;
    }
    else {
      return $ufmatch;
    }
  }

  /**
   * Update the uf_name in the user object.
   *
   * @param int $contactId
   *   Id of the contact to update.
   */
  public static function updateUFName($contactId) {
    if (!Civi::settings()->get('syncCMSEmail') || !$contactId) {
      return;
    }

    // 1. Do check for contact Id.
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if (!$ufmatch->find(TRUE)) {
      return;
    }

    $config = CRM_Core_Config::singleton();
    $ufName = CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);

    if (!$ufName) {
      return;
    }

    $update = FALSE;

    if ($ufmatch->uf_name != $ufName) {
      $update = TRUE;
    }

    // CRM-6928
    // 2. Do check for duplicate ufName.
    $ufDupeName = new CRM_Core_DAO_UFMatch();
    $ufDupeName->uf_name = $ufName;
    $ufDupeName->domain_id = CRM_Core_Config::domainID();
    if ($ufDupeName->find(TRUE) &&
      $ufDupeName->contact_id != $contactId
    ) {
      $update = FALSE;
    }

    if (!$update) {
      return;
    }

    // Save the updated ufmatch object.
    $ufmatch->uf_name = $ufName;
    $ufmatch->save();
    $config->userSystem->updateCMSName($ufmatch->uf_id, $ufName);
  }

  /**
   * Update the email value for the contact and user profile.
   *
   * @param int $contactId
   *   Contact ID of the user.
   * @param string $emailAddress
   *   Email to be modified for the user.
   */
  public static function updateContactEmail($contactId, $emailAddress) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $emailAddress = $strtolower($emailAddress);

    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if ($ufmatch->find(TRUE)) {
      // Save the email in UF Match table.
      $ufmatch->uf_name = $emailAddress;
      CRM_Core_BAO_UFMatch::create((array) $ufmatch);

      // If CMS integration is disabled skip Civi email update if CMS user email
      // is changed.
      if (Civi::settings()->get('syncCMSEmail') == FALSE) {
        return;
      }

      // Check if the primary email for the contact exists.
      // $contactDetails[1] - email
      // $contactDetails[3] - email id
      $contactDetails = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);

      if (trim($contactDetails[1])) {
        // Update if record is found but different.
        $emailID = $contactDetails[3];
        if (trim($contactDetails[1]) != $emailAddress) {
          civicrm_api3('Email', 'create', [
            'id' => $emailID,
            'email' => $emailAddress,
          ]);
        }
      }
      else {
        // Else insert a new email record.
        $result = civicrm_api3('Email', 'create', [
          'contact_id' => $contactId,
          'email' => $emailAddress,
          'is_primary' => 1,
        ]);
        $emailID = $result['id'];
      }

      CRM_Core_BAO_Log::register($contactId,
        'civicrm_email',
        $emailID
      );
    }
  }

  /**
   * Delete the object records that are associated with this cms user.
   *
   * @param int $ufID
   *   Id of the user to delete.
   */
  public static function deleteUser($ufID) {
    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = $domainId = CRM_Core_Config::domainID();
    $ufmatch->delete();

    // Flush cache.
    Civi::$statics[__CLASS__][$domainId] = [];
  }

  /**
   * Get the contact_id given a uf_id.
   *
   * @param int $ufID
   *   Id of UF for which related contact_id is required.
   *
   * @return int|null
   *   contact_id on success, null otherwise.
   */
  public static function getContactId($ufID) {
    if (!$ufID) {
      return NULL;
    }
    $domainId = CRM_Core_Config::domainID();

    if (!isset(Civi::$statics[__CLASS__][$domainId])) {
      Civi::$statics[__CLASS__][$domainId] = [];
    }
    $contactId = array_search($ufID, Civi::$statics[__CLASS__][$domainId]);
    if ($contactId) {
      return $contactId;
    }
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = $domainId;
    if ($ufmatch->find(TRUE)) {
      $contactId = (int) $ufmatch->contact_id;
      Civi::$statics[__CLASS__][$domainId][$contactId] = (int) $ufID;
      return $contactId;
    }
    return NULL;
  }

  /**
   * Get the uf_id given a contact_id.
   *
   * @param int $contactID
   *   ID of the contact for which related uf_id is required.
   *
   * @return int|null
   *   uf_id of the given contact_id on success, null otherwise.
   */
  public static function getUFId($contactID) {
    if (!$contactID) {
      return NULL;
    }
    $domainId = CRM_Core_Config::domainID();
    $contactID = (int) $contactID;

    if (empty(Civi::$statics[__CLASS__][$domainId]) || !array_key_exists($contactID, Civi::$statics[__CLASS__][$domainId])) {
      Civi::$statics[__CLASS__][$domainId][$contactID] = NULL;
      $ufmatch = new CRM_Core_DAO_UFMatch();
      $ufmatch->contact_id = $contactID;
      $ufmatch->domain_id = $domainId;
      if ($ufmatch->find(TRUE)) {
        Civi::$statics[__CLASS__][$domainId][$contactID] = (int) $ufmatch->uf_id;
      }
    }
    return Civi::$statics[__CLASS__][$domainId][$contactID];
  }

  /**
   * @deprecated
   * @return bool
   */
  public static function isEmptyTable() {
    CRM_Core_Error::deprecatedFunctionWarning('unused function to be removed');
    $sql = "SELECT count(id) FROM civicrm_uf_match";
    return CRM_Core_DAO::singleValueQuery($sql) > 0 ? FALSE : TRUE;
  }

  /**
   * Get the list of contact_id.
   *
   * @deprecated
   * @return int
   *   contact_id on success, null otherwise.
   */
  public static function getContactIDs() {
    CRM_Core_Error::deprecatedFunctionWarning('unused function to be removed');
    $id = [];
    $dao = new CRM_Core_DAO_UFMatch();
    $dao->find();
    while ($dao->fetch()) {
      $id[] = $dao->contact_id;
    }
    return $id;
  }

  /**
   * See if this user exists, and if so, if they're allowed to login
   *
   * @deprecated
   * @param int $openId
   *
   * @return bool
   *   true if allowed to login, false otherwise
   */
  public static function getAllowedToLogin($openId) {
    CRM_Core_Error::deprecatedFunctionWarning('unused function to be removed');
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->uf_name = $openId;
    $ufmatch->allowed_to_login = 1;
    if ($ufmatch->find(TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the next unused uf_id value
   *
   * @deprecated
   * @return int
   *   Next highest unused value for uf_id.
   */
  public static function getNextUfIdValue() {
    CRM_Core_Error::deprecatedFunctionWarning('unused function to be removed');
    $query = "SELECT MAX(uf_id)+1 AS next_uf_id FROM civicrm_uf_match";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {
      $ufID = $dao->next_uf_id;
    }

    if (!isset($ufID)) {
      $ufID = 1;
    }
    return $ufID;
  }

  /**
   * Is duplicate user
   *
   * @param string $email
   * @deprecated
   * @return bool
   */
  public static function isDuplicateUser($email) {
    CRM_Core_Error::deprecatedFunctionWarning('unused function to be removed');
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!empty($email) && isset($contactID)) {
      $dao = new CRM_Core_DAO_UFMatch();
      $dao->uf_name = $email;
      if ($dao->find(TRUE) && $contactID != $dao->contact_id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get uf match values for given uf id or logged in user.
   *
   * @param int $ufID
   *   Uf id.
   *
   * @return array
   *   uf values.
   */
  public static function getUFValues($ufID = NULL) {
    if (!$ufID) {
      //get logged in user uf id.
      $ufID = CRM_Utils_System::getLoggedInUfID();
    }
    if (!$ufID) {
      return [];
    }

    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__][$ufID])) {
      $ufmatch = new CRM_Core_DAO_UFMatch();
      $ufmatch->uf_id = $ufID;
      $ufmatch->domain_id = CRM_Core_Config::domainID();
      if ($ufmatch->find(TRUE)) {
        Civi::$statics[__CLASS__][__FUNCTION__][$ufID] = [
          'uf_id' => $ufmatch->uf_id,
          'uf_name' => $ufmatch->uf_name,
          'contact_id' => $ufmatch->contact_id,
          'domain_id' => $ufmatch->domain_id,
        ];
      }
    }
    return Civi::$statics[__CLASS__][__FUNCTION__][$ufID] ?? NULL;
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    // Prevent default behavior of joining ACLs onto the contact_id field.
    $clauses = [];
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

  /**
   * This checks and adds a unique index on (uf_id,domain_id)
   *
   * @return bool
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function tryToAddUniqueIndexOnUfId(): bool {
    if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_uf_match', 'UI_uf_match_uf_id_domain_id')) {
      // Run a query to check if we have duplicates
      $query = 'SELECT COUNT(*) FROM civicrm_uf_match
GROUP BY uf_id,domain_id
HAVING COUNT(*) > 1';
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        // Tell the user they need to fix it manually
        \Civi::log()->error('You have multiple records with the same uf_id in civicrm_uf_match. You need to manually fix this in the database so that uf_id is unique.');
        return FALSE;
      }
      else {
        // Add the unique index
        CRM_Core_DAO::executeQuery("
        ALTER TABLE civicrm_uf_match ADD UNIQUE INDEX UI_uf_match_uf_id_domain_id (uf_id,domain_id);
      ");
      }
    }
    return TRUE;
  }

}
