<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 *
 */

/**
 * This class contains all contact related functions that are called using AJAX (jQuery)
 */
class CRM_Contact_Page_AJAX {
  /**
   * When a user chooses a username, CHECK_USERNAME_TTL
   * is the time window in which they can check usernames
   * (without reloading the overall form).
   */
  const CHECK_USERNAME_TTL = 10800; // 3hr; 3*60*60

  const AUTOCOMPLETE_TTL = 21600; // 6hr; 6*60*60

  /**
   * Ajax callback for custom fields of type ContactReference
   *
   * Todo: Migrate contact reference fields to use EntityRef
   */
  public static function contactReference() {
    $name = CRM_Utils_Array::value('term', $_GET);
    $name = CRM_Utils_Type::escape($name, 'String');
    $cfID = CRM_Utils_Type::escape($_GET['id'], 'Positive');

    // check that this is a valid, active custom field of Contact Reference type
    $params = array('id' => $cfID);
    $returnProperties = array('filter', 'data_type', 'is_active');
    $cf = array();
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $cf, $returnProperties);
    if (!$cf['id'] || !$cf['is_active'] || $cf['data_type'] != 'ContactReference') {
      CRM_Utils_System::civiExit('error');
    }

    if (!empty($cf['filter'])) {
      $filterParams = array();
      parse_str($cf['filter'], $filterParams);

      $action = CRM_Utils_Array::value('action', $filterParams);

      if (!empty($action) &&
        !in_array($action, array('get', 'lookup'))
      ) {
        CRM_Utils_System::civiExit('error');
      }
    }

    $list = array_keys(CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_reference_options'
    ), '1');

    $return = array_unique(array_merge(array('sort_name'), $list));

    $limit = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'search_autocomplete_count', NULL, 10);

    $params = array('offset' => 0, 'rowCount' => $limit, 'version' => 3);
    foreach ($return as $fld) {
      $params["return.{$fld}"] = 1;
    }

    if (!empty($action)) {
      $excludeGet = array(
        'reset',
        'key',
        'className',
        'fnName',
        'json',
        'reset',
        'context',
        'timestamp',
        'limit',
        'id',
        's',
        'q',
        'action',
      );
      foreach ($_GET as $param => $val) {
        if (empty($val) ||
          in_array($param, $excludeGet) ||
          strpos($param, 'return.') !== FALSE ||
          strpos($param, 'api.') !== FALSE
        ) {
          continue;
        }
        $params[$param] = $val;
      }
    }

    if ($name) {
      $params['sort_name'] = $name;
    }

    $params['sort'] = 'sort_name';

    // tell api to skip permission chk. dgg
    $params['check_permissions'] = 0;

    // add filter variable to params
    if (!empty($filterParams)) {
      $params = array_merge($params, $filterParams);
    }

    $contact = civicrm_api('Contact', 'Get', $params);

    if (!empty($contact['is_error'])) {
      CRM_Utils_System::civiExit('error');
    }

    $contactList = array();
    foreach ($contact['values'] as $value) {
      $view = array();
      foreach ($return as $fld) {
        if (!empty($value[$fld])) {
          $view[] = $value[$fld];
        }
      }
      $contactList[] = array('id' => $value['id'], 'text' => implode(' :: ', $view));
    }

    CRM_Utils_JSON::output($contactList);
  }

  /**
   * Fetch PCP ID by PCP Supporter sort_name, also displays PCP title and associated Contribution Page title
   */
  public static function getPCPList() {
    $name = CRM_Utils_Array::value('term', $_GET);
    $name = CRM_Utils_Type::escape($name, 'String');
    $limit = $max = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'search_autocomplete_count', NULL, 10);

    $where = ' AND pcp.page_id = cp.id AND pcp.contact_id = cc.id';

    $config = CRM_Core_Config::singleton();
    if ($config->includeWildCardInName) {
      $strSearch = "%$name%";
    }
    else {
      $strSearch = "$name%";
    }
    $includeEmailFrom = $includeNickName = '';
    if ($config->includeNickNameInName) {
      $includeNickName = " OR nick_name LIKE '$strSearch'";
    }
    if ($config->includeEmailInName) {
      $includeEmailFrom = "LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 )";
      $whereClause = " WHERE ( email LIKE '$strSearch' OR sort_name LIKE '$strSearch' $includeNickName ) {$where} ";
    }
    else {
      $whereClause = " WHERE ( sort_name LIKE '$strSearch' $includeNickName ) {$where} ";
    }

    $offset = $count = 0;
    if (!empty($_GET['page'])) {
      $page = (int) $_GET['page'];
      $offset = $limit * ($page - 1);
      $limit++;
    }

    $select = 'cc.sort_name, pcp.title, cp.title';
    $query = "
        SELECT id, data
        FROM (
            SELECT pcp.id as id, CONCAT_WS( ' :: ', {$select} ) as data, sort_name
            FROM civicrm_pcp pcp, civicrm_contribution_page cp, civicrm_contact cc
            {$includeEmailFrom}
            {$whereClause} AND pcp.page_type = 'contribute'
            UNION ALL
            SELECT pcp.id as id, CONCAT_WS( ' :: ', {$select} ) as data, sort_name
            FROM civicrm_pcp pcp, civicrm_event cp, civicrm_contact cc
            {$includeEmailFrom}
            {$whereClause} AND pcp.page_type = 'event'
            ) t
        ORDER BY sort_name
        LIMIT $offset, $limit
        ";

    $dao = CRM_Core_DAO::executeQuery($query);
    $output = array('results' => array(), 'more' => FALSE);
    while ($dao->fetch()) {
      if (++$count > $max) {
        $output['more'] = TRUE;
      }
      else {
        $output['results'][] = array('id' => $dao->id, 'text' => $dao->data);
      }
    }
    CRM_Utils_JSON::output($output);
  }

  public static function relationship() {
    $relType = CRM_Utils_Request::retrieve('rel_type', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $relContactID = CRM_Utils_Request::retrieve('rel_contact', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $relationshipID = CRM_Utils_Array::value('rel_id', $_REQUEST); // this used only to determine add or update mode
    $caseID = CRM_Utils_Request::retrieve('case_id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    // check if there are multiple clients for this case, if so then we need create
    // relationship and also activities for each contacts

    // get case client list
    $clientList = CRM_Case_BAO_Case::getCaseClients($caseID);

    $ret = array('is_error' => 0);

    foreach ($clientList as $sourceContactID) {
      $relationParams = array(
        'relationship_type_id' => $relType . '_a_b',
        'contact_check' => array($relContactID => 1),
        'is_active' => 1,
        'case_id' => $caseID,
        'start_date' => date("Ymd"),
      );

      $relationIds = array('contact' => $sourceContactID);

      // check if we are editing/updating existing relationship
      if ($relationshipID && $relationshipID != 'null') {
        // here we need to retrieve appropriate relationshipID based on client id and relationship type id
        $caseRelationships = new CRM_Contact_DAO_Relationship();
        $caseRelationships->case_id = $caseID;
        $caseRelationships->relationship_type_id = $relType;
        $caseRelationships->contact_id_a = $sourceContactID;
        $caseRelationships->find();

        while ($caseRelationships->fetch()) {
          $relationIds['relationship'] = $caseRelationships->id;
          $relationIds['contactTarget'] = $relContactID;
        }
        $caseRelationships->free();
      }

      // create new or update existing relationship
      $return = CRM_Contact_BAO_Relationship::legacyCreateMultiple($relationParams, $relationIds);

      if (!empty($return[4][0])) {
        $relationshipID = $return[4][0];

        //create an activity for case role assignment.CRM-4480
        CRM_Case_BAO_Case::createCaseRoleActivity($caseID, $relationshipID, $relContactID);
      }
      else {
        $ret = array(
          'is_error' => 1,
          'error_message' => ts('The relationship type definition for the case role is not valid for the client and / or staff contact types. You can review and edit relationship types at <a href="%1">Administer >> Option Lists >> Relationship Types</a>.',
            array(1 => CRM_Utils_System::url('civicrm/admin/reltype', 'reset=1'))),
        );
      }
    }

    CRM_Utils_JSON::output($ret);
  }

  /**
   * Fetch the custom field help.
   */
  public static function customField() {
    $fieldId = CRM_Utils_Type::escape($_REQUEST['id'], 'Integer');
    $params = array('id' => $fieldId);
    $returnProperties = array('help_pre', 'help_post');
    $values = array();

    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $values, $returnProperties);
    CRM_Utils_JSON::output($values);
  }

  public static function groupTree() {
    header('Content-Type: application/json');
    $gids = CRM_Utils_Type::escape($_GET['gids'], 'String');
    echo CRM_Contact_BAO_GroupNestingCache::json($gids);
    CRM_Utils_System::civiExit();
  }

  /**
   * Delete custom value.
   */
  public static function deleteCustomValue() {
    header('Content-Type: text/plain');
    $customValueID = CRM_Utils_Type::escape($_REQUEST['valueID'], 'Positive');
    $customGroupID = CRM_Utils_Type::escape($_REQUEST['groupID'], 'Positive');

    CRM_Core_BAO_CustomValue::deleteCustomValue($customValueID, $customGroupID);
    $contactId = CRM_Utils_Array::value('contactId', $_REQUEST);
    if ($contactId) {
      echo CRM_Contact_BAO_Contact::getCountComponent('custom_' . $_REQUEST['groupID'], $contactId);
    }

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();
    CRM_Utils_System::civiExit();
  }

  /**
   *  check the CMS username.
   */
  static public function checkUserName() {
    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), array('for', 'ts'));
    if (
      CRM_Utils_Time::getTimeRaw() > $_REQUEST['ts'] + self::CHECK_USERNAME_TTL
      || $_REQUEST['for'] != 'civicrm/ajax/cmsuser'
      || !$signer->validate($_REQUEST['sig'], $_REQUEST)
    ) {
      $user = array('name' => 'error');
      CRM_Utils_JSON::output($user);
    }

    $config = CRM_Core_Config::singleton();
    $username = trim($_REQUEST['cms_name']);

    $params = array('name' => $username);

    $errors = array();
    $config->userSystem->checkUserNameEmailExists($params, $errors);

    if (isset($errors['cms_name']) || isset($errors['name'])) {
      //user name is not availble
      $user = array('name' => 'no');
      CRM_Utils_JSON::output($user);
    }
    else {
      //user name is available
      $user = array('name' => 'yes');
      CRM_Utils_JSON::output($user);
    }

    // Not reachable: JSON::output() above exits.
    CRM_Utils_System::civiExit();
  }

  /**
   *  Function to get email address of a contact.
   */
  public static function getContactEmail() {
    if (!empty($_REQUEST['contact_id'])) {
      $contactID = CRM_Utils_Type::escape($_REQUEST['contact_id'], 'Positive');
      if (!CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
        return;
      }
      list($displayName,
        $userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);

      header('Content-Type: text/plain');
      if ($userEmail) {
        echo $userEmail;
      }
    }
    else {
      $noemail = CRM_Utils_Array::value('noemail', $_GET);
      $queryString = NULL;
      $name = CRM_Utils_Array::value('name', $_GET);
      if ($name) {
        $name = CRM_Utils_Type::escape($name, 'String');
        if ($noemail) {
          $queryString = " cc.sort_name LIKE '%$name%'";
        }
        else {
          $queryString = " ( cc.sort_name LIKE '%$name%' OR ce.email LIKE '%$name%' ) ";
        }
      }
      else {
        $cid = CRM_Utils_Array::value('cid', $_GET);
        if ($cid) {
          //check cid for interger
          $contIDS = explode(',', $cid);
          foreach ($contIDS as $contID) {
            CRM_Utils_Type::escape($contID, 'Integer');
          }
          $queryString = " cc.id IN ( $cid )";
        }
      }

      if ($queryString) {
        $offset = CRM_Utils_Array::value('offset', $_GET, 0);
        $rowCount = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'search_autocomplete_count', NULL, 10);

        $offset = CRM_Utils_Type::escape($offset, 'Int');

        // add acl clause here
        list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('cc');
        if ($aclWhere) {
          $aclWhere = " AND $aclWhere";
        }
        if ($noemail) {
          $query = "
SELECT sort_name name, cc.id
FROM civicrm_contact cc
     {$aclFrom}
WHERE cc.is_deceased = 0 AND {$queryString}
      {$aclWhere}
LIMIT {$offset}, {$rowCount}
";

          // send query to hook to be modified if needed
          CRM_Utils_Hook::contactListQuery($query,
            $name,
            CRM_Utils_Array::value('context', $_GET),
            CRM_Utils_Array::value('cid', $_GET)
          );

          $dao = CRM_Core_DAO::executeQuery($query);
          while ($dao->fetch()) {
            $result[] = array(
              'id' => $dao->id,
              'text' => $dao->name,
            );
          }
        }
        else {
          $query = "
SELECT sort_name name, ce.email, cc.id
FROM   civicrm_email ce INNER JOIN civicrm_contact cc ON cc.id = ce.contact_id
       {$aclFrom}
WHERE  ce.on_hold = 0 AND cc.is_deceased = 0 AND cc.do_not_email = 0 AND {$queryString}
       {$aclWhere}
LIMIT {$offset}, {$rowCount}
";

          // send query to hook to be modified if needed
          CRM_Utils_Hook::contactListQuery($query,
            $name,
            CRM_Utils_Array::value('context', $_GET),
            CRM_Utils_Array::value('cid', $_GET)
          );

          $dao = CRM_Core_DAO::executeQuery($query);

          while ($dao->fetch()) {
            //working here
            $result[] = array(
              'text' => '"' . $dao->name . '" <' . $dao->email . '>',
              'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->email}" : '"' . $dao->name . '" <' . $dao->email . '>',
            );
          }
        }
        if ($result) {
          CRM_Utils_JSON::output($result);
        }
      }
    }
    CRM_Utils_System::civiExit();
  }

  public static function getContactPhone() {

    $queryString = NULL;
    //check for mobile type
    $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
    $mobileType = CRM_Utils_Array::value('Mobile', $phoneTypes);

    $name = CRM_Utils_Array::value('name', $_GET);
    if ($name) {
      $name = CRM_Utils_Type::escape($name, 'String');
      $queryString = " ( cc.sort_name LIKE '%$name%' OR cp.phone LIKE '%$name%' ) ";
    }
    else {
      $cid = CRM_Utils_Array::value('cid', $_GET);
      if ($cid) {
        //check cid for interger
        $contIDS = explode(',', $cid);
        foreach ($contIDS as $contID) {
          CRM_Utils_Type::escape($contID, 'Integer');
        }
        $queryString = " cc.id IN ( $cid )";
      }
    }

    if ($queryString) {
      $offset = CRM_Utils_Array::value('offset', $_GET, 0);
      $rowCount = CRM_Utils_Array::value('rowcount', $_GET, 20);

      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      // add acl clause here
      list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('cc');
      if ($aclWhere) {
        $aclWhere = " AND $aclWhere";
      }

      $query = "
SELECT sort_name name, cp.phone, cc.id
FROM   civicrm_phone cp INNER JOIN civicrm_contact cc ON cc.id = cp.contact_id
       {$aclFrom}
WHERE  cc.is_deceased = 0 AND cc.do_not_sms = 0 AND cp.phone_type_id = {$mobileType} AND {$queryString}
       {$aclWhere}
LIMIT {$offset}, {$rowCount}
";

      // send query to hook to be modified if needed
      CRM_Utils_Hook::contactListQuery($query,
        $name,
        CRM_Utils_Array::value('context', $_GET),
        CRM_Utils_Array::value('cid', $_GET)
      );

      $dao = CRM_Core_DAO::executeQuery($query);

      while ($dao->fetch()) {
        $result[] = array(
          'text' => '"' . $dao->name . '" (' . $dao->phone . ')',
          'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->phone}" : '"' . $dao->name . '" <' . $dao->phone . '>',
        );
      }
    }

    if ($result) {
      CRM_Utils_JSON::output($result);
    }
    CRM_Utils_System::civiExit();
  }


  public static function buildSubTypes() {
    $parent = CRM_Utils_Array::value('parentId', $_REQUEST);

    switch ($parent) {
      case 1:
        $contactType = 'Individual';
        break;

      case 2:
        $contactType = 'Household';
        break;

      case 4:
        $contactType = 'Organization';
        break;
    }

    $subTypes = CRM_Contact_BAO_ContactType::subTypePairs($contactType, FALSE, NULL);
    asort($subTypes);
    CRM_Utils_JSON::output($subTypes);
  }

  public static function buildDedupeRules() {
    $parent = CRM_Utils_Array::value('parentId', $_REQUEST);

    switch ($parent) {
      case 1:
        $contactType = 'Individual';
        break;

      case 2:
        $contactType = 'Household';
        break;

      case 4:
        $contactType = 'Organization';
        break;
    }

    $dedupeRules = CRM_Dedupe_BAO_RuleGroup::getByType($contactType);

    CRM_Utils_JSON::output($dedupeRules);
  }

  /**
   * Function used for CiviCRM dashboard operations.
   */
  public static function dashboard() {
    $operation = CRM_Utils_Type::escape($_REQUEST['op'], 'String');

    switch ($operation) {
      case 'get_widgets_by_column':
        // This would normally be coming from either the database (this user's settings) or a default/initial dashboard configuration.
        // get contact id of logged in user

        $dashlets = CRM_Core_BAO_Dashboard::getContactDashlets();
        break;

      case 'get_widget':
        $dashletID = CRM_Utils_Type::escape($_GET['id'], 'Positive');

        $dashlets = CRM_Core_BAO_Dashboard::getDashletInfo($dashletID);
        break;

      case 'save_columns':
        CRM_Core_BAO_Dashboard::saveDashletChanges($_REQUEST['columns']);
        CRM_Utils_System::civiExit();
      case 'delete_dashlet':
        $dashletID = CRM_Utils_Type::escape($_REQUEST['dashlet_id'], 'Positive');
        CRM_Core_BAO_Dashboard::deleteDashlet($dashletID);
        CRM_Utils_System::civiExit();
    }

    CRM_Utils_JSON::output($dashlets);
  }

  /**
   * Retrieve signature based on email id.
   */
  public static function getSignature() {
    $emailID = CRM_Utils_Type::escape($_REQUEST['emailID'], 'Positive');
    $query = "SELECT signature_text, signature_html FROM civicrm_email WHERE id = {$emailID}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $signatures = array();
    while ($dao->fetch()) {
      $signatures = array(
        'signature_text' => $dao->signature_text,
        'signature_html' => $dao->signature_html,
      );
    }

    CRM_Utils_JSON::output($signatures);
  }

  /**
   * Process dupes.
   */
  public static function processDupes() {
    $oper = CRM_Utils_Type::escape($_REQUEST['op'], 'String');
    $cid = CRM_Utils_Type::escape($_REQUEST['cid'], 'Positive');
    $oid = CRM_Utils_Type::escape($_REQUEST['oid'], 'Positive');

    if (!$oper || !$cid || !$oid) {
      return;
    }

    $exception = new CRM_Dedupe_DAO_Exception();
    $exception->contact_id1 = $cid;
    $exception->contact_id2 = $oid;
    //make sure contact2 > contact1.
    if ($cid > $oid) {
      $exception->contact_id1 = $oid;
      $exception->contact_id2 = $cid;
    }
    $exception->find(TRUE);
    $status = NULL;
    if ($oper == 'dupe-nondupe') {
      $status = $exception->save();
    }
    if ($oper == 'nondupe-dupe') {
      $status = $exception->delete();
    }

    CRM_Utils_JSON::output(array('status' => ($status) ? $oper : $status));
  }

  public static function getDedupes() {

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = 'sort_name';
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $gid = isset($_REQUEST['gid']) ? CRM_Utils_Type::escape($_REQUEST['gid'], 'Integer') : 0;
    $rgid = isset($_REQUEST['rgid']) ? CRM_Utils_Type::escape($_REQUEST['rgid'], 'Integer') : 0;
    $contactType = '';
    if ($rgid) {
      $contactType = CRM_Core_DAO::getFieldValue('CRM_Dedupe_DAO_RuleGroup', $rgid, 'contact_type');
    }

    $cacheKeyString = "merge {$contactType}_{$rgid}_{$gid}";
    $searchRows = array();
    $selectorElements = array('src', 'dst', 'weight', 'actions');

    $join = "LEFT JOIN civicrm_dedupe_exception de ON ( pn.entity_id1 = de.contact_id1 AND
                                                             pn.entity_id2 = de.contact_id2 )";
    $where = "de.id IS NULL";

    $iFilteredTotal = $iTotal = CRM_Core_BAO_PrevNextCache::getCount($cacheKeyString, $join, $where);
    $mainContacts = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, $join, $where, $offset, $rowCount);

    foreach ($mainContacts as $mainId => $main) {
      $searchRows[$mainId]['src'] = CRM_Utils_System::href($main['srcName'], 'civicrm/contact/view', "reset=1&cid={$main['srcID']}");
      $searchRows[$mainId]['dst'] = CRM_Utils_System::href($main['dstName'], 'civicrm/contact/view', "reset=1&cid={$main['dstID']}");
      $searchRows[$mainId]['weight'] = CRM_Utils_Array::value('weight', $main);

      if (!empty($main['canMerge'])) {
        $mergeParams = "reset=1&cid={$main['srcID']}&oid={$main['dstID']}&action=update&rgid={$rgid}";
        if ($gid) {
          $mergeParams .= "&gid={$gid}";
        }

        $searchRows[$mainId]['actions'] = '<a class="action-item crm-hover-button" href="' . CRM_Utils_System::url('civicrm/contact/merge', $mergeParams) . '">' . ts('merge') . '</a>';
        $searchRows[$mainId]['actions'] .= "<a class='action-item crm-hover-button crm-notDuplicate' href='#' onClick=\"processDupes( {$main['srcID']}, {$main['dstID']}, 'dupe-nondupe', 'dupe-listing'); return false;\">" . ts('not a duplicate') . "</a>";
      }
      else {
        $searchRows[$mainId]['actions'] = '<em>' . ts('Insufficient access rights - cannot merge') . '</em>';
      }
    }

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($searchRows, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);

    CRM_Utils_System::civiExit();
  }

  /**
   * Retrieve a PDF Page Format for the PDF Letter form.
   */
  public function pdfFormat() {
    $formatId = CRM_Utils_Type::escape($_REQUEST['formatId'], 'Integer');

    $pdfFormat = CRM_Core_BAO_PdfFormat::getById($formatId);

    CRM_Utils_JSON::output($pdfFormat);
  }

  /**
   * Retrieve Paper Size dimensions.
   */
  public static function paperSize() {
    $paperSizeName = CRM_Utils_Type::escape($_REQUEST['paperSizeName'], 'String');

    $paperSize = CRM_Core_BAO_PaperSize::getByName($paperSizeName);

    CRM_Utils_JSON::output($paperSize);
  }

  /**
   * Used to store selected contacts across multiple pages in advanced search.
   */
  public static function selectUnselectContacts() {
    $name = CRM_Utils_Array::value('name', $_REQUEST);
    $cacheKey = CRM_Utils_Array::value('qfKey', $_REQUEST);
    $state = CRM_Utils_Array::value('state', $_REQUEST, 'checked');
    $variableType = CRM_Utils_Array::value('variableType', $_REQUEST, 'single');

    $actionToPerform = CRM_Utils_Array::value('action', $_REQUEST, 'select');

    if ($variableType == 'multiple') {
      // action post value only works with multiple type variable
      if ($name) {
        //multiple names like mark_x_1-mark_x_2 where 1,2 are cids
        $elements = explode('-', $name);
        foreach ($elements as $key => $element) {
          $elements[$key] = self::_convertToId($element);
        }
        CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $actionToPerform, $elements);
      }
      else {
        CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $actionToPerform);
      }
    }
    elseif ($variableType == 'single') {
      $cId = self::_convertToId($name);
      $action = ($state == 'checked') ? 'select' : 'unselect';
      CRM_Core_BAO_PrevNextCache::markSelection($cacheKey, $action, $cId);
    }
    $contactIds = CRM_Core_BAO_PrevNextCache::getSelection($cacheKey);
    $countSelectionCids = count($contactIds[$cacheKey]);

    $arrRet = array('getCount' => $countSelectionCids);
    CRM_Utils_JSON::output($arrRet);
  }

  /**
   * @param string $name
   *
   * @return string
   */
  public static function _convertToId($name) {
    if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
      $cId = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
    }
    return $cId;
  }

  public static function getAddressDisplay() {
    $contactId = CRM_Utils_Array::value('contact_id', $_REQUEST);
    if (!$contactId) {
      $addressVal["error_message"] = "no contact id found";
    }
    else {
      $entityBlock = array(
        'contact_id' => $contactId,
        'entity_id' => $contactId,
      );
      $addressVal = CRM_Core_BAO_Address::getValues($entityBlock);
    }

    CRM_Utils_JSON::output($addressVal);
  }

  /**
   * Retrieve contact relationships.
   */
  public static function getContactRelationships() {
    $contactID = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $context = CRM_Utils_Type::escape($_GET['context'], 'String');
    $relationship_type_id = CRM_Utils_Type::escape(CRM_Utils_Array::value('relationship_type_id', $_GET), 'Integer',
      FALSE);

    if (!CRM_Contact_BAO_Contact_Permission::allow($contactID)) {
      return CRM_Utils_System::permissionDenied();
    }

    $sortMapper = array(
      0 => 'relation',
      1 => 'sort_name',
      2 => 'start_date',
      3 => 'end_date',
      4 => 'city',
      5 => 'state',
      6 => 'email',
      7 => 'phone',
      8 => 'links',
      9 => '',
      10 => '',
    );

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['contact_id'] = $contactID;
    $params['context'] = $context;
    if ($relationship_type_id) {
      $params['relationship_type_id'] = $relationship_type_id;
    }

    // get the contact relationships
    $relationships = CRM_Contact_BAO_Relationship::getContactRelationshipSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'relation',
      'name',
      'start_date',
      'end_date',
      'city',
      'state',
      'email',
      'phone',
      'links',
      'id',
      'is_active',
    );

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($relationships, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

}
