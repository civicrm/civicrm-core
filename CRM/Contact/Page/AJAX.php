<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

/**
 * This class contains all contact related functions that are called using AJAX (jQuery)
 */
class CRM_Contact_Page_AJAX {
  static function getContactList() {
    // if context is 'customfield'
    if (CRM_Utils_Array::value('context', $_GET) == 'customfield') {
      return self::contactReference();
    }

    $params = array('version' => 3, 'check_permissions' => TRUE);

    // String params
    // FIXME: param keys don't match input keys, using this array to translate
    $whitelist = array(
      's' => 'name',
      'fieldName' => 'field_name',
      'tableName' => 'table_name',
      'context' => 'context',
      'rel' => 'rel'
    );
    foreach ($whitelist as $key => $param) {
      if (!empty($_GET[$key])) {
        $params[$param] = $_GET[$key];
      }
    }

    //CRM-10687: Allow quicksearch by multiple fields
    if (!empty($params['field_name'])) {
      if ($params['field_name'] == 'phone_numeric') {
        $params['name'] = preg_replace('/[^\d]/', '', $params['name']);
      }
      if (!$params['name']) {
        CRM_Utils_System::civiExit();
      }
    }

    // Numeric params
    $whitelist = array(
      'limit',
      'org',
      'employee_id',
      'cid',
      'id',
      'cmsuser',
    );
    foreach ($whitelist as $key) {
      if (!empty($_GET[$key]) && is_numeric($_GET[$key])) {
        $params[$key] = $_GET[$key];
      }
    }

    $result = civicrm_api('Contact', 'getquick', $params);
    if (empty($result['is_error']) && !empty($result['values'])) {
      foreach ($result['values'] as $key => $val) {
        echo "{$val['data']}|{$val['id']}\n";
      }
    }
    CRM_Utils_System::civiExit();
  }

  static function contactReference() {
    $name = CRM_Utils_Array::value('s', $_GET);
    $name = CRM_Utils_Type::escape($name, 'String');
    $cfID = CRM_Utils_Type::escape($_GET['id'], 'Positive');

    // check that this is a valid, active custom field of Contact Reference type
    $params           = array('id' => $cfID);
    $returnProperties = array('filter', 'data_type', 'is_active');
    $fldValues        = array();
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $cf, $returnProperties);
    if (!$cf['id'] || !$cf['is_active'] || $cf['data_type'] = !'ContactReference') {
      echo "$name|error\n";
      CRM_Utils_System::civiExit();
    }

    if ($cf['filter']) {
      $filterParams = array();
      parse_str($cf['filter'], $filterParams);

      $action = CRM_Utils_Array::value('action', $filterParams);

      if (!empty($action) &&
        !in_array($action, array('get', 'lookup'))
      ) {
        echo "$name|error\n";
        CRM_Utils_System::civiExit();
      }
    }

    $list = array_keys(CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_reference_options'
      ), '1');

    $return = array_unique(array_merge(array('sort_name'), $list));

    $config = CRM_Core_Config::singleton();

    $limit = 10;
    if (CRM_Utils_Array::value('limit', $_GET)) {
      $limit = CRM_Utils_Type::escape($_GET['limit'], 'Positive');
    }

    $params = array('offset' => 0, 'rowCount' => $limit, 'version' => 3);
    foreach ($return as $fld) {
      $params["return.{$fld}"] = 1;
    }

    if (!empty($action)) {
      $excludeGet = array('reset', 'key', 'className', 'fnName', 'json', 'reset', 'context', 'timestamp', 'limit', 'id', 's', 'q', 'action');
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

    if (CRM_Utils_Array::value('is_error', $contact)) {
      echo "$name|error\n";
      CRM_Utils_System::civiExit();
    }

    $contactList = '';
    foreach ($contact['values'] as $value) {
      $view = array();
      foreach ($return as $fld) {
        if (CRM_Utils_Array::value($fld, $value)) {
          $view[] = $value[$fld];
        }
      }
      echo $contactList = implode(' :: ', $view) . "|" . $value['id'] . "\n";
    }

    if (!$contactList) {
      echo "$name|$name\n";
    }

    CRM_Utils_System::civiExit();
  }

  /**
   * Function to fetch PCP ID by PCP Supporter sort_name, also displays PCP title and associated Contribution Page title
   */
  static function getPCPList() {
    $name  = CRM_Utils_Array::value('s', $_GET);
    $name  = CRM_Utils_Type::escape($name, 'String');
    $limit = '10';

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

    if (CRM_Utils_Array::value('limit', $_GET)) {
      $limit = CRM_Utils_Type::escape($_GET['limit'], 'Positive');
    }

    $select = 'cc.sort_name, pcp.title, cp.title';
    $query = "
        SELECT id, data
        FROM (
            SELECT pcp.id as id, CONCAT_WS( ' :: ', {$select} ) as data, sort_name
            FROM civicrm_pcp pcp, civicrm_contribution_page cp, civicrm_contact cc
            {$includeEmailFrom}
            {$whereClause}
            LIMIT 0, {$limit}
            ) t
        ORDER BY sort_name
        ";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      echo $pcpList = "$dao->data|$dao->id\n";
    }

    CRM_Utils_System::civiExit();
  }

  /**
   * Function to fetch the values
   */
  static function autocomplete() {
    $fieldID       = CRM_Utils_Type::escape($_GET['cfid'], 'Integer');
    $optionGroupID = CRM_Utils_Type::escape($_GET['ogid'], 'Integer');
    $label         = CRM_Utils_Type::escape($_GET['s'], 'String');

    $selectOption = CRM_Core_BAO_CustomOption::valuesByID($fieldID, $optionGroupID);

    $completeList = NULL;
    foreach ($selectOption as $id => $value) {
      if (strtolower($label) == strtolower(substr($value, 0, strlen($label)))) {
        echo $completeList = "$value|$id\n";
      }
    }
    CRM_Utils_System::civiExit();
  }

  static function relationship() {
    $relType         = CRM_Utils_Array::value('rel_type', $_REQUEST);
    $relContactID    = CRM_Utils_Array::value('rel_contact', $_REQUEST);
    $sourceContactID = CRM_Utils_Array::value('contact_id', $_REQUEST); // we no longer need this.
    $relationshipID  = CRM_Utils_Array::value('rel_id', $_REQUEST); // this used only to determine add or update mode
    $caseID          = CRM_Utils_Array::value('case_id', $_REQUEST);

    // check if there are multiple clients for this case, if so then we need create
    // relationship and also activities for each contacts

    // get case client list
    $clientList = CRM_Case_BAO_Case::getCaseClients($caseID);

    foreach($clientList as $sourceContactID) {
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

        while($caseRelationships->fetch()) {
          $relationIds['relationship'] = $caseRelationships->id;
          $relationIds['contactTarget'] = $relContactID;
        }
        $caseRelationships->free();
      }

      // create new or update existing relationship
      $return = CRM_Contact_BAO_Relationship::create($relationParams, $relationIds);

      $status = 'process-relationship-fail';
      if (CRM_Utils_Array::value(0, $return[4])) {
        $relationshipID = $return[4][0];
        $status = 'process-relationship-success';

        //create an activity for case role assignment.CRM-4480
        CRM_Case_BAO_Case::createCaseRoleActivity($caseID, $relationshipID, $relContactID);
      }
    }

    $relation['status'] = $status;
    echo json_encode($relation);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to fetch the custom field help
   */
  static function customField() {
    $fieldId          = CRM_Utils_Type::escape($_REQUEST['id'], 'Integer');
    $params           = array('id' => $fieldId);
    $returnProperties = array('help_pre', 'help_post');
    $values           = array();

    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $params, $values, $returnProperties);
    echo json_encode($values);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to obtain list of permissioned employer for the given contact-id.
   */
  static function getPermissionedEmployer() {
    $cid  = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $name = trim(CRM_Utils_Type::escape($_GET['s'], 'String'));
    $name = str_replace('*', '%', $name);

    $elements = CRM_Contact_BAO_Relationship::getPermissionedEmployer($cid, $name);

    if (!empty($elements)) {
      foreach ($elements as $cid => $name) {
        echo $element = $name['name'] . "|$cid\n";
      }
    }
    CRM_Utils_System::civiExit();
  }


  static function groupTree() {
    $gids = CRM_Utils_Type::escape($_GET['gids'], 'String');
    echo CRM_Contact_BAO_GroupNestingCache::json($gids);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function for building contact combo box
   */
  static function search() {
    $json = TRUE;
    $name = CRM_Utils_Array::value('name', $_GET, '');
    if (!array_key_exists('name', $_GET)) {
      $name = CRM_Utils_Array::value('s', $_GET) . '%';
      $json = FALSE;
    }
    $name = CRM_Utils_Type::escape($name, 'String');
    $whereIdClause = '';
    if (CRM_Utils_Array::value('id', $_GET)) {
      $json = TRUE;
      if (is_numeric($_GET['id'])) {
        $id = CRM_Utils_Type::escape($_GET['id'], 'Integer');
        $whereIdClause = " AND civicrm_contact.id = {$id}";
      }
      else {
        $name = $_GET['id'];
      }
    }

    $elements = array();
    if ($name || isset($id)) {
      $name = $name . '%';

      //contact's based of relationhip type
      $relType = NULL;
      if (isset($_GET['rel'])) {
        $relation = explode('_', $_GET['rel']);
        $relType  = CRM_Utils_Type::escape($relation[0], 'Integer');
        $rel      = CRM_Utils_Type::escape($relation[2], 'String');
      }

      //shared household info
      $shared = NULL;
      if (isset($_GET['sh'])) {
        $shared = CRM_Utils_Type::escape($_GET['sh'], 'Integer');
        if ($shared == 1) {
          $contactType = 'Household';
          $cName = 'household_name';
        }
        else {
          $contactType = 'Organization';
          $cName = 'organization_name';
        }
      }

      // contacts of type household
      $hh = $addStreet = $addCity = NULL;
      if (isset($_GET['hh'])) {
        $hh = CRM_Utils_Type::escape($_GET['hh'], 'Integer');
      }

      //organization info
      $organization = $street = $city = NULL;
      if (isset($_GET['org'])) {
        $organization = CRM_Utils_Type::escape($_GET['org'], 'Integer');
      }

      if (isset($_GET['org']) || isset($_GET['hh'])) {
        $json = FALSE;
        if ($splitName = explode(' :: ', $name)) {
          $contactName = trim(CRM_Utils_Array::value('0', $splitName));
          $street      = trim(CRM_Utils_Array::value('1', $splitName));
          $city        = trim(CRM_Utils_Array::value('2', $splitName));
        }
        else {
          $contactName = $name;
        }

        if ($street) {
          $addStreet = "AND civicrm_address.street_address LIKE '$street%'";
        }
        if ($city) {
          $addCity = "AND civicrm_address.city LIKE '$city%'";
        }
      }

      if ($organization) {

        $query = "
SELECT CONCAT_WS(' :: ',sort_name,LEFT(street_address,25),city) 'sort_name',
civicrm_contact.id 'id'
FROM civicrm_contact
LEFT JOIN civicrm_address ON ( civicrm_contact.id = civicrm_address.contact_id
                                AND civicrm_address.is_primary=1
                             )
WHERE civicrm_contact.contact_type='Organization' AND organization_name LIKE '%$contactName%'
{$addStreet} {$addCity} {$whereIdClause}
ORDER BY organization_name ";
      }
      elseif ($shared) {
        $query = "
SELECT CONCAT_WS(':::' , sort_name, supplemental_address_1, sp.abbreviation, postal_code, cc.name )'sort_name' , civicrm_contact.id 'id' , civicrm_contact.display_name 'disp' FROM civicrm_contact LEFT JOIN civicrm_address ON (civicrm_contact.id =civicrm_address.contact_id AND civicrm_address.is_primary =1 )LEFT JOIN civicrm_state_province sp ON (civicrm_address.state_province_id =sp.id )LEFT JOIN civicrm_country cc ON (civicrm_address.country_id =cc.id )WHERE civicrm_contact.contact_type ='{$contactType}' AND {$cName} LIKE '%$name%' {$whereIdClause} ORDER BY {$cName} ";
      }
      elseif ($hh) {
        $query = "
SELECT CONCAT_WS(' :: ' , sort_name, LEFT(street_address,25),city) 'sort_name' , location_type_id 'location_type_id', is_primary 'is_primary', is_billing 'is_billing', civicrm_contact.id 'id'
FROM civicrm_contact
LEFT JOIN civicrm_address ON (civicrm_contact.id =civicrm_address.contact_id AND civicrm_address.is_primary =1 )
WHERE civicrm_contact.contact_type ='Household'
AND household_name LIKE '%$contactName%' {$addStreet} {$addCity} {$whereIdClause} ORDER BY household_name ";
      }
      elseif ($relType) {
        if (CRM_Utils_Array::value('case', $_GET)) {
          $query = "
SELECT distinct(c.id), c.sort_name
FROM civicrm_contact c
LEFT JOIN civicrm_relationship ON civicrm_relationship.contact_id_{$rel} = c.id
WHERE c.sort_name LIKE '%$name%'
AND civicrm_relationship.relationship_type_id = $relType
GROUP BY sort_name
";
        }
      }
      else {

        $query = "
SELECT sort_name, id
FROM civicrm_contact
WHERE sort_name LIKE '%$name'
{$whereIdClause}
ORDER BY sort_name ";
      }

      $limit = 10;
      if (isset($_GET['limit'])) {
        $limit = CRM_Utils_Type::escape($_GET['limit'], 'Positive');
      }

      $query .= " LIMIT 0,{$limit}";

      $dao = CRM_Core_DAO::executeQuery($query);

      if ($shared) {
        while ($dao->fetch()) {
          echo $dao->sort_name;
          CRM_Utils_System::civiExit();
        }
      }
      else {
        while ($dao->fetch()) {
          if ($json) {
            $elements[] = array('name' => addslashes($dao->sort_name),
              'id' => $dao->id,
            );
          }
          else {
            echo $elements = "$dao->sort_name|$dao->id|$dao->location_type_id|$dao->is_primary|$dao->is_billing\n";
          }
        }
        //for adding new household address / organization
        if (empty($elements) && !$json && ($hh || $organization)) {
          echo CRM_Utils_Array::value('s', $_GET);
        }
      }
    }

    if (isset($_GET['sh'])) {
      echo "";
      CRM_Utils_System::civiExit();
    }

    if (empty($elements)) {
      $name = str_replace('%', '', $name);
      $elements[] = array(
        'name' => $name,
        'id' => $name,
      );
    }

    if ($json) {
      echo json_encode($elements);
    }
    CRM_Utils_System::civiExit();
  }

  /**
   *
   * Function to check how many contact exits in db for given criteria,
   * if one then return contact id else null
   */
  static function contact() {
    $name = CRM_Utils_Type::escape($_GET['name'], 'String');

    $query = "
SELECT id
FROM civicrm_contact
WHERE sort_name LIKE '%$name%'";

    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();

    if ($dao->N == 1) {
      echo $dao->id;
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to delete custom value
   *
   */
  static function deleteCustomValue() {
    $customValueID = CRM_Utils_Type::escape($_REQUEST['valueID'], 'Positive');
    $customGroupID = CRM_Utils_Type::escape($_REQUEST['groupID'], 'Positive');

    CRM_Core_BAO_CustomValue::deleteCustomValue($customValueID, $customGroupID);
    if ($contactId = CRM_Utils_Array::value('contactId', $_REQUEST)) {
      echo CRM_Contact_BAO_Contact::getCountComponent('custom_' . $_REQUEST['groupID'], $contactId);
    }

    // reset the group contact cache for this group
    CRM_Contact_BAO_GroupContactCache::remove();
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to perform enable / disable actions on record.
   *
   */
  static function enableDisable() {
    $op        = CRM_Utils_Type::escape($_REQUEST['op'], 'String');
    $recordID  = CRM_Utils_Type::escape($_REQUEST['recordID'], 'Positive');
    $recordBAO = CRM_Utils_Type::escape($_REQUEST['recordBAO'], 'String');

    $isActive = NULL;
    if ($op == 'disable-enable') {
      $isActive = TRUE;
    }
    elseif ($op == 'enable-disable') {
      $isActive = FALSE;
    }
    $status = array('status' => 'record-updated-fail');
    if (isset($isActive)) {
      // first munge and clean the recordBAO and get rid of any non alpha numeric characters
      $recordBAO = CRM_Utils_String::munge($recordBAO);
      $recordClass = explode('_', $recordBAO);

      // make sure recordClass is namespaced (we cant check CRM since extensions can also use this)
      // but it should be at least 3 levels deep
      if (count($recordClass) >= 3) {
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $recordBAO) . ".php");
        $method = 'setIsActive';

        if (method_exists($recordBAO, $method)) {
          $updated = call_user_func_array(array($recordBAO, $method),
            array($recordID, $isActive)
          );
          if ($updated) {
            $status = array('status' => 'record-updated-success');
          }

          // call hook enableDisable
          CRM_Utils_Hook::enableDisable($recordBAO, $recordID, $isActive);
        }
      }
      echo json_encode($status);
      CRM_Utils_System::civiExit();
    }
  }

  /**
     *Function to check the CMS username
     *
    */
  static public function checkUserName() {
    $config = CRM_Core_Config::singleton();
    $username = trim($_REQUEST['cms_name']);

    $params = array('name' => $username);

    $errors = array();
    $config->userSystem->checkUserNameEmailExists($params, $errors);

    if (isset($errors['cms_name']) || isset($errors['name'])) {
      //user name is not availble
      $user = array('name' => 'no');
      echo json_encode($user);
    }
    else {
      //user name is available
      $user = array('name' => 'yes');
      echo json_encode($user);
    }
    CRM_Utils_System::civiExit();
  }

  /**
   *  Function to get email address of a contact
   */
  static function getContactEmail() {
    if (CRM_Utils_Array::value('contact_id', $_REQUEST)) {
      $contactID = CRM_Utils_Type::escape($_REQUEST['contact_id'], 'Positive');
      list($displayName,
        $userEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      if ($userEmail) {
        echo $userEmail;
      }
    }
    else {
      $noemail = CRM_Utils_Array::value('noemail', $_GET);
      $queryString = NULL;
      if ($name = CRM_Utils_Array::value('name', $_GET)) {
        $name = CRM_Utils_Type::escape($name, 'String');
        if ($noemail) {
          $queryString = " cc.sort_name LIKE '%$name%'";
        }
        else {
          $queryString = " ( cc.sort_name LIKE '%$name%' OR ce.email LIKE '%$name%' ) ";
        }
      }
      elseif ($cid = CRM_Utils_Array::value('cid', $_GET)) {
        //check cid for interger
        $contIDS = explode(',', $cid);
        foreach ($contIDS as $contID) {
          CRM_Utils_Type::escape($contID, 'Integer');
        }
        $queryString = " cc.id IN ( $cid )";
      }

      if ($queryString) {
        $offset = CRM_Utils_Array::value('offset', $_GET, 0);
        $rowCount = CRM_Utils_Array::value('rowcount', $_GET, 20);

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
              'name' => $dao->name,
              'id' => $dao->id,
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
            $result[] = array(
              'name' => '"' . $dao->name . '" &lt;' . $dao->email . '&gt;',
              'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->email}" : '"' . $dao->name . '" <' . $dao->email . '>',
            );
          }
        }

        if ($result) {
          echo json_encode($result);
        }
      }
    }
    CRM_Utils_System::civiExit();
  }

  static function getContactPhone() {

    $queryString = NULL;
    //check for mobile type
    $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
    $mobileType = CRM_Utils_Array::value('Mobile', $phoneTypes);

    if ($name = CRM_Utils_Array::value('name', $_GET)) {
      $name = CRM_Utils_Type::escape($name, 'String');
      $queryString = " ( cc.sort_name LIKE '%$name%' OR cp.phone LIKE '%$name%' ) ";
    }
    elseif ($cid = CRM_Utils_Array::value('cid', $_GET)) {
      //check cid for interger
      $contIDS = explode(',', $cid);
      foreach ($contIDS as $contID) {
        CRM_Utils_Type::escape($contID, 'Integer');
      }
      $queryString = " cc.id IN ( $cid )";
    }

    if ($queryString) {
      $offset = CRM_Utils_Array::value('offset', $_GET, 0);
      $rowCount = CRM_Utils_Array::value('rowcount', $_GET, 20);

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
          'name' => '"' . $dao->name . '" &lt;' . $dao->phone . '&gt;',
          'id' => (CRM_Utils_Array::value('id', $_GET)) ? "{$dao->id}::{$dao->phone}" : '"' . $dao->name . '" <' . $dao->phone . '>',
        );
      }
    }

    if ($result) {
      echo json_encode($result);
    }
    CRM_Utils_System::civiExit();
  }


  static function buildSubTypes() {
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
    echo json_encode($subTypes);
    CRM_Utils_System::civiExit();
  }

  static function buildDedupeRules() {
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

    echo json_encode($dedupeRules);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function used for CiviCRM dashboard operations
   */
  static function dashboard() {
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

    echo json_encode($dashlets);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to retrieve signature based on email id
   */
  static function getSignature() {
    $emailID = CRM_Utils_Type::escape($_REQUEST['emailID'], 'Positive');
    $query   = "SELECT signature_text, signature_html FROM civicrm_email WHERE id = {$emailID}";
    $dao     = CRM_Core_DAO::executeQuery($query);

    $signatures = array();
    while ($dao->fetch()) {
      $signatures = array(
        'signature_text' => $dao->signature_text,
        'signature_html' => $dao->signature_html,
      );
    }

    echo json_encode($signatures);
    CRM_Utils_System::civiExit();
  }

  static function relationshipContacts() {
    $searchValues = $searchRows = array();
    $addCount = 0;

    $relType          = CRM_Utils_Type::escape($_REQUEST['relType'], 'String');
    $typeName         = isset($_REQUEST['typeName']) ? CRM_Utils_Type::escape($_REQUEST['typeName'], 'String') : '';
    $relContact       = CRM_Utils_Type::escape($_REQUEST['relContact'], 'String');
    $currentContactId = CRM_Utils_Type::escape($_REQUEST['cid'], 'Integer');

    if (in_array($typeName, array(
      'Employee of', 'Employer of'))) {
      $addCount = 1;
    }

    $sortMapper = array(
      1 => 'sort_name', (2 + $addCount) => 'city', (3 + $addCount) => 'state_province',
      (4 + $addCount) => 'email', (5 + $addCount) => 'phone',
    );

    $sEcho     = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset    = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount  = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort      = isset($_REQUEST['iSortCol_0']) ? $sortMapper[CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer')] : 'sort_name';
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $searchValues[] = array('sort_name', 'LIKE', $relContact, 0, 1);

    list($rid, $direction) = explode('_', $relType, 2);

    $relationshipType = new CRM_Contact_DAO_RelationshipType();

    $relationshipType->id = $rid;
    if ($relationshipType->find(TRUE)) {
      if ($direction == 'a_b') {
        $type = $relationshipType->contact_type_b;
        $subType = $relationshipType->contact_sub_type_b;
      }
      else {
        $type = $relationshipType->contact_type_a;
        $subType = $relationshipType->contact_sub_type_a;
      }

      if ($type == 'Individual' || $type == 'Organization' || $type == 'Household') {
        $searchValues[] = array('contact_type', '=', $type, 0, 0);
      }

      if ($subType) {
        $searchValues[] = array('contact_sub_type', '=', $subType, 0, 0);
      }
    }

    // exclude current contact
    $searchValues[] = array('contact_id', '!=', $currentContactId, 0, 0);

    $query       = new CRM_Contact_BAO_Query($searchValues);
    $searchCount = $query->searchQuery(0, 0, NULL, TRUE);
    $iTotal      = $searchCount;

    if ($searchCount > 0) {
      // get the result of the search
      $result = $query->searchQuery($offset, $rowCount, $sort, FALSE, FALSE,
        FALSE, FALSE, FALSE, NULL, $sortOrder
      );

      $config = CRM_Core_Config::singleton();

      while ($result->fetch()) {
        $contactID = $result->contact_id;
        $typeImage = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
          $result->contact_sub_type : $result->contact_type,
          FALSE, $contactID
        );

        $searchRows[$contactID]['id'] = $contactID;
        $searchRows[$contactID]['name'] = $typeImage . ' ' . $result->sort_name;
        $searchRows[$contactID]['city'] = $result->city;
        $searchRows[$contactID]['state'] = $result->state_province;
        $searchRows[$contactID]['email'] = $result->email;
        $searchRows[$contactID]['phone'] = $result->phone;
      }
    }

    foreach ($searchRows as $cid => $row) {
      $searchRows[$cid]['check'] = '<input type="checkbox" id="contact_check[' . $cid . ']" name="contact_check[' . $cid . ']" value=' . $cid . ' />';

      if ($typeName == 'Employee of') {
        $searchRows[$cid]['employee_of'] = '<input type="radio" name="employee_of" value=' . $cid . ' >';
      }
      elseif ($typeName == 'Employer of') {
        $searchRows[$cid]['employer_of'] = '<input type="checkbox"  name="employer_of[' . $cid . ']" value=' . $cid . ' />';
      }
    }

    $selectorElements = array('check', 'name');
    if ($typeName == 'Employee of') {
      $selectorElements[] = 'employee_of';
    }
    elseif ($typeName == 'Employer of') {
      $selectorElements[] = 'employer_of';
    }
    $selectorElements = array_merge($selectorElements, array('city', 'state', 'email', 'phone'));

    $iFilteredTotal = $iTotal;
    echo CRM_Utils_JSON::encodeDataTableSelector($searchRows, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to process dupes.
   *
   */
  static function processDupes() {
    $oper = CRM_Utils_Type::escape($_REQUEST['op'], 'String');
    $cid  = CRM_Utils_Type::escape($_REQUEST['cid'], 'Positive');
    $oid  = CRM_Utils_Type::escape($_REQUEST['oid'], 'Positive');

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

    echo json_encode(array('status' => ($status) ? $oper : $status));
    CRM_Utils_System::civiExit();
  }

  static function getDedupes() {

    $sEcho     = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset    = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount  = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort      = isset($_REQUEST['iSortCol_0']) ? $sortMapper[CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer')] : 'sort_name';
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $gid         = isset($_REQUEST['gid']) ? CRM_Utils_Type::escape($_REQUEST['gid'], 'Integer') : 0;
    $rgid        = isset($_REQUEST['rgid']) ? CRM_Utils_Type::escape($_REQUEST['rgid'], 'Integer') : 0;
    $contactType = '';
    if ($rgid) {
      $contactType = CRM_Core_DAO::getFieldValue('CRM_Dedupe_DAO_RuleGroup', $rgid, 'contact_type');
    }

    $cacheKeyString   = "merge {$contactType}_{$rgid}_{$gid}";
    $searchRows       = array();
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

      if (CRM_Utils_Array::value('canMerge', $main)) {
        $mergeParams = "reset=1&cid={$main['srcID']}&oid={$main['dstID']}&action=update&rgid={$rgid}";
        if ($gid) {
          $mergeParams .= "&gid={$gid}";
        }

        $searchRows[$mainId]['actions'] = CRM_Utils_System::href(ts('merge'), 'civicrm/contact/merge', $mergeParams);
        $searchRows[$mainId]['actions'] .= "&nbsp;|&nbsp; <a id='notDuplicate' href='#' onClick=\"processDupes( {$main['srcID']}, {$main['dstID']}, 'dupe-nondupe', 'dupe-listing'); return false;\">" . ts('not a duplicate') . "</a>";
      }
      else {
        $searchRows[$mainId]['actions'] = '<em>' . ts('Insufficient access rights - cannot merge') . '</em>';
      }
    }

    echo CRM_Utils_JSON::encodeDataTableSelector($searchRows, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);

    CRM_Utils_System::civiExit();
  }

  /**
   * Function to retrieve a PDF Page Format for the PDF Letter form
   */
  function pdfFormat() {
    $formatId = CRM_Utils_Type::escape($_REQUEST['formatId'], 'Integer');

    $pdfFormat = CRM_Core_BAO_PdfFormat::getById($formatId);

    echo json_encode($pdfFormat);
    CRM_Utils_System::civiExit();
  }

  /**
   * Function to retrieve Paper Size dimensions
   */
  function paperSize() {
    $paperSizeName = CRM_Utils_Type::escape($_REQUEST['paperSizeName'], 'String');

    $paperSize = CRM_Core_BAO_PaperSize::getByName($paperSizeName);

    echo json_encode($paperSize);
    CRM_Utils_System::civiExit();
  }

  static function relationshipContactTypeList() {
    $relType = CRM_Utils_Array::value('relType', $_REQUEST);

    $types = CRM_Contact_BAO_Relationship::getValidContactTypeList($relType);

    $elements = array();
    foreach ($types as $key => $label) {
      $elements[] = array(
        'name' => $label,
        'value' => $key,
      );
    }

    echo json_encode($elements);
    CRM_Utils_System::civiExit();
  }

  static function selectUnselectContacts() {
    $name         = CRM_Utils_Array::value('name', $_REQUEST);
    $cacheKey     = CRM_Utils_Array::value('qfKey', $_REQUEST);
    $state        = CRM_Utils_Array::value('state', $_REQUEST, 'checked');
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
    echo json_encode($arrRet);
    CRM_Utils_System::civiExit();
  }

  static function _convertToId($name) {
    if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
      $cId = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
    }
    return $cId;
  }

  static function getAddressDisplay() {
    $contactId = CRM_Utils_Array::value('contact_id', $_REQUEST);
    if (!$contactId) {
      $addressVal["error_message"] = "no contact id found";
    }
    else {
      $entityBlock =
        array(
          'contact_id' => $contactId,
          'entity_id' => $contactId,
        );
      $addressVal = CRM_Core_BAO_Address::getValues($entityBlock);
    }

    echo json_encode($addressVal);
    CRM_Utils_System::civiExit();
  }
}
