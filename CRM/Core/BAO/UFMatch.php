<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * The basic class that interfaces with the external user framework
 */
class CRM_Core_BAO_UFMatch extends CRM_Core_DAO_UFMatch {

  /*
   * Create UF Match, Note that thsi function is here in it's simplest form @ the moment
   *
   *
   * @param array $params input parameters
   */
  /**
   * @param $params
   *
   * @return CRM_Core_DAO_UFMatch
   */
  static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'UFMatch', CRM_Utils_Array::value('id', $params), $params);
    if(empty($params['domain_id'])) {
      $params['domain_id'] = CRM_Core_Config::domainID();
    }
    $dao = new CRM_Core_DAO_UFMatch();
    $dao->copyValues($params);
    $dao->save();
    CRM_Utils_Hook::post($hook, 'UFMatch', $dao->id, $dao);
    return $dao;
  }


  /**
   * Given a UF user object, make sure there is a contact
   * object for this user. If the user has new values, we need
   * to update the CRM DB with the new values
   *
   * @param Object $user the drupal user object
   * @param boolean $update has the user object been edited
   * @param $uf
   *
   * @param $ctype
   * @param bool $isLogin
   *
   * @return void
   * @access public
   * @static
   */
  static function synchronize(&$user, $update, $uf, $ctype, $isLogin = FALSE) {
    $userSystem = CRM_Core_Config::singleton()->userSystem;
    $session = CRM_Core_Session::singleton();
    if (!is_object($session)) {
      CRM_Core_Error::fatal('wow, session is not an object?');
      return;
    }

    $userSystemID = $userSystem->getBestUFID($user);
    $uniqId = $userSystem->getBestUFUniqueIdentifier($user);

    // if the id of the object is zero (true for anon users in drupal)
    // have we already processed this user, if so early
    // return.
    $userID = $session->get('userID');
    $ufID = $session->get('ufID');

    if (!$update && $ufID == $userSystemID) {
      return;
    }

    //check do we have logged in user.
    $isUserLoggedIn = CRM_Utils_System::isUserLoggedIn();

    // reset the session if we are a different user
    if ($ufID && $ufID != $userSystemID) {
      $session->reset();

      //get logged in user ids, and set to session.
      if ($isUserLoggedIn) {
        $userIds = self::getUFValues();
        $session->set('ufID', CRM_Utils_Array::value('uf_id', $userIds, ''));
        $session->set('userID', CRM_Utils_Array::value('contact_id', $userIds, ''));
        $session->set('ufUniqID', CRM_Utils_Array::value('uf_name', $userIds, ''));
      }
    }

    // return early
    if ($userSystemID == 0) {
      return;
    }

    $ufmatch = self::synchronizeUFMatch($user, $userSystemID, $uniqId, $uf, NULL, $ctype, $isLogin);
    if (!$ufmatch) {
      return;
    }

    //make sure we have session w/ consistent ids.
    $ufID     = $ufmatch->uf_id;
    $userID   = $ufmatch->contact_id;
    $ufUniqID = '';
    if ($isUserLoggedIn) {
      $loggedInUserUfID = CRM_Utils_System::getLoggedInUfID();
      //are we processing logged in user.
      if ($loggedInUserUfID && $loggedInUserUfID != $ufID) {
        $userIds  = self::getUFValues($loggedInUserUfID);
        $ufID     = CRM_Utils_Array::value('uf_id', $userIds, '');
        $userID   = CRM_Utils_Array::value('contact_id', $userIds, '');
        $ufUniqID = CRM_Utils_Array::value('uf_name', $userIds, '');
      }
    }

    //set user ids to session.
    $session->set('ufID', $ufID);
    $session->set('userID', $userID);
    $session->set('ufUniqID', $ufUniqID);

    // add current contact to recently viewed
    if ($ufmatch->contact_id) {
      list($displayName, $contactImage, $contactType, $contactSubtype, $contactImageUrl) =
        CRM_Contact_BAO_Contact::getDisplayAndImage($ufmatch->contact_id, TRUE, TRUE);

      $otherRecent = array(
        'imageUrl' => $contactImageUrl,
        'subtype' => $contactSubtype,
        'editUrl' => CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$ufmatch->contact_id}"),
      );

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
   * Synchronize the object with the UF Match entry. Can be called stand-alone from
   * the drupalUsers script
   *
   * @param Object $user the drupal user object
   * @param string $userKey the id of the user from the uf object
   * @param string $uniqId the OpenID of the user
   * @param string $uf the name of the user framework
   * @param integer $status returns the status if user created or already exits (used for CMS sync)
   *
   * @param null $ctype
   * @param bool $isLogin
   *
   * @return the ufmatch object that was found or created
   * @access public
   * @static
   */
  static function &synchronizeUFMatch(&$user, $userKey, $uniqId, $uf, $status = NULL, $ctype = NULL, $isLogin = FALSE) {
    $config = CRM_Core_Config::singleton();

    if (!CRM_Utils_Rule::email($uniqId)) {
      $retVal = $status ? NULL : FALSE;
      return $retVal;
    }

    $newContact = FALSE;

    // make sure that a contact id exists for this user id
    $ufmatch            = new CRM_Core_DAO_UFMatch();
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    $ufmatch->uf_id     = $userKey;

    if (!$ufmatch->find(TRUE)) {
      $transaction = new CRM_Core_Transaction();

      $dao = NULL;
      if (!empty($_POST) && !$isLogin) {
        $params = $_POST;
        $params['email'] = $uniqId;

        $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
        $dedupeParams['check_permission'] = FALSE;
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

        if (!empty($ids) &&
          CRM_Core_BAO_Setting::getItem(
            CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
            'uniq_email_per_site'
          )
        ) {
          // restrict dupeIds to ones that belong to current domain/site.
          $siteContacts = CRM_Core_BAO_Domain::getContactList();
          foreach ($ids as $index => $dupeId) {
            if (!in_array($dupeId, $siteContacts)) {
              unset($ids[$index]);
            }
          }
          // re-index the array
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
        // ensure there does not exists a contact_id / uf_id pair
        // in the DB. This might be due to multiple emails per contact
        // CRM-9091
        $sql = "
SELECT id
FROM   civicrm_uf_match
WHERE  contact_id = %1
AND    domain_id = %2
";
        $params = array(
          1 => array($dao->contact_id, 'Integer'),
          2 => array(CRM_Core_Config::domainID(), 'Integer'),
        );
        $conflict = CRM_Core_DAO::singleValueQuery($sql, $params);

        if (!$conflict) {
          $found               = TRUE;
          $ufmatch->contact_id = $dao->contact_id;
          $ufmatch->uf_name    = $uniqId;
        }
      }

      if (!$found) {
        if ($config->userSystem->is_drupal) {
          $mail = 'mail';
        }
        elseif ($uf == 'WordPress') {
          $mail = 'user_email';
        }
        else {
          $mail = 'email';
        }

        if (is_object($user)) {
          $params = array('email-Primary' => $user->$mail);
        }

        if ($ctype == 'Organization') {
          $params['organization_name'] = $uniqId;
        }
        elseif ($ctype == 'Household') {
          $params['household_name'] = $uniqId;
        }

        if (!$ctype) {
          $ctype = "Individual";
        }
        $params['contact_type'] = $ctype;

        // extract first / middle / last name
        // for joomla
        if ($uf == 'Joomla' && $user->name) {
          CRM_Utils_String::extractName($user->name, $params);
        }

        if ($uf == 'WordPress') {
          if ($user->first_name) {
            $params['first_name'] = $user->first_name;
          }

          if ($user->last_name) {
            $params['last_name'] = $user->last_name;
          }
        }

        $contactId           = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray);
        $ufmatch->contact_id = $contactId;
        $ufmatch->uf_name    = $uniqId;
      }

      // check that there are not two CMS IDs matching the same CiviCRM contact - this happens when a civicrm
      // user has two e-mails and there is a cms match for each of them
      // the gets rid of the nasty fata error but still reports the error
      $sql = "
SELECT uf_id
FROM   civicrm_uf_match
WHERE  ( contact_id = %1
OR     uf_name      = %2
OR     uf_id        = %3 )
AND    domain_id    = %4
";
      $params = array(
        1 => array($ufmatch->contact_id, 'Integer'),
        2 => array($ufmatch->uf_name, 'String'),
        3 => array($ufmatch->uf_id, 'Integer'),
        4 => array($ufmatch->domain_id, 'Integer'),
      );

      $conflict = CRM_Core_DAO::singleValueQuery($sql, $params);

      if (!$conflict) {
        $ufmatch = CRM_Core_BAO_UFMatch::create((array) $ufmatch);
        $ufmatch->free();
        $newContact = TRUE;
        $transaction->commit();
      }
      else {
        $msg = ts("Contact ID %1 is a match for %2 user %3 but has already been matched to %4",
          array(
            1 => $ufmatch->contact_id,
            2 => $uf,
            3 => $ufmatch->uf_id,
            4 => $conflict
          )
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
   * update the uf_name in the user object
   *
   * @param int    $contactId id of the contact to update
   *
   * @return void
   * @access public
   * @static
   */
  static function updateUFName($contactId) {
    if (!$contactId) {
      return;
    }
    $config = CRM_Core_Config::singleton();
    $ufName = CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);

    if (!$ufName) {
      return;
    }

    $update = FALSE;

    // 1.do check for contact Id.
    $ufmatch             = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id  = CRM_Core_Config::domainID();
    if (!$ufmatch->find(TRUE)) {
      return;
    }
    if ($ufmatch->uf_name != $ufName) {
      $update = TRUE;
    }

    // CRM-6928
    // 2.do check for duplicate ufName.
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

    // save the updated ufmatch object
    $ufmatch->uf_name = $ufName;
    $ufmatch->save();
    $config->userSystem->updateCMSName($ufmatch->uf_id, $ufName);
  }

  /**
   * Update the email value for the contact and user profile
   *
   * @param  $contactId  Int     Contact ID of the user
   * @param $emailAddress
   *
   * @internal param String $email email to be modified for the user
   *
   * @return void
   * @access public
   * @static
   */
  static function updateContactEmail($contactId, $emailAddress) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    $emailAddress = $strtolower($emailAddress);

    $ufmatch             = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id  = CRM_Core_Config::domainID();
    if ($ufmatch->find(TRUE)) {
      // Save the email in UF Match table
      $ufmatch->uf_name = $emailAddress;
      CRM_Core_BAO_UFMatch::create((array) $ufmatch);

      //check if the primary email for the contact exists
      //$contactDetails[1] - email
      //$contactDetails[3] - email id
      $contactDetails = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);

      if (trim($contactDetails[1])) {
        $emailID = $contactDetails[3];
        //update if record is found
        $query = "UPDATE  civicrm_email
                     SET email = %1
                     WHERE id =  %2";
        $p = array(
          1 => array($emailAddress, 'String'),
          2 => array($emailID, 'Integer'),
        );
        $dao = CRM_Core_DAO::executeQuery($query, $p);
      }
      else {
        //else insert a new email record
        $email             = new CRM_Core_DAO_Email();
        $email->contact_id = $contactId;
        $email->is_primary = 1;
        $email->email      = $emailAddress;
        $email->save();
        $emailID = $email->id;
      }

      CRM_Core_BAO_Log::register($contactId,
        'civicrm_email',
        $emailID
      );
    }
  }

  /**
   * Delete the object records that are associated with this cms user
   *
   * @param  int  $ufID id of the user to delete
   *
   * @return void
   * @access public
   * @static
   */
  static function deleteUser($ufID) {
    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    $ufmatch->delete();
  }

  /**
   * get the contact_id given a uf_id
   *
   * @param int  $ufID  Id of UF for which related contact_id is required
   *
   * @return int    contact_id on success, null otherwise
   * @access public
   * @static
   */
  static function getContactId($ufID) {
    if (!isset($ufID)) {
      return NULL;
    }

    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if ($ufmatch->find(TRUE)) {
      return (int ) $ufmatch->contact_id;
    }
    return NULL;
  }

  /**
   * get the uf_id given a contact_id
   *
   * @param int  $contactID   ID of the contact for which related uf_id is required
   *
   * @return int    uf_id of the given contact_id on success, null otherwise
   * @access public
   * @static
   */
  static function getUFId($contactID) {
    if (!isset($contactID)) {
      return NULL;
    }
    $domain = CRM_Core_BAO_Domain::getDomain();
    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->contact_id = $contactID;
    $ufmatch->domain_id = $domain->id;
    if ($ufmatch->find(TRUE)) {
      return $ufmatch->uf_id;
    }
    return NULL;
  }

  /**
   * @return bool
   */
  static function isEmptyTable() {
    $sql = "SELECT count(id) FROM civicrm_uf_match";
    return CRM_Core_DAO::singleValueQuery($sql) > 0 ? FALSE : TRUE;
  }

  /**
   * get the list of contact_id
   *
   *
   * @return int    contact_id on success, null otherwise
   * @access public
   * @static
   */
  static function getContactIDs() {
    $id = array();
    $dao = new CRM_Core_DAO_UFMatch();
    $dao->find();
    while ($dao->fetch()) {
      $id[] = $dao->contact_id;
    }
    return $id;
  }

  /**
   * see if this user exists, and if so, if they're allowed to login
   *
   *
   * @param $openId
   *
   * @return bool     true if allowed to login, false otherwise
   * @access public
   * @static
   */
  static function getAllowedToLogin($openId) {
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->uf_name = $openId;
    $ufmatch->allowed_to_login = 1;
    if ($ufmatch->find(TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * get the next unused uf_id value, since the standalone UF doesn't
   * have id's (it uses OpenIDs, which go in a different field)
   *
   *
   * @return int     next highest unused value for uf_id
   * @access public
   * @static
   */
  static function getNextUfIdValue() {
    $query = "SELECT MAX(uf_id)+1 AS next_uf_id FROM civicrm_uf_match";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {
      $ufId = $dao->next_uf_id;
    }

    if (!isset($ufId)) {
      $ufId = 1;
    }
    return $ufId;
  }

  /**
   * @param $email
   *
   * @return bool
   */
  static function isDuplicateUser($email) {
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
   * @param int $ufID uf id.
   *
   * return array  $ufValues uf values.
   **
   *
   * @return array
   */
  static function getUFValues($ufID = NULL) {
    if (!$ufID) {
      //get logged in user uf id.
      $ufID = CRM_Utils_System::getLoggedInUfID();
    }
    if (!$ufID) {
      return array();
    }

    static $ufValues;
    if ($ufID && !isset($ufValues[$ufID])) {
      $ufmatch            = new CRM_Core_DAO_UFMatch();
      $ufmatch->uf_id     = $ufID;
      $ufmatch->domain_id = CRM_Core_Config::domainID();
      if ($ufmatch->find(TRUE)) {
        $ufValues[$ufID] = array(
          'uf_id' => $ufmatch->uf_id,
          'uf_name' => $ufmatch->uf_name,
          'contact_id' => $ufmatch->contact_id,
          'domain_id' => $ufmatch->domain_id,
        );
      }
    }
    return $ufValues[$ufID];
  }
}

