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
  public static function createLabel($contactRows, $format, $fileName = 'MailingLabels_CiviCRM.pdf') {
    if (CIVICRM_UF === 'UnitTests') {
      throw new CRM_Core_Exception_PrematureExitException('civiExit called', ['rows' => $contactRows, 'format' => $format, 'file_name' => $fileName]);
    }
    $pdf = new CRM_Utils_PDF_Label($format, 'mm');
    $pdf->Open();
    $pdf->AddPage();

    //build contact string that needs to be printed
    $val = NULL;
    foreach ((array) $contactRows as $row => $value) {
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
   * @param array $contactIDs
   * @param int $locationTypeID
   * @param bool $respectDoNotMail
   * @param bool $mergeSameAddress
   * @param bool $mergeSameHousehold
   *   UNUSED.
   *
   * @return array
   *   Array of rows for labels
   */
  public static function getRows($contactIDs, $locationTypeID, $respectDoNotMail, $mergeSameAddress, $mergeSameHousehold) {
    $locName = NULL;
    $rows = [];
    //get the address format sequence from the config file
    $addressReturnProperties = CRM_Contact_Form_Task_LabelCommon::getAddressReturnProperties();

    //build the return properties
    $returnProperties = ['display_name' => 1, 'contact_type' => 1, 'prefix_id' => 1];
    $mailingFormat = Civi::settings()->get('mailing_format');

    if ($mailingFormat) {
      $mailingFormatProperties = CRM_Utils_Token::getReturnProperties($mailingFormat);
      $returnProperties = array_merge($returnProperties, $mailingFormatProperties);
    }

    if ($mergeSameAddress) {
      // we need first name/last name for summarising to avoid spillage
      $returnProperties['first_name'] = 1;
      $returnProperties['last_name'] = 1;
    }

    //get the contacts information
    $params = $custom = [];
    foreach ($contactIDs as $key => $contactID) {
      $params[] = [
        CRM_Core_Form::CB_PREFIX . $contactID,
        '=',
        1,
        0,
        0,
      ];
    }

    // fix for CRM-2651
    if (!empty($respectDoNotMail['do_not_mail'])) {
      $params[] = ['do_not_mail', '=', 0, 0, 0];
    }
    // fix for CRM-2613
    $params[] = ['is_deceased', '=', 0, 0, 0];

    if ($locationTypeID) {
      $locType = CRM_Core_DAO_Address::buildOptions('location_type_id');
      $locName = $locType[$locationTypeID];
      $location = ['location' => ["{$locName}" => $addressReturnProperties]];
      $returnProperties = array_merge($returnProperties, $location);
      $params[] = ['location_type', '=', [$locationTypeID => 1], 0, 0];
    }
    else {
      $returnProperties = array_merge($returnProperties, $addressReturnProperties);
    }

    //get the total number of contacts to fetch from database.
    $numberofContacts = count($contactIDs);
    //this does the same as calling civicrm_api3('contact, get, array('id' => array('IN' => $this->_contactIds)
    // except it also handles multiple locations
    [$details] = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);

    foreach ($contactIDs as $value) {
      $contact = $details[$value] ?? NULL;

      // we need to remove all the "_id"
      unset($contact['contact_id']);

      if ($locName && !empty($contact[$locName])) {
        // If location type is not primary, $contact contains
        // one more array as "$contact[$locName] = array( values... )"

        unset($contact[$locName]);

        if (!empty($contact['county_id'])) {
          unset($contact['county_id']);
        }
      }
      else {

        if (!empty($contact['addressee_display'])) {
          $contact['addressee_display'] = trim($contact['addressee_display']);
        }
        if (!empty($contact['addressee'])) {
          $contact['addressee'] = $contact['addressee_display'];
        }
      }
      // now create the rows for generating mailing labels
      foreach ($contact as $field => $fieldValue) {
        $rows[$value][$field] = $fieldValue;
      }
    }
    // sigh couldn't extract out tokenfields yet
    return [$rows];
  }

  /**
   * Get array of return properties for address fields required for mailing label.
   *
   * @return array
   *   return properties for address e.g
   *   [street_address => 1, supplemental_address_1 => 1, supplemental_address_2 => 1]
   */
  public static function getAddressReturnProperties(): array {
    $mailingFormat = Civi::settings()->get('mailing_format');

    $addressFields = CRM_Utils_Address::sequence($mailingFormat);
    $addressReturnProperties = array_fill_keys($addressFields, 1);

    if (array_key_exists('postal_code', $addressReturnProperties)) {
      $addressReturnProperties['postal_code_suffix'] = 1;
    }
    return $addressReturnProperties;
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public static function mergeSameHousehold(&$rows) {
    // group selected contacts by type
    $individuals = [];
    $households = [];
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
