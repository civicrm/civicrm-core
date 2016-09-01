<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * @copyright DharmaTech  (c) 2009
 * $Id$
 *
 */

require_once 'Engage/Report/Form/List.php';

/**
 *  Generate a walk list
 */
class Engage_Report_Form_WalkList extends Engage_Report_Form_List {
  function __construct() {

    parent::__construct();

    //  Walk list columns
    $this->_columns = array(
      $this->_demoTable =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          $this->_demoLangCol =>
          array(
            'type' => CRM_Report_Form::OP_STRING,
            'required' => TRUE,
            'title' => ts('Language'),
          ),
        ),
        'filters' =>
        array(
          $this->_demoLangCol =>
          array(
            'title' => ts('Language'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Report_Form::OP_STRING,
            'options' => $this->_languages,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      $this->_coreInfoTable =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          $this->_coreTypeCol =>
          array(
            'type' => CRM_Report_Form::OP_STRING,
            'required' => TRUE,
            'title' => ts('Constituent Type'),
          ),
          $this->_coreOtherCol =>
          array(
            'type' => CRM_Report_Form::OP_STRING,
            'required' => TRUE,
            'title' => ts('Other Name'),
          ),
        ),
        'filters' =>
        array(
          $this->_coreTypeCol =>
          array(
            'title' => ts('Constituent Type'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Report_Form::OP_STRING,
            'options' => $this->_contactType,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'gender_id' =>
          array('title' => ts('Sex'),
            'required' => TRUE,
          ),
          'birth_date' =>
          array('title' => ts('Age'),
            'required' => TRUE,
            'type' => CRM_Report_Form::OP_INT,
          ),
          'id' =>
          array('title' => ts('Contact ID'),
            'required' => TRUE,
          ),
          'display_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'gender_id' =>
          array('title' => ts('Sex'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Report_Form::OP_STRING,
            'options' => array('' => '') + CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'operator' => 'like',
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' =>
        array('sort_name' => array('title' => ts('Contact Name'),
            'required' => TRUE,
          )),
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_number' =>
          array(
            'required' => TRUE,
            'title' => ts('Street#'),
          ),
          'street_name' =>
          array('title' => ts('Street Name'),
            'nodisplay' => TRUE,
            'required' => TRUE,
          ),
          'street_address' =>
          array(
            'required' => TRUE,
            'title' => ts('Street Address'),
          ),
          'street_unit' =>
          array(
            'required' => TRUE,
            'title' => ts('Apt.'),
          ),
          'city' =>
          array('required' => TRUE),
          'postal_code' =>
          array(
            'title' => 'Zip',
            'required' => TRUE,
          ),
          'state_province_id' =>
          array('title' => ts('State/Province'),
            'required' => TRUE,
          ),
          'country_id' =>
          array('title' => ts('Country'),
          ),
        ),
        'filters' =>
        array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => array('title' => 'Zip'),
        ),
        'grouping' => 'location-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' => array('default' => TRUE,
            'required' => TRUE,
          )),
        'grouping' => 'location-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' => NULL),
        'grouping' => 'location-fields',
      ),
      $this->_voterInfoTable =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          $this->_partyCol =>
          array(
            'type' => CRM_Report_Form::OP_STRING,
            'required' => TRUE,
            'title' => ts('Party Reg'),
          ),
          $this->_vhCol =>
          array(
            'type' => CRM_Report_Form::OP_STRING,
            'required' => TRUE,
            'title' => ts('VH'),
          ),
        ),
        'filters' => array(),
        'grouping' => 'contact-fields',
      ),
      'civicrm_group' =>
      array(
        'dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'filters' =>
        array(
          'gid' =>
          array(
            'name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ),
        ),
      ),
      'civicrm_contribution_lastcont' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
      ),
      'civicrm_contribution_cont' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'alias' => 'cont',
        'fields' =>
        array(
          'receive_date' => array('default' => TRUE, 'title' => 'Last Receipt'),
          'total_amount' => array(
            'default' => TRUE, 'title' => 'Amount received',
          ),
        ),
      ),
    );
  }

  /**
   *  Generate WHERE clauses for SQL SELECT
   *  FIXME: deal with age filter
   */
  function where() {
    $clauses = array("{$this->_aliases['civicrm_address']}.id IS NOT NULL");

    foreach ($this->_columns as $tableName => $table) {
      //echo "where: table name $tableName<br>";

      //  Treatment of normal filters
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          //echo "&nbsp;&nbsp;&nbsp;field name $fieldName<br>";
          $clause = NULL;

          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to);
          }
          elseif ($fieldName == $this->_demoLangCol) {
            if (!empty($this->_params[$this->_demoLangCol . '_value'])) {
              $clause = "{$field['dbAlias']}='" . $this->_params[$this->_demoLangCol . '_value'] . "'";
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op == 'mand') {
              $clause = TRUE;
            }
            elseif ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }
          //var_dump($clause);
          if (!empty($clause)) {
            if (!empty($field['group'])) {
              $clauses[] = $this->engageWhereGroupClause($clause);
            }
            else {
              $clauses[] = $clause;
            }
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  /**
   *  Process submitted form
   */
  function postProcess() {
    parent::postProcess();
  }

  function alterDisplay(&$rows) {

    if ($this->_outputMode == 'print' || $this->_outputMode == 'pdf') {
      $this->executePrintmode($rows);
      return;
    }
    // custom code to alter rows
    //var_dump($rows);
    $genderList = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // handle state province
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value);
        }
        $entryFound = TRUE;
      }

      // Handle contactType
      if (!empty($row[$this->_coreInfoTable . '_' . $this->_coreTypeCol])) {
        $rows[$rowNum][$this->_coreInfoTable . '_' . $this->_coreTypeCol] = $this->hexOne2str($rows[$rowNum][$this->_coreInfoTable . '_' . $this->_coreTypeCol]);
        $entryFound = TRUE;
      }

      // date of birth to age
      if (!empty($row['civicrm_contact_birth_date'])) {
        $rows[$rowNum]['civicrm_contact_birth_date'] = $this->dob2age($row['civicrm_contact_birth_date'] . " 00:00:00");
        $entryFound = TRUE;
      }

      // gender label
      if (!empty($row['civicrm_contact_gender_id'])) {
        $rows[$rowNum]['civicrm_contact_gender_id'] = $genderList[$row['civicrm_contact_gender_id']];
        $entryFound = TRUE;
      }

      //  Abbreviate party registration to first letter
      if (!empty($row["{$this->_voterInfoTable}_{$this->_partyCol}"])) {
        $rows[$rowNum]["{$this->_voterInfoTable}_{$this->_partyCol}"] = substr($row["{$this->_voterInfoTable}_{$this->_partyCol}"], 0, 1);
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }

    // make sure column order is same as in print mode
    $columnOrder = array(
      'civicrm_address_street_number',
      'civicrm_address_street_unit',
      'civicrm_contact_display_name',
      'civicrm_phone_phone',
      'civicrm_contact_birth_date',
      'civicrm_contact_gender_id',
      $this->_demoTable . '_' . $this->_demoLangCol,
      $this->_voterInfoTable . '_' . $this->_partyCol,
      $this->_voterInfoTable . '_' . $this->_vhCol,
      $this->_coreInfoTable . '_' . $this->_coreTypeCol,
      'civicrm_contact_id',
    );
    $tempHeaders = $this->_columnHeaders;
    $this->_columnHeaders = array();
    foreach ($columnOrder as $col) {
      if (array_key_exists($col, $tempHeaders)) {
        $this->_columnHeaders[$col] = $tempHeaders[$col];
        unset($tempHeaders[$col]);
      }
    }
    $this->_columnHeaders = $this->_columnHeaders + $tempHeaders;
  }

  function executePrintmode($rows) {
    //only get these last contribution related variables in print mode if selected on previous form
    if (array_key_exists('civicrm_contribution_cont_receive_date', $rows[0])) {
      $receiveDate = ', date_received   DATE';
    }
    if (array_key_exists('civicrm_contribution_cont_total_amount', $rows[0])) {
      $contAmount = ' , total_amount FLOAT';
    }
    //  Separate out fields and build a temporary table
    $tempTable = "WalkList_" . uniqid();
    $sql = "CREATE TEMPORARY TABLE {$tempTable}" . " ( id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                  street_name     VARCHAR(255),
                  s_street_number VARCHAR(32),
                  i_street_number INT,
                  odd             TINYINT,
                  apt_number      VARCHAR(32),
                  city            VARCHAR(64),
                  state           VARCHAR(32),
                  zip             VARCHAR(32),
                  name            VARCHAR(255),
                  phone           VARCHAR(255),
                  age             INT,
                  sex             VARCHAR(16),
                  lang            CHAR(2),
                  party           CHAR(1),
                  vh              CHAR(1),
                  contact_type    VARCHAR(128),
                  other_name      VARCHAR(128),
                  contact_id      INT
                  $receiveDate $contAmount
                  )
                 ENGINE=HEAP
                 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($sql);

    $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');

    foreach ($rows as $key => $value) {

      $dob  = $value['civicrm_contact_birth_date'];
      $age  = empty($dob) ? 0 : $this->dob2age($dob);
      if (!empty($value['civicrm_contact_gender_id'])){
        $sex  = $gender[CRM_Utils_Array::value('civicrm_contact_gender_id', $value)];
      }
      $sex  = empty($sex) ? '' : $sex;
      $lang = strtoupper(substr($value[$this->_demoTable . '_' . $this->_demoLangCol], 0, 2
        ));
      $party       = substr($value["{$this->_voterInfoTable}_{$this->_partyCol}"], 0, 1);
      $vh          = substr($value["{$this->_voterInfoTable}_{$this->_vhCol}"], 0, 1);
      $contactType = $value[$this->_coreInfoTable . '_' . $this->_coreTypeCol];
      $on          = $value[$this->_coreInfoTable . '_' . $this->_coreOtherCol];
      $otherName   = empty($on) ? 0 : "'{$on}'";
      $type        = '';
      if (!empty($contactType)) {
        $type = $this->hexOne2str($contactType);
      }
      $contact_id = (int)$value['civicrm_contact_id'];

      $state = '';
      if (!empty($value['civicrm_address_state_province_id'])) {
        $state = CRM_Core_PseudoConstant::stateProvince(
          $value['civicrm_address_state_province_id']
        );
      }

      $sStreetNumber = $value['civicrm_address_street_number'];
      $iStreetNumber = $value['civicrm_address_street_number'] ? (int)$value['civicrm_address_street_number'] : 0;
      $odd           = $value['civicrm_address_street_number'] ? ((int)$value['civicrm_address_street_number'] % 2) : 0;
      $apt_number    = $value['civicrm_address_street_number'] ? $value['civicrm_address_street_number'] : '';
      $phone_number  = $value['civicrm_phone_phone'] ? $value['civicrm_phone_phone'] : '';
      $query         = "INSERT INTO {$tempTable} SET
                       street_name     = %1,
                       s_street_number = %2,
                       i_street_number = %3,
                       odd             = %4,
                       apt_number      = %5,
                       city            = %6,
                       state           = %7,
                       zip             = %8,
                       name            = %9,
                       phone           = %10,
                       age             = %11,
                       sex             = %12,
                       lang            = %13,
                       party           = %14,
                       vh              = %15,
                       contact_type    = %16,
                       other_name      = %17,
                       contact_id      = %18";
      $params = array(
        1 => array($value['civicrm_address_street_name'] ? $value['civicrm_address_street_name'] : '', 'String'),
        2 => array((String )$sStreetNumber, 'String'),
        3 => array($iStreetNumber, 'Integer'),
        4 => array($odd, 'Integer'),
        5 => array((String) $apt_number , 'String'),
        6 => array($value['civicrm_address_city'] ? $value['civicrm_address_city'] : '', 'String'),
        7 => array((String) $state , 'String'),
        8 => array($value['civicrm_address_postal_code'] ? $value['civicrm_address_postal_code'] : '', 'String'),
        9 => array($value['civicrm_contact_display_name'] ? $value['civicrm_contact_display_name'] : '', 'String'),
        10 => array((String)  $phone_number, 'String'),
        11 => array($age, 'Integer'),
        12 => array((String) $sex, 'String'),
        13 => array((String) $lang, 'String'),
        14 => array((String) $party, 'String'),
        15 => array((String) $vh, 'String'),
        16 => array((String) $type, 'String'),
        17 => array((String) $otherName, 'String'),
        18 => array((String) $contact_id, 'Integer'),
      );

      if (!empty($contAmount)) {
        $query       .= ", total_amount = %19";
        $total_amount = $value['civicrm_contribution_cont_total_amount'] ? $value['civicrm_contribution_cont_total_amount'] : 0;
        $params[19]   = array($total_amount, 'Money');
      }
      if (!empty($receiveDate)) {
        $query        .= ",date_received  = %20";
        $date_received = $value['civicrm_contribution_cont_receive_date'] ? CRM_Utils_Date::isoToMysql($value['civicrm_contribution_cont_receive_date']) : NULL;
        $params[20]    = array($date_received, 'Timestamp');
      }
      CRM_Core_DAO::executeQuery($query, $params);
    }

    //  With the data normalized and in a table, we can
    //  retrieve it in the order we need to present it
    $query = "SELECT * FROM {$tempTable} ORDER BY state, city, zip,
                  street_name, odd, i_street_number, apt_number";
    $dao = CRM_Core_DAO::executeQuery($query);

    //  Initialize output state
    $first       = TRUE;
    $state       = '';
    $city        = '';
    $zip         = '';
    $street_name = '';
    $odd         = '';
    $pageRow     = 0;
    $reportDate  = date('F j, Y');

    $pdfRows     = array();
    $groupRows   = array();
    $groupCounts = 0;

    $pdfHeaders = array('s_street_number' => array('title' => 'STREET#'),
      'apt_number' => array('title' => 'APT'),
      'name' => array('title' => 'Name'),
      'phone' => array('title' => 'PHONE'),
      'age' => array('title' => 'AGE'),
      'sex' => array('title' => 'SEX'),
      'lang' => array('title' => 'Lang'),
      'party' => array('title' => 'Party'),
      'vh' => array('title' => 'VH'),
      'contact_type' => array('title' => 'Constituent Type'),
      'note' => array('title' => 'NOTES'),
      'rcode' => array('title' => 'RESPONSE CODES'),
      'status' => array('title' => 'STATUS'),
      'contact_id' => array(
        'title' => 'ID',
        'class' => 'width=7%',
      ),
    );

    if (variable_get('civicrm_engage_groupbreak_street', "1") != 1) {
      $pdfHeaders['street_name']['title'] = 'Street';
    }
    if ($receiveDate) {
      $pdfHeaders['date_received'] = array('title' => 'Last donation Date');
    }
    if ($contAmount) {
      $pdfHeaders['total_amount'] = array('title' => 'Last donation');
    }


    $groupInfo = array(
      'date' => $reportDate,
      'descr' => empty($this->_groupDescr) ? '' : "<br>Group {$this->_groupDescr}",
    );


    while ($dao->fetch()) {

      if (strtolower($state) != strtolower($dao->state)
        || (variable_get('civicrm_engage_groupbreak_city', "1") == 1 && strtolower($city) != strtolower($dao->city))
        || (variable_get('civicrm_engage_groupbreak_zip', "1") == 1 && strtolower($zip) != strtolower($dao->zip))
        || (variable_get('civicrm_engage_groupbreak_street', "1") == 1 && strtolower($street_name) != strtolower($dao->street_name))
        || (variable_get('civicrm_engage_groupbreak_odd_even', "1") == 1 && $odd != $dao->odd)
        || $pageRow > variable_get('civicrm_engage_lines_per_group', "6") - 1
      ) {
        $state       = $dao->state;
        $city        = $dao->city;
        $zip         = $dao->zip;
        $street_name = $dao->street_name;
        $odd         = $dao->odd;
        $pageRow     = 0;
        $groupRow['city_zip'] = '';
        $groupRow['org'] = $this->_orgName;
        if (variable_get('civicrm_engage_groupbreak_street', "1") == 1) {
          $groupRow['street_name'] = $street_name;
        }
        if (variable_get('civicrm_engage_groupbreak_city', "1") == 1) {
          $groupRow['city_zip'] .= $city . ', ';
        }
        $groupRow['city_zip'] .= $state;
        //don't give zip or odd-even if not grouped on
        if (variable_get('civicrm_engage_groupbreak_zip', "1") == 1) {
          $groupRow['city_zip'] .= ' ' . $zip;
        }
        if (variable_get('civicrm_engage_groupbreak_odd_even', "1") == 1) {
          $groupRow['odd'] = $odd ? 'Odd' : 'Even';
        }
        $groupCounts++;
        $groupRows[$groupCounts] = $groupRow;
      }

      // if admin settings have been defined to specify not to canvas people for a period change date to specified text
      if (variable_get('civicrm_engage_no_canvas_period', "0") > 0 && $dao->date_received > 0
        && ((strtotime("now") - strtotime($dao->date_received)) / 60 / 60 / 24 / 30) < variable_get('civicrm_engage_no_canvas_period', "0")
      ) {
        $dao->date_received = variable_get('civicrm_engage_no_canvass_text', "Do Not Canvass");
      }

      $pdfRow = array();
      foreach ($pdfHeaders as $k => $v) {
        if (property_exists($dao, $k)) {
          if ($k == 'name' && $dao->other_name) {
            $pdfRow[$k] = $dao->$k . "<br />" . $dao->other_name;
            continue;
          }
          $pdfRow[$k] = $dao->$k;
        }
        else {
          $pdfRow[$k] = "";
        }
      }

      $pdfRows[$groupCounts][] = $pdfRow;

      $pageRow++;
    }
    if (variable_get('civicrm_engage_group_per_page', "1")) {
      $this->assign('newgroupdiv', 'class="page"');
    }
    $this->assign('pageTotal', $groupCounts);
    $this->assign('pdfHeaders', $pdfHeaders);
    $this->assign('groupInfo', $groupInfo);
    $this->assign('pdfRows', $pdfRows);
    $this->assign('groupRows', $groupRows);
  }
}

