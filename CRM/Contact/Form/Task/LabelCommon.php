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
 * This class provides the common functionality for sending email to
 * one or a group of contact ids. This class is reused by all the search
 * components in CiviCRM (since they all have send email as a task)
 */
class CRM_Contact_Form_Task_LabelCommon {
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
      //we should not consider addressee for data exists, CRM-6025
       if ($key != 'addressee' && !empty($contact[$key])) {
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
  static function createLabel(&$contactRows, &$format, $fileName = 'MailingLabels_CiviCRM.pdf') {
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
   * function to get the rows for the labels
   *
   * @param $contactIDs
   * @param integer $locationTypeID
   * @param boolean $respectDoNotMail
   * @param $mergeSameAddress
   * @param $mergeSameHousehold
   *
   * @internal param array $contactIds Contact IDS to do labels for
   * @return array of rows for labels
   * @access  public
   */

  static function getRows($contactIDs, $locationTypeID, $respectDoNotMail, $mergeSameAddress, $mergeSameHousehold) {
    $locName = NULL;
    //get the address format sequence from the config file
    $addressReturnProperties = CRM_Contact_Form_Task_LabelCommon::getAddressReturnProperties();

    //build the returnproperties
    $returnProperties = array('display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1);
    $mailingFormat = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'mailing_format'
    );

    $mailingFormatProperties = array();
    if ($mailingFormat) {
      $mailingFormatProperties = CRM_Contact_Form_Task_LabelCommon::regexReturnProperties($mailingFormat);
      $returnProperties = array_merge($returnProperties, $mailingFormatProperties);
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
    $returnProperties = array_merge($returnProperties, $customFormatProperties);

    if ($mergeSameAddress) {
      // we need first name/last name for summarising to avoid spillage
      $returnProperties['first_name'] = 1;
      $returnProperties['last_name'] = 1;
    }

    //get the contacts information
    $params = $custom = array();
    foreach ($contactIDs as $key => $contactID) {
      $params[] = array(
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=', 1, 0, 0,
      );
    }

    // fix for CRM-2651
    if (!empty($respectDoNotMail['do_not_mail'])) {
      $params[] = array('do_not_mail', '=', 0, 0, 0);
    }
    // fix for CRM-2613
    $params[] = array('is_deceased', '=', 0, 0, 0);

    if ($locationTypeID) {
      $locType          = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $locName          = $locType[$locationTypeID];
      $location         = array('location' => array("{$locName}" => $addressReturnProperties));
      $returnProperties = array_merge($returnProperties, $location);
      $params[]         = array('location_type', '=', array($locationTypeID => 1), 0, 0);
    }
    else {
      $returnProperties = array_merge($returnProperties, $addressReturnProperties);
    }

    foreach ($returnProperties as $name) {
      $cfID = CRM_Core_BAO_CustomField::getKeyID($name);
      if ($cfID) {
        $custom[] = $cfID;
      }
    }

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($contactIDs);
    //this does the same as calling civicrm_api3('contact, get, array('id' => array('IN' => $this->_contactIds)
    // except it also handles multiple locations
    $query            = new CRM_Contact_BAO_Query($params, $returnProperties);
    $details          = $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);

    $messageToken = CRM_Utils_Token::getTokens($mailingFormat);
    $details = $details[0];
    $tokenFields = CRM_Contact_Form_Task_LabelCommon::getTokenData($details);

    foreach ($contactIDs as $value) {
      foreach ($custom as $cfID) {
        if (isset($details[$value]["custom_{$cfID}"])) {
          $details[$value]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::getDisplayValue($details[$value]["custom_{$cfID}"], $cfID, $details[1]);
        }
      }
      $contact = CRM_Utils_Array::value($value, $details);

      if (is_a($contact, 'CRM_Core_Error')) {
        return NULL;
      }

      // we need to remove all the "_id"
      unset($contact['contact_id']);

      if ($locName && !empty($contact[$locName])) {
        // If location type is not primary, $contact contains
        // one more array as "$contact[$locName] = array( values... )"

        if(!CRM_Contact_Form_Task_LabelCommon::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
          continue;
        }

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
        if ($locationTypeID) {
          foreach ($valuesothers as $vals) {
            if ( CRM_Utils_Array::value('location_type_id', $vals) ==
              $locationTypeID) {
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
        if(!CRM_Contact_Form_Task_LabelCommon::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
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
    // sigh couldn't extract out tokenfields yet
    return array($rows, $tokenFields);
  }

  /**
   * function to extract the return properties from the mailing format
   * @todo I'm placing bets this is a duplicate of code elsewhere - find & merge
   * @param unknown_type $format
   * @return multitype:number
   */
  function regexReturnProperties(&$format) {
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
   * Get array of return properties for address fields required for mailing label
   * @return array return properites for address e.g
   * array (
   *  - [street_address] => 1,
   * -  [supplemental_address_1] => 1,
   * -  [supplemental_address_2] => 1
   * )
   */
  function getAddressReturnProperties() {
    $mailingFormat = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'mailing_format'
    );

    $addressFields = CRM_Utils_Address::sequence($mailingFormat);
    $addressReturnProperties = array_fill_keys($addressFields, 1);

    if (array_key_exists('postal_code', $addressReturnProperties)) {
      $addressReturnProperties['postal_code_suffix'] = 1;
    }
    return $addressReturnProperties;
  }

  /**
   * Get token list from mailing format & contacts
   * @param unknown_type $contacts
   * @return unknown
   */
  function getTokenData(&$contacts) {
    $mailingFormat = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'mailing_format'
    );
    $tokens = $tokenFields = array();
    $messageToken = CRM_Utils_Token::getTokens($mailingFormat);

    // also get all token values
    CRM_Utils_Hook::tokenValues($contacts,
      array_keys($contacts),
      NULL,
      $messageToken,
      'CRM_Contact_Form_Task_LabelCommon'
     );

    CRM_Utils_Hook::tokens($tokens);

    foreach ($tokens as $category => $catTokens) {
      foreach ($catTokens as $token => $tokenName) {
        $tokenFields[] = $token;
      }
    }
    return $tokenFields;

  }
  /**
   * Merge contacts with the Same address to get one shared label
   * @param unknown_type $rows
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
      $formatted = array(
       'first_name' => $rows[$rowID]['first_name'],
       'individual_prefix' => $rows[$rowID]['individual_prefix']
      );
      $format = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'display_name_format');
      $firstNameWithPrefix = CRM_Utils_Address::format($formatted, $format,  FALSE, FALSE, TRUE);
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
        if(count($first_names) == 1) {
          $family = $first_names[current(array_keys($first_names))]['addressee_display'];
        }
        else {
          // collapse the tree to summarize
          $family = trim(implode(" & ", $first_names) . " " . $last_name);
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

  /**
   * @param $rows
   *
   * @return array
   */
  function mergeSameHousehold(&$rows) {
    # group selected contacts by type
    $individuals = array();
    $households = array();
    foreach ($rows as $contact_id => $row) {
      if ($row['contact_type'] == 'Household') {
        $households[$contact_id] = $row;
      }
      elseif ($row['contact_type'] == 'Individual') {
        $individuals[$contact_id] = $row;
      }
    }

    # exclude individuals belonging to selected households
    foreach ($households as $household_id => $row) {
    $dao = new CRM_Contact_DAO_Relationship();
    $dao->contact_id_b = $household_id;
    $dao->find();
    while ($dao->fetch()) {
    $individual_id = $dao->contact_id_a;
    if (array_key_exists($individual_id, $individuals)) {
    unset($individuals[$individual_id]);
    }
    }
    }

    # merge back individuals and households
    $rows = array_merge($individuals, $households);
    return $rows;
    }
}
