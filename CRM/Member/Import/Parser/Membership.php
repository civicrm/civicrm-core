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
 * class to parse membership csv files
 */
class CRM_Member_Import_Parser_Membership extends CRM_Import_Parser {

  /**
   * Array of metadata for all available fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * Array of successfully imported membership id's
   *
   * @var array
   */
  protected $_newMemberships;

  protected $_fileName;

  /**
   * Imported file size
   * @var int
   */
  protected $_fileSize;

  /**
   * Separator being used
   * @var string
   */
  protected $_separator;

  /**
   * Get information about the provided job.
   *  - name
   *  - id (generally the same as name)
   *  - label
   *
   *  e.g. ['activity_import' => ['id' => 'activity_import', 'label' => ts('Activity Import'), 'name' => 'activity_import']]
   *
   * @return array
   */
  public static function getUserJobInfo(): array {
    return [
      'membership_import' => [
        'id' => 'membership_import',
        'name' => 'membership_import',
        'label' => ts('Membership Import'),
        'entity' => 'Membership',
        'url' => 'civicrm/import/membership',

      ],
    ];
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   */
  public function getImportEntities() : array {
    return [
      'Membership' => ['text' => ts('Membership Fields'), 'is_contact' => FALSE],
      'Contact' => ['text' => ts('Contact Fields'), 'is_contact' => TRUE],
    ];
  }

  /**
   * Validate the values.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @throws \CRM_Core_Exception
   */
  public function validateValues($values): void {
    $params = $this->getMappedRow($values);
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }

    if (empty($params['membership_type_id'])) {
      $errors[] = ts('Missing required fields');
      return;
    }

    //To check whether start date or join date is provided
    if (empty($params['start_date']) && empty($params['join_date'])) {
      $errors[] = 'Membership Start Date is required to create a memberships.';
    }
    //fix for CRM-2219 Update Membership
    if ($this->isUpdateExisting() && !empty($params['is_override']) && empty($params['status_id'])) {
      $errors[] = 'Required parameter missing: Status';
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int|void|null
   *   the result of this processing - which is ignored
   */
  public function import($values) {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      if (!empty($params['contact_id'])) {
        $this->validateContactID($params['contact_id'], $this->getContactType());
      }

      //assign join date equal to start date if join date is not provided
      if (empty($params['join_date']) && !empty($params['start_date'])) {
        $params['join_date'] = $params['start_date'];
      }

      $formatted = $params;
      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;

      $formatValues = [];
      foreach ($params as $key => $field) {
        // ignore empty values or empty arrays etc
        if (CRM_Utils_System::isNull($field)) {
          continue;
        }

        $formatValues[$key] = $field;
      }

      require_once 'api/v3/utils.php';
      // It's very likely this line does nothing.
      _civicrm_api3_store_values(CRM_Member_DAO_Membership::fields(), $formatValues, $formatted);

      if (!$this->isUpdateExisting()) {
        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          NULL,
          'Membership'
        );
      }
      else {

        if (!empty($formatValues['membership_id'])) {
          $dao = new CRM_Member_BAO_Membership();
          $dao->id = $formatValues['membership_id'];
          $dates = ['join_date', 'start_date', 'end_date'];
          foreach ($dates as $v) {
            if (empty($formatted[$v])) {
              $formatted[$v] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $formatValues['membership_id'], $v);
            }
          }

          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
            $formatValues['membership_id'],
            'Membership'
          );
          if ($dao->find(TRUE)) {
            if (empty($params['line_item']) && !empty($formatted['membership_type_id'])) {
              CRM_Price_BAO_LineItem::getLineItemArray($formatted, NULL, 'membership', $formatted['membership_type_id']);
            }

            $newMembership = civicrm_api3('Membership', 'create', $formatted);
            $this->_newMemberships[] = $newMembership['id'];
            $this->setImportStatus($rowNumber, 'IMPORTED', 'Required parameter missing: Status');
            return CRM_Import_Parser::VALID;
          }
          throw new CRM_Core_Exception('Matching Membership record not found for Membership ID ' . $formatValues['membership_id'] . '. Row was skipped.', CRM_Import_Parser::ERROR);
        }
      }

      //Format dates
      $startDate = $formatted['start_date'];
      $endDate = $formatted['end_date'] ?? NULL;
      $joinDate = $formatted['join_date'];

      if (empty($formatValues['id']) && empty($formatValues['contact_id'])) {
        $error = $this->checkContactDuplicate($formatValues);

        if (CRM_Core_Error::isAPIError($error, CRM_Core_Error::DUPLICATE_CONTACT)) {
          $matchedIDs = (array) $error['error_message']['params'];
          if (count($matchedIDs) > 1) {
            throw new CRM_Core_Exception('Multiple matching contact records detected for this row. The membership was not imported', CRM_Import_Parser::ERROR);
          }
          else {
            $cid = $matchedIDs[0];
            $formatted['contact_id'] = $cid;

            //fix for CRM-1924
            $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($formatted['membership_type_id'],
              $joinDate,
              $startDate,
              $endDate
            );
            $this->formattedDates($calcDates, $formatted);

            //fix for CRM-3570, exclude the statuses those having is_admin = 1
            //now user can import is_admin if is override is true.
            $excludeIsAdmin = FALSE;
            if (empty($formatted['is_override'])) {
              $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
            }
            $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
              $endDate,
              $joinDate,
              'now',
              $excludeIsAdmin,
              $formatted['membership_type_id'],
              $formatted
            );

            if (empty($formatted['status_id'])) {
              $formatted['status_id'] = $calcStatus['id'];
            }
            elseif (empty($formatted['is_override'])) {
              if (empty($calcStatus)) {
                throw new CRM_Core_Exception('Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.', CRM_Import_Parser::ERROR);
              }
              if ($formatted['status_id'] != $calcStatus['id']) {
                //Status Hold" is either NOT mapped or is FALSE
                throw new CRM_Core_Exception('Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.', CRM_Import_Parser::ERROR);
              }
            }

            $newMembership = civicrm_api3('membership', 'create', $formatted);

            $this->_newMemberships[] = $newMembership['id'];
            $this->setImportStatus($rowNumber, 'IMPORTED', '');
            return CRM_Import_Parser::VALID;
          }
        }
        else {
          // Using new Dedupe rule.
          $ruleParams = [
            'contact_type' => $this->getContactType(),
            'used' => 'Unsupervised',
          ];
          $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);
          $disp = '';

          foreach ($fieldsArray as $value) {
            if (array_key_exists(trim($value), $params)) {
              $paramValue = $params[trim($value)];
              if (is_array($paramValue)) {
                $disp .= $params[trim($value)][0][trim($value)] . " ";
              }
              else {
                $disp .= $params[trim($value)] . " ";
              }
            }
          }

          if (!empty($params['external_identifier'])) {
            if ($disp) {
              $disp .= "AND {$params['external_identifier']}";
            }
            else {
              $disp = $params['external_identifier'];
            }
          }
          throw new CRM_Core_Exception('No matching Contact found for (' . $disp . ')', CRM_Import_Parser::ERROR);
        }
      }
      else {
        if (!empty($formatValues['external_identifier'])) {
          $checkCid = new CRM_Contact_DAO_Contact();
          $checkCid->external_identifier = $formatValues['external_identifier'];
          $checkCid->find(TRUE);
          if ($checkCid->id != $formatted['contact_id']) {
            throw new CRM_Core_Exception('Mismatch of External ID:' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id'], CRM_Import_Parser::ERROR);
          }
        }

        //to calculate dates
        $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($formatted['membership_type_id'],
          $joinDate,
          $startDate,
          $endDate
        );
        $this->formattedDates($calcDates, $formatted);
        //end of date calculation part

        //fix for CRM-3570, exclude the statuses those having is_admin = 1
        //now user can import is_admin if is override is true.
        $excludeIsAdmin = FALSE;
        if (empty($formatted['is_override'])) {
          $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
        }
        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
          $endDate,
          $joinDate,
          'now',
          $excludeIsAdmin,
          $formatted['membership_type_id'],
          $formatted
        );
        if (empty($formatted['status_id'])) {
          $formatted['status_id'] = $calcStatus['id'] ?? NULL;
        }
        elseif (empty($formatted['is_override'])) {
          if (empty($calcStatus)) {
            throw new CRM_Core_Exception('Status in import row (' . ($formatValues['status_id'] ?? '') . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.', CRM_Import_Parser::ERROR);
          }
          if ($formatted['status_id'] != $calcStatus['id']) {
            //Status Hold" is either NOT mapped or is FALSE
            throw new CRM_Core_Exception('Status in import row (' . ($formatValues['status_id'] ?? '') . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.', CRM_Import_Parser::ERROR);
          }
        }

        $newMembership = civicrm_api3('membership', 'create', $formatted);
        $this->setImportStatus($rowNumber, 'IMPORTED', '', $newMembership['id']);
        return CRM_Import_Parser::VALID;
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   *  to calculate join, start and end dates
   *
   * @param array $calcDates
   *   Array of dates returned by getDatesForMembershipType().
   *
   * @param $formatted
   *
   */
  public function formattedDates($calcDates, &$formatted) {
    $dates = [
      'join_date',
      'start_date',
      'end_date',
    ];

    foreach ($dates as $d) {
      if (isset($formatted[$d]) &&
        !CRM_Utils_System::isNull($formatted[$d])
      ) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($formatted[$d]);
      }
      elseif (isset($calcDates[$d])) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($calcDates[$d]);
      }
    }
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $metadata = $this->getImportableFields($this->getContactType());
      // We are consolidating on `importableFieldsMetadata` - but both still used.
      $this->importableFieldsMetadata = $this->fieldMetadata = $metadata;
    }
  }

  /**
   * @param string $contactType
   *
   * @return array|mixed
   * @throws \CRM_Core_Exception
   */
  protected function getImportableFields($contactType = 'Individual') {
    $fields = Civi::cache('fields')->get('membership_importable_fields' . $contactType);
    if (!$fields) {
      $fields = ['' => ['title' => '- ' . ts('do not import') . ' -']];

      $tmpFields = CRM_Member_DAO_Membership::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => $contactType,
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

      $tmpContactField = [];
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = trim($customFieldId ? 'custom_' . $customFieldId : $value);
          $tmpContactField[$value] = $contactFields[$value] ?? NULL;
          $title = $tmpContactField[$value]['title'] . ' ' . ts('(match to contact)');
          $tmpContactField[$value]['title'] = $title;
        }
      }
      $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' ' . ts('(match to contact)');

      $tmpFields['membership_contact_id']['title'] .= ' ' . ts('(match to contact)');

      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
      Civi::cache('fields')->set('membership_importable_fields' . $contactType, $fields);
    }
    return $fields;
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    $uniqueNames = ['membership_id', 'membership_contact_id', 'membership_start_date', 'membership_join_date', 'membership_end_date', 'membership_source', 'member_is_override', 'member_is_test', 'member_is_pay_later', 'member_campaign_id'];
    $fields = [];
    foreach ($uniqueNames as $name) {
      $fields[$this->importableFieldsMetadata[$name]['name']] = $name;
    }
    // Include the parent fields as they could be present if required for matching ...in theory.
    return array_merge($fields, parent::getOddlyMappedMetadataFields());
  }

}
