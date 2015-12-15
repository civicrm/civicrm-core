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
 */

/**
 * This class provides the common functionality for sending email to one or a group of contact ids.
 */
class CRM_Contact_Form_Task_LabelCommon {

  /**
   * Create labels (pdf).
   *
   * @param array $contactRows
   *   Associated array of contact data.
   * @param string $format
   *   Format in which labels needs to be printed.
   * @param string $fileName
   *   The name of the file to save the label in.
   */
  public static function createLabel(&$contactRows, &$format, $fileName = 'MailingLabels_CiviCRM.pdf') {
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
   * Get the rows for the labels.
   *
   * @param $contactIDs
   * @param int $locationTypeID
   * @param bool $respectDoNotMail
   * @param $mergeSameAddress
   * @param bool $mergeSameHousehold
   *   UNUSED.
   *
   * @return array
   *   Array of rows for labels
   */
  public static function getRows($contactIDs, $locationTypeID, $respectDoNotMail, $mergeSameAddress, $mergeSameHousehold) {
    $locName = NULL;
    //get the address format sequence from the config file
    $addressReturnProperties = CRM_Contact_Form_Task_LabelCommon::getAddressReturnProperties();

    //build the return properties
    $returnProperties = array('display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1);
    $mailingFormat = Civi::settings()->get('mailing_format');

    $mailingFormatProperties = array();
    if ($mailingFormat) {
      $mailingFormatProperties = CRM_Utils_Token::getReturnProperties($mailingFormat);
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
        '=',
        1,
        0,
        0,
      );
    }

    // fix for CRM-2651
    if (!empty($respectDoNotMail['do_not_mail'])) {
      $params[] = array('do_not_mail', '=', 0, 0, 0);
    }
    // fix for CRM-2613
    $params[] = array('is_deceased', '=', 0, 0, 0);

    if ($locationTypeID) {
      $locType = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
      $locName = $locType[$locationTypeID];
      $location = array('location' => array("{$locName}" => $addressReturnProperties));
      $returnProperties = array_merge($returnProperties, $location);
      $params[] = array('location_type', '=', array($locationTypeID => 1), 0, 0);
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
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    $details = $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);

    $messageToken = CRM_Utils_Token::getTokens($mailingFormat);
    $details = $details[0];
    $tokenFields = CRM_Contact_Form_Task_LabelCommon::getTokenData($details);

    foreach ($contactIDs as $value) {
      foreach ($custom as $cfID) {
        if (isset($details[$value]["custom_{$cfID}"])) {
          $details[$value]["custom_{$cfID}"] = CRM_Core_BAO_CustomField::displayValue($details[$value]["custom_{$cfID}"], $cfID);
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

        if (!CRM_Contact_Form_Task_Label::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
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
            if (CRM_Utils_Array::value('location_type_id', $vals) ==
              $locationTypeID
            ) {
              foreach ($vals as $k => $v) {
                if (in_array($k, array(
                  'email',
                  'phone',
                  'im',
                  'openid',
                ))) {
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
        if (!CRM_Contact_Form_Task_Label::tokenIsFound($contact, $mailingFormatProperties, $tokenFields)) {
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
   * Get array of return properties for address fields required for mailing label.
   *
   * @return array
   *   return properties for address e.g
   *   [street_address => 1, supplemental_address_1 => 1, supplemental_address_2 => 1]
   */
  public static function getAddressReturnProperties() {
    $mailingFormat = Civi::settings()->get('mailing_format');

    $addressFields = CRM_Utils_Address::sequence($mailingFormat);
    $addressReturnProperties = array_fill_keys($addressFields, 1);

    if (array_key_exists('postal_code', $addressReturnProperties)) {
      $addressReturnProperties['postal_code_suffix'] = 1;
    }
    return $addressReturnProperties;
  }

  /**
   * Get token list from mailing format & contacts
   * @param array $contacts
   * @return array
   */
  public static function getTokenData(&$contacts) {
    $mailingFormat = Civi::settings()->get('mailing_format');
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
   * @param array $rows
   *
   * @return array
   */
  public function mergeSameHousehold(&$rows) {
    // group selected contacts by type
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

    // exclude individuals belonging to selected households
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

    // merge back individuals and households
    $rows = array_merge($individuals, $households);
    return $rows;
  }

}
