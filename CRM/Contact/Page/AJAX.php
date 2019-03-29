<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
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
    $params = ['id' => $cfID];
    $returnProperties = ['filter', 'data_type', 'is_active'];
    $cf = [];
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $cf, $returnProperties);
    if (!$cf['id'] || !$cf['is_active'] || $cf['data_type'] != 'ContactReference') {
      CRM_Utils_System::civiExit(1);
    }

    if (!empty($cf['filter'])) {
      $filterParams = [];
      parse_str($cf['filter'], $filterParams);

      $action = CRM_Utils_Array::value('action', $filterParams);
      if (!empty($action) && !in_array($action, ['get', 'lookup'])) {
        CRM_Utils_System::civiExit(1);
      }

      if (!empty($filterParams['group'])) {
        $filterParams['group'] = explode(',', $filterParams['group']);
      }
    }

    $list = array_keys(CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_reference_options'
    ), '1');

    $return = array_unique(array_merge(['sort_name'], $list));

    $limit = Civi::settings()->get('search_autocomplete_count');

    $params = ['offset' => 0, 'rowCount' => $limit, 'version' => 3];
    foreach ($return as $fld) {
      $params["return.{$fld}"] = 1;
    }

    if (!empty($action)) {
      $excludeGet = [
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
      ];
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
      CRM_Utils_System::civiExit(1);
    }

    $contactList = [];
    foreach ($contact['values'] as $value) {
      $view = [];
      foreach ($return as $fld) {
        if (!empty($value[$fld])) {
          $view[] = $value[$fld];
        }
      }
      $contactList[] = ['id' => $value['id'], 'text' => implode(' :: ', $view)];
    }

    if (!empty($_GET['is_unit_test'])) {
      return $contactList;
    }
    CRM_Utils_JSON::output($contactList);
  }

  /**
   * Fetch PCP ID by PCP Supporter sort_name, also displays PCP title and associated Contribution Page title
   */
  public static function getPCPList() {
    $name = CRM_Utils_Array::value('term', $_GET);
    $name = CRM_Utils_Type::escape($name, 'String');
    $limit = $max = Civi::settings()->get('search_autocomplete_count');

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
    if (!empty($_GET['page_num'])) {
      $page = (int) $_GET['page_num'];
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
    $output = ['results' => [], 'more' => FALSE];
    while ($dao->fetch()) {
      if (++$count > $max) {
        $output['more'] = TRUE;
      }
      else {
        $output['results'][] = ['id' => $dao->id, 'text' => $dao->data];
      }
    }
    CRM_Utils_JSON::output($output);
  }

  public static function relationship() {
    $relType = CRM_Utils_Request::retrieve('rel_type', 'String', CRM_Core_DAO::$_nullObject, TRUE);
    $relContactID = CRM_Utils_Request::retrieve('rel_contact', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $originalCid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $relationshipID = CRM_Utils_Request::retrieve('rel_id', 'Positive');
    $caseID = CRM_Utils_Request::retrieve('case_id', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    if (!CRM_Case_BAO_Case::accessCase($caseID)) {
      CRM_Utils_System::permissionDenied();
    }

    $ret = ['is_error' => 0];

    list($relTypeId, $b, $a) = explode('_', $relType);

    if ($relationshipID && $originalCid) {
      CRM_Case_BAO_Case::endCaseRole($caseID, $a, $originalCid, $relTypeId);
    }

    $clientList = CRM_Case_BAO_Case::getCaseClients($caseID);

    // Loop through multiple case clients
    foreach ($clientList as $i => $sourceContactID) {
      try {
        $params = [
          'case_id' => $caseID,
          'relationship_type_id' => $relTypeId,
          "contact_id_$a" => $relContactID,
          "contact_id_$b" => $sourceContactID,
          'sequential' => TRUE,
        ];
        // first check if there is any existing relationship present with same parameters.
        // If yes then update the relationship by setting active and start date to current time
        $relationship = civicrm_api3('Relationship', 'get', $params)['values'];
        $params = array_merge(CRM_Utils_Array::value(0, $relationship, $params), [
          'start_date' => 'now',
          'is_active' => TRUE,
          'end_date' => '',
        ]);
        $result = civicrm_api3('relationship', 'create', $params);
      }
      catch (CiviCRM_API3_Exception $e) {
        $ret['is_error'] = 1;
        $ret['error_message'] = $e->getMessage();
      }
      // Save activity only for the primary (first) client
      if ($i == 0 && empty($result['is_error'])) {
        CRM_Case_BAO_Case::createCaseRoleActivity($caseID, $result['id'], $relContactID, $sourceContactID);
      }
    }
    if (!empty($_REQUEST['is_unit_test'])) {
      return $ret;
    }

    CRM_Utils_JSON::output($ret);
  }

  /**
   * Fetch the custom field help.
   */
  public static function customField() {
    $fieldId = CRM_Utils_Type::escape($_REQUEST['id'], 'Integer');
    $params = ['id' => $fieldId];
    $returnProperties = ['help_pre', 'help_post'];
    $values = [];

    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $values, $returnProperties);
    CRM_Utils_JSON::output($values);
  }

  public static function groupTree() {
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    $gids = CRM_Utils_Type::escape($_GET['gids'], 'String');
    echo CRM_Contact_BAO_GroupNestingCache::json($gids);
    CRM_Utils_System::civiExit();
  }

  /**
   * Delete custom value.
   */
  public static function deleteCustomValue() {
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
    $customValueID = CRM_Utils_Type::escape($_REQUEST['valueID'], 'Positive');
    $customGroupID = CRM_Utils_Type::escape($_REQUEST['groupID'], 'Positive');
    $contactId = CRM_Utils_Request::retrieve('contactId', 'Positive');
    CRM_Core_BAO_CustomValue::deleteCustomValue($customValueID, $customGroupID);
    if ($contactId) {
      echo CRM_Contact_BAO_Contact::getCountComponent('custom_' . $customGroupID, $contactId);
    }

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();
    CRM_Utils_System::civiExit();
  }

  /**
   *  check the CMS username.
   */
  static public function checkUserName() {
    $signer = new CRM_Utils_Signer(CRM_Core_Key::privateKey(), ['for', 'ts']);
    $sig = CRM_Utils_Request::retrieve('sig', 'String');
    $for = CRM_Utils_Request::retrieve('for', 'String');
    if (
      CRM_Utils_Time::getTimeRaw() > $_REQUEST['ts'] + self::CHECK_USERNAME_TTL
      || $for != 'civicrm/ajax/cmsuser'
      || !$signer->validate($sig, $_REQUEST)
    ) {
      $user = ['name' => 'error'];
      CRM_Utils_JSON::output($user);
    }

    $config = CRM_Core_Config::singleton();
    $username = trim(CRM_Utils_Array::value('cms_name', $_REQUEST));

    $params = ['name' => $username];

    $errors = [];
    $config->userSystem->checkUserNameEmailExists($params, $errors);

    if (isset($errors['cms_name']) || isset($errors['name'])) {
      //user name is not available
      $user = ['name' => 'no'];
      CRM_Utils_JSON::output($user);
    }
    else {
      //user name is available
      $user = ['name' => 'yes'];
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

      CRM_Utils_System::setHttpHeader('Content-Type', 'text/plain');
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
          //check cid for integer
          $contIDS = explode(',', $cid);
          foreach ($contIDS as $contID) {
            CRM_Utils_Type::escape($contID, 'Integer');
          }
          $queryString = " cc.id IN ( $cid )";
        }
      }

      if ($queryString) {
        $result = [];
        $offset = CRM_Utils_Array::value('offset', $_GET, 0);
        $rowCount = Civi::settings()->get('search_autocomplete_count');

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
            CRM_Utils_Request::retrieve('context', 'Alphanumeric'),
            CRM_Utils_Request::retrieve('cid', 'Positive')
          );

          $dao = CRM_Core_DAO::executeQuery($query);
          while ($dao->fetch()) {
            $result[] = [
              'id' => $dao->id,
              'text' => $dao->name,
            ];
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
            CRM_Utils_Request::retrieve('context', 'Alphanumeric'),
            CRM_Utils_Request::retrieve('cid', 'Positive')
          );

          $dao = CRM_Core_DAO::executeQuery($query);

          while ($dao->fetch()) {
            //working here
            $result[] = [
              'text' => '"' . $dao->name . '" <' . $dao->email . '>',
              'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->email}" : '"' . $dao->name . '" <' . $dao->email . '>',
            ];
          }
        }
        CRM_Utils_JSON::output($result);
      }
    }
    CRM_Utils_System::civiExit();
  }

  public static function getContactPhone() {

    $queryString = NULL;
    $sqlParmas = [];
    //check for mobile type
    $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
    $mobileType = CRM_Utils_Array::value('Mobile', $phoneTypes);

    $name = CRM_Utils_Request::retrieveValue('name', 'String', NULL, FALSE, 'GET');
    if ($name) {
      $key = (int) count(array_keys($sqlParmas)) + 1;
      $queryString = " ( cc.sort_name LIKE %{$key} OR cp.phone LIKE %{$key} ) ";
      $sqlParams[$key] = ['%' . $name . '%', 'String'];
    }
    else {
      $cid = CRM_Utils_Request::retrieveValue('cid', 'CommaSeparatedIntegers', NULL, FALSE, 'GET');
      if ($cid) {
        $queryString = " cc.id IN ( $cid )";
      }
    }

    if ($queryString) {
      $result = [];
      $offset = (int) CRM_Utils_Request::retrieveValue('offset', 'Integer', 0, FALSE, 'GET');
      $rowCount = (int) CRM_Utils_Request::retrieveValue('rowcount', 'Integer', 20, FALSE, 'GET');

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
        CRM_Utils_Request::retrieve('context', 'Alphanumeric'),
        CRM_Utils_Request::retrieve('cid', 'Positive')
      );

      $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);

      while ($dao->fetch()) {
        $result[] = [
          'text' => '"' . $dao->name . '" (' . $dao->phone . ')',
          'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->phone}" : '"' . $dao->name . '" <' . $dao->phone . '>',
        ];
      }
      CRM_Utils_JSON::output($result);
    }
    CRM_Utils_System::civiExit();
  }


  public static function buildSubTypes() {
    $parent = CRM_Utils_Request::retrieve('parentId', 'Positive');

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
    $parent = CRM_Utils_Request::retrieve('parentId', 'Positive');

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
    switch ($_REQUEST['op']) {
      case 'save_columns':
        CRM_Core_BAO_Dashboard::saveDashletChanges($_REQUEST['columns']);
        break;

      case 'delete_dashlet':
        $dashletID = CRM_Utils_Type::escape($_REQUEST['dashlet_id'], 'Positive');
        CRM_Core_BAO_Dashboard::deleteDashlet($dashletID);
    }

    CRM_Utils_System::civiExit();
  }

  /**
   * Retrieve signature based on email id.
   */
  public static function getSignature() {
    $emailID = CRM_Utils_Type::escape($_REQUEST['emailID'], 'Positive');
    $query = "SELECT signature_text, signature_html FROM civicrm_email WHERE id = {$emailID}";
    $dao = CRM_Core_DAO::executeQuery($query);

    $signatures = [];
    while ($dao->fetch()) {
      $signatures = [
        'signature_text' => $dao->signature_text,
        'signature_html' => $dao->signature_html,
      ];
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

    CRM_Utils_JSON::output(['status' => ($status) ? $oper : $status]);
  }

  /**
   * Retrieve list of duplicate pairs from cache table.
   */
  public static function getDedupes() {
    $offset    = isset($_REQUEST['start']) ? CRM_Utils_Type::escape($_REQUEST['start'], 'Integer') : 0;
    $rowCount  = isset($_REQUEST['length']) ? CRM_Utils_Type::escape($_REQUEST['length'], 'Integer') : 25;

    $gid = CRM_Utils_Request::retrieve('gid', 'Positive');
    $rgid = CRM_Utils_Request::retrieve('rgid', 'Positive');
    $null = NULL;
    $criteria = CRM_Utils_Request::retrieve('criteria', 'Json', $null, FALSE, '{}');
    $selected = CRM_Utils_Request::retrieveValue('selected', 'Boolean');
    if ($rowCount < 0) {
      $rowCount = 0;
    }

    $whereClause = $orderByClause = '';
    $cacheKeyString   = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid, json_decode($criteria, TRUE));

    $searchRows       = [];

    $searchParams = self::getSearchOptionsFromRequest();
    $queryParams = [];

    $join  = '';
    $where = [];

    $isOrQuery = self::isOrQuery();

    $nextParamKey = 3;
    $mappings = [
      'dst' => 'cc1.display_name',
      'src' => 'cc2.display_name',
      'dst_email' => 'ce1.email',
      'src_email' => 'ce2.email',
      'dst_postcode' => 'ca1.postal_code',
      'src_postcode' => 'ca2.postal_code',
      'dst_street' => 'ca1.street',
      'src_street' => 'ca2.street',
    ];

    foreach ($mappings as $key => $dbName) {
      if (!empty($searchParams[$key])) {
        // CRM-18694.
        $wildcard = strstr($key, 'postcode') ? '' : '%';
        $queryParams[$nextParamKey] = [$wildcard . $searchParams[$key] . '%', 'String'];
        $where[] = $dbName . " LIKE %{$nextParamKey} ";
        $nextParamKey++;
      }
    }

    if ($isOrQuery) {
      $whereClause   = ' ( ' . implode(' OR ', $where) . ' ) ';
    }
    else {
      if (!empty($where)) {
        $whereClause  = implode(' AND ', $where);
      }
    }
    $whereClause .= $whereClause ? ' AND de.id IS NULL' : ' de.id IS NULL';

    if ($selected) {
      $whereClause .= ' AND pn.is_selected = 1';
    }
    $join .= CRM_Dedupe_Merger::getJoinOnDedupeTable();

    $select = [
      'cc1.contact_type'     => 'dst_contact_type',
      'cc1.display_name'     => 'dst_display_name',
      'cc1.contact_sub_type' => 'dst_contact_sub_type',
      'cc2.contact_type'     => 'src_contact_type',
      'cc2.display_name'     => 'src_display_name',
      'cc2.contact_sub_type' => 'src_contact_sub_type',
      'ce1.email'            => 'dst_email',
      'ce2.email'            => 'src_email',
      'ca1.postal_code'      => 'dst_postcode',
      'ca2.postal_code'      => 'src_postcode',
      'ca1.street_address'   => 'dst_street',
      'ca2.street_address'   => 'src_street',
    ];

    if ($select) {
      $join .= " INNER JOIN civicrm_contact cc1 ON cc1.id = pn.entity_id1";
      $join .= " INNER JOIN civicrm_contact cc2 ON cc2.id = pn.entity_id2";
      $join .= " LEFT JOIN civicrm_email ce1 ON (ce1.contact_id = pn.entity_id1 AND ce1.is_primary = 1 )";
      $join .= " LEFT JOIN civicrm_email ce2 ON (ce2.contact_id = pn.entity_id2 AND ce2.is_primary = 1 )";
      $join .= " LEFT JOIN civicrm_address ca1 ON (ca1.contact_id = pn.entity_id1 AND ca1.is_primary = 1 )";
      $join .= " LEFT JOIN civicrm_address ca2 ON (ca2.contact_id = pn.entity_id2 AND ca2.is_primary = 1 )";
    }
    $iTotal = CRM_Core_BAO_PrevNextCache::getCount($cacheKeyString, $join, $whereClause, '=', $queryParams);
    if (!empty($_REQUEST['order'])) {
      foreach ($_REQUEST['order'] as $orderInfo) {
        if (!empty($orderInfo['column'])) {
          $orderColumnNumber = $orderInfo['column'];
          $dir = $orderInfo['dir'];
        }
      }
      $columnDetails = CRM_Utils_Array::value($orderColumnNumber, $_REQUEST['columns']);
    }
    if (!empty($columnDetails)) {
      switch ($columnDetails['data']) {
        case 'src':
          $orderByClause = " ORDER BY cc2.display_name {$dir}";
          break;

        case 'src_email':
          $orderByClause = " ORDER BY ce2.email {$dir}";
          break;

        case 'src_street':
          $orderByClause = " ORDER BY ca2.street_address {$dir}";
          break;

        case 'src_postcode':
          $orderByClause = " ORDER BY ca2.postal_code {$dir}";
          break;

        case 'dst':
          $orderByClause = " ORDER BY cc1.display_name {$dir}";
          break;

        case 'dst_email':
          $orderByClause = " ORDER BY ce1.email {$dir}";
          break;

        case 'dst_street':
          $orderByClause = " ORDER BY ca1.street_address {$dir}";
          break;

        case 'dst_postcode':
          $orderByClause = " ORDER BY ca1.postal_code {$dir}";
          break;

        default:
          $orderByClause = " ORDER BY cc1.display_name ASC";
          break;
      }
    }

    $dupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, $join, $whereClause, $offset, $rowCount, $select, $orderByClause, TRUE, $queryParams);
    $iFilteredTotal = CRM_Core_DAO::singleValueQuery("SELECT FOUND_ROWS()");

    $count = 0;
    foreach ($dupePairs as $key => $pairInfo) {
      $pair = $pairInfo['data'];
      $srcContactSubType  = CRM_Utils_Array::value('src_contact_sub_type', $pairInfo);
      $dstContactSubType  = CRM_Utils_Array::value('dst_contact_sub_type', $pairInfo);
      $srcTypeImage = CRM_Contact_BAO_Contact_Utils::getImage($srcContactSubType ?
        $srcContactSubType : $pairInfo['src_contact_type'],
        FALSE,
        $pairInfo['entity_id2']
      );
      $dstTypeImage = CRM_Contact_BAO_Contact_Utils::getImage($dstContactSubType ?
        $dstContactSubType : $pairInfo['dst_contact_type'],
        FALSE,
        $pairInfo['entity_id1']
      );

      $searchRows[$count]['is_selected'] = $pairInfo['is_selected'];
      $searchRows[$count]['is_selected_input'] = "<input type='checkbox' class='crm-dedupe-select' name='pnid_{$pairInfo['prevnext_id']}' value='{$pairInfo['is_selected']}' onclick='toggleDedupeSelect(this)'>";
      $searchRows[$count]['src_image'] = $srcTypeImage;
      $searchRows[$count]['src'] = CRM_Utils_System::href($pair['srcName'], 'civicrm/contact/view', "reset=1&cid={$pairInfo['entity_id2']}");
      $searchRows[$count]['src_email'] = CRM_Utils_Array::value('src_email', $pairInfo);
      $searchRows[$count]['src_street'] = CRM_Utils_Array::value('src_street', $pairInfo);
      $searchRows[$count]['src_postcode'] = CRM_Utils_Array::value('src_postcode', $pairInfo);
      $searchRows[$count]['dst_image'] = $dstTypeImage;
      $searchRows[$count]['dst'] = CRM_Utils_System::href($pair['dstName'], 'civicrm/contact/view', "reset=1&cid={$pairInfo['entity_id1']}");
      $searchRows[$count]['dst_email'] = CRM_Utils_Array::value('dst_email', $pairInfo);
      $searchRows[$count]['dst_street'] = CRM_Utils_Array::value('dst_street', $pairInfo);
      $searchRows[$count]['dst_postcode'] = CRM_Utils_Array::value('dst_postcode', $pairInfo);
      $searchRows[$count]['conflicts'] = str_replace("',", "',<br/>", CRM_Utils_Array::value('conflicts', $pair));
      $searchRows[$count]['weight'] = CRM_Utils_Array::value('weight', $pair);

      if (!empty($pairInfo['data']['canMerge'])) {
        $mergeParams = [
          'reset' => 1,
            'cid' => $pairInfo['entity_id1'],
            'oid' => $pairInfo['entity_id2'],
            'action' => 'update',
            'rgid' => $rgid,
            'criteria' => $criteria,
            'limit' => CRM_Utils_Request::retrieve('limit', 'Integer'),
          ];
        if ($gid) {
          $mergeParams['gid'] = $gid;
        }

        $searchRows[$count]['actions']  = "<a class='crm-dedupe-flip' href='#' data-pnid={$pairInfo['prevnext_id']}>" . ts('flip') . "</a>&nbsp;|&nbsp;";
        $searchRows[$count]['actions'] .= CRM_Utils_System::href(ts('merge'), 'civicrm/contact/merge', $mergeParams);
        $searchRows[$count]['actions'] .= "&nbsp;|&nbsp;<a id='notDuplicate' href='#' onClick=\"processDupes( {$pairInfo['entity_id1']}, {$pairInfo['entity_id2']}, 'dupe-nondupe', 'dupe-listing'); return false;\">" . ts('not a duplicate') . "</a>";
      }
      else {
        $searchRows[$count]['actions'] = '<em>' . ts('Insufficient access rights - cannot merge') . '</em>';
      }
      $count++;
    }

    $dupePairs = [
      'data'            => $searchRows,
      'recordsTotal'    => $iTotal,
      'recordsFiltered' => $iFilteredTotal,
    ];
    if (!empty($_REQUEST['is_unit_test'])) {
      return $dupePairs;
    }
    CRM_Utils_JSON::output($dupePairs);
  }

  /**
   * Get the searchable options from the request.
   *
   * @return array
   */
  public static function getSearchOptionsFromRequest() {
    $searchParams = [];
    $searchData = CRM_Utils_Array::value('search', $_REQUEST);
    $searchData['value'] = CRM_Utils_Type::escape($searchData['value'], 'String');
    $selectorElements = [
      'is_selected',
      'is_selected_input',
      'src_image',
      'src',
      'src_email',
      'src_street',
      'src_postcode',
      'dst_image',
      'dst',
      'dst_email',
      'dst_street',
      'dst_postcode',
      'conflicts',
      'weight',
      'actions',
    ];
    $columns = $_REQUEST['columns'];

    foreach ($columns as $column) {
      if (!empty($column['search']['value']) && in_array($column['data'], $selectorElements)) {
        $searchParams[$column['data']] = CRM_Utils_Type::escape($column['search']['value'], 'String');
      }
      elseif (!empty($searchData['value'])) {
        $searchParams[$column['data']] = $searchData['value'];
      }
    }
    return $searchParams;
  }

  /**
   * Is the query an OR query.
   *
   * If a generic search value is passed in - ie. $_REQUEST['search']['value'] = 'abc'
   * then all fields are searched for this.
   *
   * It is unclear if there is any code that still passes this in or whether is is just legacy. It
   * could cause a server-killing query on a large site so it probably is NOT in use if we haven't
   * had complaints.
   *
   * @return bool
   */
  public static function isOrQuery() {
    $searchData = CRM_Utils_Array::value('search', $_REQUEST);
    return !empty($searchData['value']);
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
   * Swap contacts in a dupe pair i.e main with duplicate contact.
   *
   * @param int $prevNextId
   */
  public static function flipDupePairs($prevNextId = NULL) {
    if (!$prevNextId) {
      // @todo figure out if this is always POST & specify that rather than inexact GET
      $prevNextId = CRM_Utils_Request::retrieve('pnid', 'Integer');
    }

    $onlySelected = FALSE;
    if (is_array($prevNextId) && !CRM_Utils_Array::crmIsEmptyArray($prevNextId)) {
      $onlySelected = TRUE;
    }
    $prevNextId = CRM_Utils_Type::escapeAll((array) $prevNextId, 'Positive');
    CRM_Core_BAO_PrevNextCache::flipPair($prevNextId, $onlySelected);
    CRM_Utils_System::civiExit();
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
        CRM_Utils_Type::escapeAll($elements, 'Integer');
        Civi::service('prevnext')->markSelection($cacheKey, $actionToPerform, $elements);
      }
      else {
        Civi::service('prevnext')->markSelection($cacheKey, $actionToPerform);
      }
    }
    elseif ($variableType == 'single') {
      $cId = self::_convertToId($name);
      CRM_Utils_Type::escape($cId, 'Integer');
      $action = ($state == 'checked') ? 'select' : 'unselect';
      Civi::service('prevnext')->markSelection($cacheKey, $action, $cId);
    }
    $contactIds = Civi::service('prevnext')->getSelection($cacheKey);
    $countSelectionCids = count($contactIds[$cacheKey]);

    $arrRet = ['getCount' => $countSelectionCids];
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
    $contactId = CRM_Utils_Request::retrieve('contact_id', 'Positive');
    if (!$contactId) {
      $addressVal["error_message"] = "no contact id found";
    }
    else {
      $entityBlock = [
        'contact_id' => $contactId,
        'entity_id' => $contactId,
      ];
      $addressVal = CRM_Core_BAO_Address::getValues($entityBlock);
    }

    CRM_Utils_JSON::output($addressVal);
  }

  /**
   * Mark dupe pairs as selected from un-selected state or vice-versa, in dupe cache table.
   */
  public static function toggleDedupeSelect() {
    $pnid = $_REQUEST['pnid'];
    $isSelected = CRM_Utils_Type::escape($_REQUEST['is_selected'], 'Boolean');
    $cacheKeyString = CRM_Utils_Request::retrieve('cacheKey', 'Alphanumeric', $null, FALSE);

    $params = [
      1 => [$isSelected, 'Boolean'],
      3 => ["$cacheKeyString%", 'String'], // using % to address rows with conflicts as well
    ];

    //check pnid is_array or integer
    $whereClause = NULL;
    if (is_array($pnid) && !CRM_Utils_Array::crmIsEmptyArray($pnid)) {
      CRM_Utils_Type::escapeAll($pnid, 'Positive');
      $pnid = implode(', ', $pnid);
      $whereClause = " id IN ( {$pnid} ) ";
    }
    else {
      $pnid = CRM_Utils_Type::escape($pnid, 'Integer');
      $whereClause = " id = %2";
      $params[2]   = [$pnid, 'Integer'];
    }

    $sql = "UPDATE civicrm_prevnext_cache SET is_selected = %1 WHERE {$whereClause} AND cacheKey LIKE %3";
    CRM_Core_DAO::executeQuery($sql, $params);

    CRM_Utils_System::civiExit();
  }

  /**
   * Retrieve contact relationships.
   */
  public static function getContactRelationships() {
    $contactID = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
    $relationship_type_id = CRM_Utils_Type::escape(CRM_Utils_Array::value('relationship_type_id', $_GET), 'Integer', FALSE);

    if (!CRM_Contact_BAO_Contact_Permission::allow($contactID)) {
      return CRM_Utils_System::permissionDenied();
    }

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();

    $params['contact_id'] = $contactID;
    $params['context'] = $context;
    if ($relationship_type_id) {
      $params['relationship_type_id'] = $relationship_type_id;
    }

    // get the contact relationships
    $relationships = CRM_Contact_BAO_Relationship::getContactRelationshipSelector($params);

    CRM_Utils_JSON::output($relationships);
  }

}
