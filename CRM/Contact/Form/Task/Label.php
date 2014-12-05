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
 * This class helps to print the labels for contacts
 *
 */
class CRM_Contact_Form_Task_Label extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $this->set('contactIds', $this->_contactIds);
    parent::preProcess();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Make Mailing Labels'));

    //add select for label
    $label = CRM_Core_BAO_LabelFormat::getList(TRUE);

    $this->add('select', 'label_name', ts('Select Label'), array('' => ts('- select label -')) + $label, TRUE);


    // add select for Location Type
    $this->addElement('select', 'location_type_id', ts('Select Location'),
      array(
        '' => ts('Primary')) + CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'), TRUE
    );

    // checkbox for SKIP contacts with Do Not Mail privacy option
    $this->addElement('checkbox', 'do_not_mail', ts('Do not print labels for contacts with "Do Not Mail" privacy option checked'));

    $this->add('checkbox', 'merge_same_address', ts('Merge labels for contacts with the same address'), NULL);
    $this->add('checkbox', 'merge_same_household', ts('Merge labels for contacts belonging to the same household'), NULL);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Make Mailing Labels'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Done'),
      ),
    ));
  }

  /**
   * This function sets the default values for the form.
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = array();
    $format = CRM_Core_BAO_LabelFormat::getDefaultValues();
    $defaults['label_name'] = CRM_Utils_Array::value('name', $format);
    $defaults['do_not_mail'] = 1;

    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $fv      = $this->controller->exportValues($this->_name);
    $config  = CRM_Core_Config::singleton();
    $locName = NULL;
    //get the address format sequence from the config file
    $mailingFormat = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'mailing_format'
    );

    $sequence = CRM_Utils_Address::sequence($mailingFormat);

    foreach ($sequence as $v) {
      $address[$v] = 1;
    }

    if (array_key_exists('postal_code', $address)) {
      $address['postal_code_suffix'] = 1;
    }

    //build the returnproperties
    $returnProperties = array('display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1);
    $mailingFormat = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'mailing_format'
    );

    $mailingFormatProperties = array();
    if ($mailingFormat) {
      $mailingFormatProperties = self::getReturnProperties($mailingFormat);
      $returnProperties = array_merge($returnProperties, $mailingFormatProperties);
    }
    //we should not consider addressee for data exists, CRM-6025
    if (array_key_exists('addressee', $mailingFormatProperties)) {
      unset($mailingFormatProperties['addressee']);
    }

    $customFormatProperties = array();
    if (stristr($mailingFormat, 'custom_')) {
      foreach ($mailingFormatProperties as $token => $true) {
        if (substr($token, 0, 7) == 'custom_') {
          if (empty($customFormatProperties[$token])) {
            $customFormatProperties[$token] = $mailingFormatProperties[$token];
          }
        }
      }
    }

    if (!empty($customFormatProperties)) {
      $returnProperties = array_merge($returnProperties, $customFormatProperties);
    }

    if (isset($fv['merge_same_address'])) {
      // we need first name/last name for summarising to avoid spillage
      $returnProperties['first_name'] = 1;
      $returnProperties['last_name'] = 1;
    }

    $individualFormat = FALSE;

    /*
     * CRM-8338: replace ids of household members with the id of their household
     * so we can merge labels by household.
     */
    if (isset($fv['merge_same_household'])) {
      $this->mergeContactIdsByHousehold();
      $individualFormat = TRUE;
    }

    //get the contacts information
    $params = array();
    if (!empty($fv['location_type_id'])) {
      $locType          = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $locName          = $locType[$fv['location_type_id']];
      $location         = array('location' => array("{$locName}" => $address));
      $returnProperties = array_merge($returnProperties, $location);
      $params[]         = array('location_type', '=', array($fv['location_type_id'] => 1), 0, 0);
    }
    else {
      $returnProperties = array_merge($returnProperties, $address);
    }

    $rows = array();

    foreach ($this->_contactIds as $key => $contactID) {
      $params[] = array(
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=', 1, 0, 0,
      );
    }

    // fix for CRM-2651
    if (!empty($fv['do_not_mail'])) {
      $params[] = array('do_not_mail', '=', 0, 0, 0);
    }
    // fix for CRM-2613
    $params[] = array('is_deceased', '=', 0, 0, 0);

    $custom = array();
    foreach ($returnProperties as $name => $dontCare) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if ($cfID) {
        $custom[] = $cfID;
      }
    }

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($this->_contactIds);
    $query            = new CRM_Contact_BAO_Query($params, $returnProperties);
    $details          = $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);

    $messageToken = CRM_Utils_Token::getTokens($mailingFormat);

    // also get all token values
    CRM_Utils_Hook::tokenValues($details[0],
      $this->_contactIds,
      NULL,
      $messageToken,
      'CRM_Contact_Form_Task_Label'
    );

    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $tokenFields = array();
    foreach ($tokens as $category => $catTokens) {
      foreach ($catTokens as $token => $tokenName) {
        $tokenFields[] = $token;
      }
    }

    foreach ($this->_contactIds as $value) {
      foreach ($custom as $cfID) {
        if (isset($details[0][$value]["custom_{$cfID}"])) {
          $details[0][$value]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::getDisplayValue($details[0][$value]["custom_{$cfID}"], $cfID, $details[1]);
        }
      }
      $contact = CRM_Utils_Array::value($value, $details['0']);

      if (is_a($contact, 'CRM_Core_Error')) {
        return NULL;
      }

      // we need to remove all the "_id"
      unset($contact['contact_id']);

      if ($locName && !empty($contact[$locName])) {
        // If location type is not primary, $contact contains
        // one more array as "$contact[$locName] = array( values... )"

        if(!$this->tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
          continue;
        }

        $contact = array_merge($contact, $contact[$locName]);
        unset($contact[$locName]);

        if (!empty($contact['county_id'])) {
          unset($contact['county_id']);
        }

        foreach ($contact as $field => $fieldValue) {
          $rows[$value][$field] = $fieldValue;
        }

        $valuesothers = array();
        $paramsothers = array('contact_id' => $value);
        $valuesothers = CRM_Core_BAO_Location::getValues($paramsothers, $valuesothers);
        if (!empty($fv['location_type_id'])) {
          foreach ($valuesothers as $vals) {
                        if ( CRM_Utils_Array::value('location_type_id', $vals) ==
                             CRM_Utils_Array::value('location_type_id', $fv ) ) {
              foreach ($vals as $k => $v) {
                if (in_array($k, array(
                  'email', 'phone', 'im', 'openid'))) {
                  if ($k == 'im') {
                    $rows[$value][$k] = $v['1']['name'];
                  }
                  else {
                    $rows[$value][$k] = $v['1'][$k];
                  }
                  $rows[$value][$k . '_id'] = $v['1']['id'];
                }
              }
            }
          }
        }
      }
      else {
        if(!$this->tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
          continue;
        }

        if (!empty($contact['addressee_display'])) {
          $contact['addressee_display'] = trim($contact['addressee_display']);
        }
        if (!empty($contact['addressee'])) {
          $contact['addressee'] = $contact['addressee_display'];
        }

        // now create the rows for generating mailing labels
        foreach ($contact as $field => $fieldValue) {
          $rows[$value][$field] = $fieldValue;
        }
      }
    }

    if (isset($fv['merge_same_address'])) {
      $this->mergeSameAddress($rows);
      $individualFormat = TRUE;
    }

    // format the addresses according to CIVICRM_ADDRESS_FORMAT (CRM-1327)
    foreach ($rows as $id => $row) {
      if ($commMethods = CRM_Utils_Array::value('preferred_communication_method', $row)) {
        $val  = array_filter(explode(CRM_Core_DAO::VALUE_SEPARATOR, $commMethods));
        $comm = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method');
        $temp = array();
        foreach ($val as $vals) {
          $temp[] = $comm[$vals];
        }
        $row['preferred_communication_method'] = implode(', ', $temp);
      }
      $row['id'] = $id;
      $formatted = CRM_Utils_Address::format($row, 'mailing_format', FALSE, TRUE, $individualFormat, $tokenFields);

      // CRM-2211: UFPDF doesn't have bidi support; use the PECL fribidi package to fix it.
      // On Ubuntu (possibly Debian?) be aware of http://pecl.php.net/bugs/bug.php?id=12366
      // Due to FriBidi peculiarities, this can't be called on
      // a multi-line string, hence the explode+implode approach.
      if (function_exists('fribidi_log2vis')) {
        $lines = explode("\n", $formatted);
        foreach ($lines as $i => $line) {
          $lines[$i] = fribidi_log2vis($line, FRIBIDI_AUTO, FRIBIDI_CHARSET_UTF8);
        }
        $formatted = implode("\n", $lines);
      }
      $rows[$id] = array($formatted);
    }

    //call function to create labels
    self::createLabel($rows, $fv['label_name']);
    CRM_Utils_System::civiExit(1);
  }

  /**
   * Check for presence of tokens to be swapped out
   *
   * @param array $contact
   * @param array $mailingFormatProperties
   * @param array $tokenFields
   *
   * @return bool
   */
  function tokenIsFound($contact, $mailingFormatProperties, $tokenFields) {
    foreach (array_merge($mailingFormatProperties, array_fill_keys($tokenFields, 1)) as $key => $dontCare) {
      if (!empty($contact[$key])) {
        return TRUE;
      }
    }
    return FALSE;
  }
  /**
   * function to create labels (pdf)
   *
   * @param   array    $contactRows   assciated array of contact data
   * @param   string   $format   format in which labels needs to be printed
   * @param   string   $fileName    The name of the file to save the label in
   *
   * @return  null
   * @access  public
   */
  function createLabel(&$contactRows, &$format, $fileName = 'MailingLabels_CiviCRM.pdf') {
    $pdf = new CRM_Utils_PDF_Label($format, 'mm');
    $pdf->Open();
    $pdf->AddPage();

    //build contact string that needs to be printed
    $val = NULL;
    foreach ($contactRows as $row => $value) {
      foreach ($value as $k => $v) {
        $val .= "$v\n";
      }

      $pdf->AddPdfLabel($val);
      $val = '';
    }
    $pdf->Output($fileName, 'D');
  }

  /**
   * function to create the array of returnProperties
   *
   * @param   string   $format   format for which return properties build
   *
   * @return array of returnProperties
   * @access  public
   */
  function getReturnProperties(&$format) {
    $returnProperties = array();
    $matches = array();
    preg_match_all('/(?<!\{|\\\\)\{(\w+\.\w+)\}(?!\})/',
      $format,
      $matches,
      PREG_PATTERN_ORDER
    );
    if ($matches[1]) {
      foreach ($matches[1] as $token) {
        list($type, $name) = preg_split('/\./', $token, 2);
        if ($name) {
          $returnProperties["{$name}"] = 1;
        }
      }
    }

    return $returnProperties;
  }

  /**
   * @param $rows
   */
  function mergeSameAddress(&$rows) {
    $uniqueAddress = array();
    foreach (array_keys($rows) as $rowID) {
      // load complete address as array key
      $address =
      trim($rows[$rowID]['street_address']) . trim($rows[$rowID]['city']) . trim($rows[$rowID]['state_province']) . trim($rows[$rowID]['postal_code']) . trim($rows[$rowID]['country']);
      if (isset($rows[$rowID]['last_name'])) {
        $name = $rows[$rowID]['last_name'];
      }
      else {
        $name = $rows[$rowID]['display_name'];
      }

      // CRM-15120
      $formatted = array(
        'first_name' => $rows[$rowID]['first_name'],
        'individual_prefix' => $rows[$rowID]['individual_prefix']
      );
      $format = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'display_name_format');
      $firstNameWithPrefix = CRM_Utils_Address::format($formatted, $format, FALSE, FALSE, TRUE);
      $firstNameWithPrefix = trim($firstNameWithPrefix);

      // fill uniqueAddress array with last/first name tree
      if (isset($uniqueAddress[$address])) {
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
        // drop unnecessary rows
        unset($rows[$rowID]);
        // this is the first listing at this address
      }
      else {
        $uniqueAddress[$address]['ID'] = $rowID;
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['first_name'] = $rows[$rowID]['first_name'];
        $uniqueAddress[$address]['names'][$name][$firstNameWithPrefix]['addressee_display'] = $rows[$rowID]['addressee_display'];
      }
    }
    foreach ($uniqueAddress as $address => $data) {
      // copy data back to $rows
      $count = 0;
      // one last name list per row
      foreach ($data['names'] as $last_name => $first_names) {
        // too many to list
        if ($count > 2) {
          break;
        }
          if(count($first_names) == 1){
            $family = $first_names[current(array_keys($first_names))]['addressee_display'];
          }
          else {
            // collapse the tree to summarize
            $family = trim(implode(" & ", array_keys($first_names)) . " " . $last_name);
          }
          if ($count) {
            $processedNames .= "\n" . $family;
          }
          else {
            // build display_name string
            $processedNames = $family;
          }
        $count++;
      }
      $rows[$data['ID']]['addressee'] = $rows[$data['ID']]['addressee_display'] = $rows[$data['ID']]['display_name'] = $processedNames;
    }
  }
}

