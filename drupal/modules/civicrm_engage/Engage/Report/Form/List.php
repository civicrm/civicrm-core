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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * @copyright DharmaTech  (c) 2009
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';
require_once 'CRM/Core/DAO.php';

/**
 *  Generate a walk list
 */
class Engage_Report_Form_List extends CRM_Report_Form {
  /*
     * Note: In order to detect column names of a particular custom group, we need to know 
     * custom field ID or LABEL. Since labels are less likely to change on initial setup of the module, 
     * we 'll use label constants for now.
     *
     * Please note these values 'll need to be adjusted if custom field labels are modified.
     *
     */
  CONST CF_CONSTITUENT_TYPE_NAME = 'constituent_type', CF_OTHER_NAME_NAME = 'other_name', CG_VOTER_INFO_TABLE = 'civicrm_value_voter_info', CF_PARTY_REG_NAME = 'party_registration', CF_VOTER_HISTORY_NAME = 'voter_history', CG_DEMOGROPHICS_TABLE = 'civicrm_value_demographics';

  /**
   *  Address information needed in output
   *  @var boolean
   */
  protected $_addressField = FALSE;

  /**
   *  Email address needed in output
   *  @var boolean
   */
  protected $_emailField = FALSE;

  /**
   *  Demographic information needed in output
   *  @var boolean
   */
  protected $_demoField = FALSE;

  protected $_coreField = FALSE;

  /**
   *  Phone number needed in output
   *  @var boolean
   */
  protected $_phoneField = FALSE;

  /**
   *  Group membership information needed in output
   *  @var boolean
   */
  protected $_groupField = FALSE;

  /**
   *  Voter Info information needed in output
   *  @var boolean
   */
  protected $_voterInfoField = FALSE;

  protected $_contributionField = FALSE;

  /**
   *  Constituent individual table name has changed
   *  between versions of civicrm. Populate this field
   *  dynamically to ensure backward compatability
   */
  protected $_constituentIndividualTable = FALSE;

  /**
   * Langauage field might be primary or secondary
   * depending on version...
   */
  protected $_langaugeName = FALSE;

  protected $_summary = NULL;

  /**
   *  Available contact type options
   *  @var string[]
   */
  protected $_contactType = NULL;

  /**
   *  Available gender options
   *  @var string[]
   */
  protected $_gender = NULL;

  /**
   *  Available group options
   *  @var string[]
   */
  protected $_groups = NULL;

  protected $_groupDescr = NULL;

  /**
   *  Available primary language options
   *  @var string[]
   */
  protected $_languages = NULL;

  protected $_orgName = NULL;

  /**
   *  Table with Voter Info group information
   *  @var string
   */
  protected $_voterInfoTable;

  protected $_demoTable;

  protected $_demoLangCol;

  protected $_coreInfoTable;

  protected $_coreTypeCol;

  protected $_coreOtherCol;

  /**
   *  Column in $_voterInfoTable with party registration information
   *  @var string
   */
  protected $_partyCol;

  /**
   *  Available party registration options
   *  @var string[]
   */
  protected $_partyRegs = array();

  /**
   *  Column in $_voterInfoTable with voter history information
   *  @var string
   */
  protected $_vhCol; function __construct() {
    // Find the invidual constituent table (varies between versions)
    $query = "SELECT table_name FROM civicrm_custom_group g" . " JOIN civicrm_custom_field f ON g.id = f.custom_group_id" . " WHERE column_name='" . self::CF_CONSTITUENT_TYPE_NAME . "' AND" . " ( g.table_name = 'civicrm_value_core_info' OR g.table_name " . " = 'civicrm_value_constituent_info' )";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_constituentIndividualTable = $dao->table_name;

    // Find the language field name (varies between versions - either
    // primary_langauge or secondary_language)
    $query = "SELECT column_name FROM civicrm_custom_field" . " WHERE column_name = 'primary_language' OR" . " column_name = 'secondary_language'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_languageName = $dao->column_name;

    //  Find the Voter Info custom data group
    $query = "SELECT id, table_name FROM civicrm_custom_group" . " WHERE table_name='" . self::CG_VOTER_INFO_TABLE . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $voterInfoID = $dao->id;
    $this->_voterInfoTable = $dao->table_name;

    //  From Voter Info custom data group get Party Registration info
    $query = "SELECT column_name, option_group_id" . " FROM civicrm_custom_field" . " WHERE custom_group_id={$voterInfoID}" . " AND column_name='" . self::CF_PARTY_REG_NAME . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_partyCol = $dao->column_name;
    $partyOptGrp     = $dao->option_group_id;
    $query           = "SELECT label, value" . " FROM civicrm_option_value" . " WHERE option_group_id={$partyOptGrp}";
    $dao             = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $this->_partyRegs[$dao->value] = $dao->label;
    }

    //  From Voter Info custom data group get Voter History info
    $query = "SELECT column_name, option_group_id" . " FROM civicrm_custom_field" . " WHERE custom_group_id={$voterInfoID}" . " AND column_name='" . self::CF_VOTER_HISTORY_NAME . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_vhCol = $dao->column_name;

    //  Get contactType option values
    //  There are two custom groups named 'Contact Type'
    //  so there isn't a very good way to do this.
    $this->_contactType = array('' => '');
    $query = "
SELECT ov.label, ov.value FROM civicrm_option_value ov
WHERE ov.option_group_id = (
    SELECT cf.option_group_id FROM civicrm_custom_field cf
    WHERE  cf.custom_group_id = (
        SELECT cg.id FROM civicrm_custom_group cg WHERE cg.table_name='" . $this->_constituentIndividualTable . "' ) AND cf.column_name='" . self::CF_CONSTITUENT_TYPE_NAME . "'
)";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $this->_contactType[$dao->value] = $dao->label;
    }


    // ** demographics ** //
    $query = "SELECT id, table_name FROM civicrm_custom_group WHERE table_name='" . self::CG_DEMOGROPHICS_TABLE . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $demoTableID = $dao->id;
    $this->_demoTable = $dao->table_name;

    $query = "
SELECT column_name 
FROM   civicrm_custom_field 
WHERE custom_group_id={$demoTableID} AND column_name = '" . $this->_languageName . "' LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_demoLangCol = $dao->column_name;

    // ** Core Info ** //
    $query = "SELECT id, table_name FROM civicrm_custom_group WHERE table_name='" . $this->_constituentIndividualTable . "'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $coreInfoTableID = $dao->id;
    $this->_coreInfoTable = $dao->table_name;

    $query = "
SELECT column_name 
FROM   civicrm_custom_field 
WHERE custom_group_id={$coreInfoTableID} AND column_name='" . self::CF_OTHER_NAME_NAME . "' LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_coreOtherCol = $dao->column_name;

    $query = "
SELECT column_name 
FROM   civicrm_custom_field 
WHERE custom_group_id={$coreInfoTableID} AND column_name='" . self::CF_CONSTITUENT_TYPE_NAME . "' LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_coreTypeCol = $dao->column_name;


    //  Get language option values, English on top
    $this->_languages = array('' => '');
    $query = "
SELECT ov.label, ov.value FROM civicrm_option_value ov
WHERE ov.option_group_id = (
    SELECT cf.option_group_id FROM civicrm_custom_field cf
    WHERE  cf.custom_group_id = {$demoTableID} AND column_name = '{$this->_demoLangCol}'
)
ORDER BY ov.label
";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $this->_languages[$dao->value] = $dao->label;
    }

    //  Get organization name
    $query = "SELECT name FROM civicrm_domain";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    $this->_orgName = $dao->name;

    parent::__construct();
  }

  function setDefaultValues($freeze = TRUE) {
    $defaults = parent::setDefaultValues($freeze);
    $defaults['report_header'] = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <style>
        body { font-size: 10px;
             }
        h1   { text-align: center;
               font-size: 14px;
               margin-bottom: 8px;
               padding-bottom: 0;
             }
        h2   { text-align: center;
               font-size: 14px;
               margin-top: 0;
               padding-top: 0;
               padding-bottom: 20px;
               margin-bottom: 20px;
             }
        .body { border-collapse: collapse;
                border-spacing: 0px;
                width: 770px;
                margin: 0;
                padding: 0;
              }
        .head { width: 770px;
              }
        thead th { padding: 0;
                   margin: 0;
                   text-align: left;
               font-size: 10px;
                   whitespace: normal;
                 }
        .head td    { padding-left: 5px;
                margin: 0 0 20px 0;
                border: 0;
            font-size: 14px;
                whitespace: normal;
              }
        .body td    { padding-left: 5px;
                margin: 0;
                border: 1px solid black;
            font-size: 10px;
                whitespace: normal;
              }
        p     { padding: 0;
                margin: 0;
              }
        .page { page-break-before: always;
              }
        @page { size: landscape;
              }
      </style>
      <meta http-equiv="Content-Type" content="text/xhtml; charset=utf-8"/>
    </head>
    <body>
';
    return $defaults;
  }

  function preProcess() {
    parent::preProcess();
  }

  function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'gid' && $type == CRM_Report_Form::OP_MULTISELECT) {
      return array('in' => ts('Is one of'),
        'mand' => ts('Is equal to'),
      );
    }
    else {
      return parent::getOperationPair($type);
    }
  }

  function engageWhereGroupClause($clause) {
    $smartGroupQuery = "";
    require_once 'CRM/Contact/DAO/Group.php';
    require_once 'CRM/Contact/BAO/SavedSearch.php';

    $group = new CRM_Contact_DAO_Group();
    $group->is_active = 1;
    $group->find();
    while ($group->fetch()) {
      if (in_array($group->id, $this->_params['gid_value']) && $group->saved_search_id) {
        $smartGroups[] = $group->id;
      }
    }

    if (!empty($smartGroups)) {
      $smartGroups = implode(',', $smartGroups);
      $smartGroupQuery = " UNION DISTINCT 
                  SELECT DISTINCT smartgroup_contact.contact_id                                    
                  FROM civicrm_group_contact_cache smartgroup_contact        
                  WHERE smartgroup_contact.group_id IN ({$smartGroups}) ";
    }

    if ($this->_params['gid_op'] == 'in') {
      return " {$this->_aliases['civicrm_contact']}.id IN ( 
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}.contact_id 
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}
                          WHERE {$clause} AND {$this->_aliases['civicrm_group']}.status = 'Added' 
                          {$smartGroupQuery} ) ";
    }
    elseif ($this->_params['gid_op'] == 'mand') {
      $query = " {$this->_aliases['civicrm_contact']}.id IN ( 
                          SELECT DISTINCT {$this->_aliases['civicrm_group']}1.contact_id 
                          FROM civicrm_group_contact {$this->_aliases['civicrm_group']}1
";

      for ($i = 2; $i <= count($this->_params['gid_value']); $i++) {
        $j = $i - 1;
        $status[] = "{$this->_aliases['civicrm_group']}{$i}.group_id != {$this->_aliases['civicrm_group']}{$j}.group_id";
        $query .= " INNER JOIN civicrm_group_contact {$this->_aliases['civicrm_group']}{$i} 
                              ON {$this->_aliases['civicrm_group']}{$i}.contact_id = {$this->_aliases['civicrm_group']}{$j}.contact_id AND " . implode(" AND ", $status) . "
";
      }
      $query .= " WHERE ";

      for ($i = 1; $i <= count($this->_params['gid_value']); $i++) {
        $query .= ($i > 1) ? " AND " : "";
        $query .= " {$this->_aliases['civicrm_group']}{$i}.group_id IN ( '" . implode("' , '", $this->_params['gid_value']) . "') AND {$this->_aliases['civicrm_group']}{$i}.status = 'Added'
";
      }
      $query .= " {$smartGroupQuery} ) ";

      return $query;
    }
  }

  function select() {
    $select = array();
    //var_dump($this->_params);
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {

          //var_dump($this->_params['fields'][$fieldName]);
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_group_contact') {
              $this->_groupField = TRUE;
            }
            elseif ($tableName == $this->_demoTable) {
              $this->_demoField = TRUE;
            }
            elseif ($tableName == $this->_coreInfoTable) {
              $this->_coreField = TRUE;
            }
            elseif ($tableName == $this->_voterInfoTable) {
              $this->_voterInfoField = TRUE;
            }
            elseif ($tableName == "civicrm_contribution_cont") {
              $this->_contributionField = TRUE;
            }

            $select[] = "{$table['alias']}.{$fieldName} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(",\n", $select) . " ";
  }

  /**
   *  Generate FROM clause for SQL SELECT
   */
  protected function from() {

    $this->_from = " FROM civicrm_contact AS {$this->_aliases['civicrm_contact']} ";
    if ($this->_contributionField) {
      //store in a temp table max receive date & relevant contribution _value - described as 'group by trick' here http://dev.mysql.com/doc/refman/5.1/en/example-maximum-column-group-row.html
      //there doesn't appear to be any efficient way to do this without using the temp table
      //as we want to use the latest receive date & the latest value (not the max value)
      //and we want to join this against contact_id
      $query = "create temporary table civicrm_maxconts SELECT * FROM ( SELECT  `receive_date` ,`total_amount`, contact_id  FROM `civicrm_contribution` ORDER BY receive_date DESC) as maxies group by contact_id;";
      $dao = CRM_Core_DAO::executeQuery($query);
      //apparently it is faster to create & then index http://mysqldump.azundris.com/archives/80-CREATE-TEMPORARY-TABLE.html
      $query        = "alter table civicrm_maxconts add index (contact_id);";
      $dao          = CRM_Core_DAO::executeQuery($query);
      $this->_from .= "LEFT JOIN civicrm_maxconts cont_civireport ON contact_civireport.id = cont_civireport.contact_id ";
    }
    if ($this->_addressField) {
      $this->_from .= "LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    if ($this->_emailField) {
      $this->_from .= "LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }

    if ($this->_phoneField) {
      $this->_from .= "LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND {$this->_aliases['civicrm_phone']}.is_primary = 1\n";
    }

    if ($this->_demoField) {
      $this->_from .= " LEFT JOIN " . $this->_demoTable . "   AS " . $this->_aliases[$this->_demoTable] . " ON {$this->_aliases['civicrm_contact']}.id =" . $this->_aliases[$this->_demoTable] . ".entity_id\n";
    }

    if ($this->_coreField) {
      $this->_from .= " LEFT JOIN " . $this->_coreInfoTable . "   AS " . $this->_aliases[$this->_coreInfoTable] . " ON {$this->_aliases['civicrm_contact']}.id =" . $this->_aliases[$this->_coreInfoTable] . ".entity_id\n";
    }


    if ($this->_voterInfoField) {
      $this->_from .= " LEFT JOIN {$this->_voterInfoTable}" . "   AS {$this->_aliases[$this->_voterInfoTable]}" . " ON {$this->_aliases['civicrm_contact']}.id =" . "{$this->_aliases[$this->_voterInfoTable]}.entity_id\n";
    }
  }

  /**
   *  Interpret the 'order_by' keys in selected fields
   */
  public function orderBy() {
    $this->_orderBy = "";
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('order_bys', $table)) {
        foreach ($table['order_bys'] as $fieldName => $field) {
          $this->_orderBy[] = $field['dbAlias'];
        }
      }
    }
    $this->_orderBy = "ORDER BY " . implode(', ', $this->_orderBy) . " ";
  }

  /**
   *  Convert a string of fields separated by \x01 to a
   *  string of fields separated by commas
   */
  function hexOne2str($hexOne) {
    $hexOneArray = explode("\x01", $hexOne);
    foreach ($hexOneArray as $key => $value) {
      if (empty($value)) {
        unset($hexOneArray[$key]);
      }
    }
    return implode(', ', $hexOneArray);
  }

  /**
   *  Convert MySQL YYYY-MM-DD HH:MM:SS date of birth timestamp to
   *  current age
   */
  function dob2age($myTimestamp) {
    //  Separate parts of DOB timestamp
    $matches = array();
    preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',
      $myTimestamp, $matches
    );
    //var_dump($matches);
    $dobYear  = (int)$matches[1];
    $dobMonth = (int)$matches[2];
    $dobDay   = (int)$matches[3];
    //echo "DOB year=$dobYear month=$dobMonth day=$dobDay<br>";

    $nowYear  = (int)strftime('%Y');
    $nowMonth = (int)strftime('%m');
    $nowDay   = (int)strftime('%d');
    //echo "Now year=$nowYear month=$nowMonth day=$nowDay<br>";
    //  Calculate age
    if ($dobMonth < $nowMonth) {

      //  Born in a month before this month
      $age = $nowYear - $dobYear;
    }
    elseif ($dobMonth == $nowMonth) {
      //  Born in this month
      if ($dobDay <= $nowDay) {
        // Born before or on this day
        $age = $nowYear - $dobYear;
      }
      else {
        //  Born after today in this month
        $age = $nowYear - $dobYear - 1;
      }
    }
    else {
      //  Born in a month after this month
      $age = $nowYear - $dobYear - 1;
    }
    //echo "age=$age years<br>";
    return $age;
  }
}

