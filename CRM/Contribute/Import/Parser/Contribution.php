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

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\Email;

/**
 * Class to parse contribution csv files.
 */
class CRM_Contribute_Import_Parser_Contribution extends CRM_Import_Parser {

  /**
   * Array of successfully imported contribution id's
   *
   * @var array
   */
  protected $_newContributions;

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
      'contribution_import' => [
        'id' => 'contribution_import',
        'name' => 'contribution_import',
        'label' => ts('Contribution Import'),
        'entity' => 'Contribution',
      ],
    ];
  }

  /**
   * Contribution-specific result codes
   * @see CRM_Import_Parser result code constants
   */
  const SOFT_CREDIT = 512, SOFT_CREDIT_ERROR = 1024, PLEDGE_PAYMENT = 2048, PLEDGE_PAYMENT_ERROR = 4096;

  /**
   * Separator being used
   * @var string
   */
  protected $_separator;

  /**
   * Total number of lines in file
   * @var int
   */
  protected $_lineCount;

  /**
   * Running total number of valid soft credit rows
   * @var int
   */
  protected $_validSoftCreditRowCount;

  /**
   * Running total number of invalid soft credit rows
   * @var int
   */
  protected $_invalidSoftCreditRowCount;

  /**
   * Running total number of valid pledge payment rows
   * @var int
   */
  protected $_validPledgePaymentRowCount;

  /**
   * Running total number of invalid pledge payment rows
   * @var int
   */
  protected $_invalidPledgePaymentRowCount;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_pledgePaymentErrors;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_softCreditErrors;

  /**
   * Filename of pledge payment error data
   *
   * @var string
   */
  protected $_pledgePaymentErrorsFileName;

  /**
   * Filename of soft credit error data
   *
   * @var string
   */
  protected $_softCreditErrorsFileName;

  /**
   * Get the field mappings for the import.
   *
   * This is the same format as saved in civicrm_mapping_field except
   * that location_type_id = 'Primary' rather than empty where relevant.
   * Also 'im_provider_id' is mapped to the 'real' field name 'provider_id'
   *
   * @return array
   * @throws \API_Exception
   */
  protected function getFieldMappings(): array {
    $mappedFields = [];
    foreach ($this->getSubmittedValue('mapper') as $i => $mapperRow) {
      $mappedField = $this->getMappingFieldFromMapperInput($mapperRow, 0, $i);
      // Just for clarity since 0 is a pseudo-value
      unset($mappedField['mapping_id']);
      $mappedFields[] = $mappedField;
    }
    return $mappedFields;
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return ['id' => ts('Contribution ID'), ['financial_type_id' => ts('Financial Type'), 'total_amount' => ts('Total Amount')]];
  }

  /**
   * Transform the input parameters into the form handled by the input routine.
   *
   * @param array $values
   *   Input parameters as they come in from the datasource
   *   eg. ['Bob', 'Smith', 'bob@example.org', '123-456']
   *
   * @return array
   *   Parameters mapped to CiviCRM fields based on the mapping. eg.
   *   [
   *     'total_amount' => '1230.99',
   *     'financial_type_id' => 1,
   *     'external_identifier' => 'abcd',
   *     'soft_credit' => [3 => ['external_identifier' => '123', 'soft_credit_type_id' => 1]]
   *
   * @throws \API_Exception
   */
  public function getMappedRow(array $values): array {
    $params = [];
    foreach ($this->getFieldMappings() as $i => $mappedField) {
      if ($mappedField['name'] === 'do_not_import' || !$mappedField['name']) {
        continue;
      }
      if (!empty($mappedField['soft_credit_match_field'])) {
        $params['soft_credit'][$i] = ['soft_credit_type_id' => $mappedField['soft_credit_type_id'], $mappedField['soft_credit_match_field'] => $values[$i]];
      }
      else {
        $entity = $this->getFieldMetadata($mappedField['name'])['entity'] ?? 'Contribution';
        $params[$entity][$this->getFieldMetadata($mappedField['name'])['name']] = $this->getTransformedFieldValue($mappedField['name'], $values[$i]);
      }
    }
    return $params;
  }

  /**
   * Override parent to cope with params being separated by entity already.
   *
   * @todo - make this the parent method...
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateParams(array $params): void {

    if (empty($params['Contribution']['id'])) {
      $this->validateRequiredFields($this->getRequiredFields(), $params['Contribution']);
    }
    $errors = [];
    foreach ($params as $entity => $values) {
      foreach ($values as $key => $value) {
        $errors = array_merge($this->getInvalidValues($value, $key), $errors);
      }
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * The initializer code, called before the processing
   */
  public function init() {
    // Force re-load of user job.
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = $this->importableFields($this->getContactType());

      $fields = array_merge($fields,
        [
          'soft_credit' => [
            'title' => ts('Soft Credit'),
            'softCredit' => TRUE,
            'headerPattern' => '/Soft Credit/i',
            'options' => FALSE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ]
      );

      // add pledge fields only if its is enabled
      if (CRM_Core_Permission::access('CiviPledge')) {
        $pledgeFields = [
          'pledge_id' => [
            'title' => ts('Pledge ID'),
            'headerPattern' => '/Pledge ID/i',
            'name' => 'pledge_id',
            // This is handled as a contribution field & the goal is
            // to make it pseudofield on the contribution.
            'entity' => 'Contribution',
            'type' => CRM_Utils_Type::T_INT,
            'options' => FALSE,
          ],
        ];

        $fields = array_merge($fields, $pledgeFields);
      }
      foreach ($fields as $name => $field) {
        $fields[$name] = array_merge([
          'type' => CRM_Utils_Type::T_INT,
          'headerPattern' => '//',
        ], $field);
      }
      $this->importableFieldsMetadata = $fields;
    }
  }

  /**
   * Combine all the importable fields from the lower levels object.
   *
   * This function should be decommissioned into setFieldMetadata.
   *
   * @param string $contactType
   *
   * @return array
   *   array of importable Fields
   */
  private function importableFields($contactType = 'Individual') {
    $fields = ['' => ['title' => ts('- do not import -')]];

    $note = CRM_Core_DAO_Note::import();
    $tmpFields = CRM_Contribute_DAO_Contribution::import();
    unset($tmpFields['option_value']);
    $tmpContactField = $this->getContactFields($contactType);
    $tmpFields['contribution_contact_id']['title'] = $tmpFields['contribution_contact_id']['html']['label'] = $tmpFields['contribution_contact_id']['title'] . ' ' . ts('(match to contact)');
    $fields = array_merge($fields, $tmpContactField);
    $fields = array_merge($fields, $tmpFields);
    $fields = array_merge($fields, $note);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Contribution'));
    return $fields;
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import($values): void {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $entityKeyedParams = $this->getMappedRow($values);
      $entityKeyedParams['Contribution']['id'] = $this->lookupContributionID($entityKeyedParams['Contribution']);
      if (empty($entityKeyedParams['Contribution']['id']) && $this->isUpdateExisting()) {
        throw new CRM_Core_Exception('Empty Contribution and Invoice and Transaction ID. Row was skipped.', CRM_Import_Parser::ERROR);
      }
      if (!empty($params['Contact']['contact_id'])) {
        $this->validateContactID($params['Contact']['contact_id'], $this->getContactType());
      }
      // @todo - here we flatten the entities back into a single array.
      // The entity format is better but the code below needs to be migrated.
      $params = [];
      foreach (['Contact', 'Contribution', 'Note'] as $entity) {
        $params = array_merge($params, ($entityKeyedParams[$entity] ?? []));
      }
      if (isset($entityKeyedParams['soft_credit'])) {
        $params['soft_credit'] = $entityKeyedParams['soft_credit'];
      }

      $formatted = array_merge(['version' => 3, 'skipRecentView' => TRUE, 'skipCleanMoney' => TRUE, 'contribution_id' => $params['id'] ?? NULL], $params);
      //CRM-10994
      if (isset($params['total_amount']) && $params['total_amount'] == 0) {
        $params['total_amount'] = '0.00';
      }

      $paramValues = [];
      foreach ($params as $key => $field) {
        if ($field == NULL || $field === '') {
          continue;
        }
        $paramValues[$key] = $field;
      }

      //import contribution record according to select contact type
      if ($this->isSkipDuplicates() &&
        (!empty($paramValues['contribution_contact_id']) || !empty($paramValues['external_identifier']))
      ) {
        $paramValues['contact_type'] = $this->getContactType();
      }
      elseif ($this->isUpdateExisting() &&
        (!empty($paramValues['id']))
      ) {
        $paramValues['contact_type'] = $this->getContactType();
      }
      elseif (!empty($paramValues['pledge_payment'])) {
        $paramValues['contact_type'] = $this->getContactType();
      }

      $this->deprecatedFormatParams($paramValues, $formatted);

      if ($this->isUpdateExisting()) {
        //fix for CRM-2219 - Update Contribution
        // onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE
        if (!empty($paramValues['id'])) {
          // todo Remove if in separate PR
          if (TRUE) {
            $formatted['id'] = $paramValues['id'];
            //process note
            if (!empty($paramValues['note'])) {
              $noteID = [];
              $contactID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $paramValues['id'], 'contact_id');
              $daoNote = new CRM_Core_BAO_Note();
              $daoNote->entity_table = 'civicrm_contribution';
              $daoNote->entity_id = $paramValues['id'];
              if ($daoNote->find(TRUE)) {
                $noteID['id'] = $daoNote->id;
              }

              $noteParams = [
                'entity_table' => 'civicrm_contribution',
                'note' => $paramValues['note'],
                'entity_id' => $paramValues['id'],
                'contact_id' => $contactID,
              ];
              CRM_Core_BAO_Note::add($noteParams, $noteID);
              unset($formatted['note']);
            }

            //need to check existing soft credit contribution, CRM-3968
            if (!empty($formatted['soft_credit'])) {
              $dupeSoftCredit = [
                'contact_id' => $formatted['soft_credit'],
                'contribution_id' => $paramValues['id'],
              ];

              //Delete all existing soft Contribution from contribution_soft table for pcp_id is_null
              $existingSoftCredit = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($dupeSoftCredit['contribution_id']);
              if (isset($existingSoftCredit['soft_credit']) && !empty($existingSoftCredit['soft_credit'])) {
                foreach ($existingSoftCredit['soft_credit'] as $key => $existingSoftCreditValues) {
                  if (!empty($existingSoftCreditValues['soft_credit_id'])) {
                    civicrm_api3('ContributionSoft', 'delete', [
                      'id' => $existingSoftCreditValues['soft_credit_id'],
                      'pcp_id' => NULL,
                    ]);
                  }
                }
              }
            }

            $formatted['id'] = $paramValues['id'];

            $newContribution = civicrm_api3('contribution', 'create', $formatted);
            $this->_newContributions[] = $newContribution['id'];

            //return soft valid since we need to show how soft credits were added
            if (!empty($formatted['soft_credit'])) {
              $this->setImportStatus($rowNumber, $this->getStatus(self::SOFT_CREDIT));
              return;
            }

            $this->setImportStatus($rowNumber, $this->processPledgePayments($formatted) ? $this->getStatus(self::PLEDGE_PAYMENT) : $this->getStatus(self::VALID), '', $newContribution['id']);
            return;
          }
        }
      }

      if (empty($formatted['contact_id'])) {

        $error = $this->checkContactDuplicate($paramValues);

        if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
          $matchedIDs = (array) $error['error_message']['params'];
          if (count($matchedIDs) > 1) {
            throw new CRM_Core_Exception('Multiple matching contact records detected for this row. The contribution was not imported', CRM_Import_Parser::ERROR);
          }
          $cid = $matchedIDs[0];
          $formatted['contact_id'] = $cid;

          $newContribution = civicrm_api('contribution', 'create', $formatted);
          if (civicrm_error($newContribution)) {
            if (is_array($newContribution['error_message'])) {
              if ($newContribution['error_message']['params'][0]) {
                throw new CRM_Core_Exception($newContribution['error_message']['message'], CRM_Import_Parser::DUPLICATE);
              }
            }
            else {
              throw new CRM_Core_Exception($newContribution['error_message'], CRM_Import_Parser::ERROR);
            }
          }

          $this->_newContributions[] = $newContribution['id'];
          $formatted['contribution_id'] = $newContribution['id'];

          //return soft valid since we need to show how soft credits were added
          if (!empty($formatted['soft_credit'])) {
            $this->setImportStatus($rowNumber, $this->getStatus(self::SOFT_CREDIT), '', $newContribution['id']);
            return;
          }

          $this->setImportStatus($rowNumber, $this->processPledgePayments($formatted) ? $this->getStatus(self::PLEDGE_PAYMENT) : $this->getStatus(self::VALID), '', $newContribution['id']);
          return;
        }

        // Using new Dedupe rule.
        $ruleParams = [
          'contact_type' => $this->getContactType(),
          'used' => 'Unsupervised',
        ];
        $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);
        $disp = NULL;
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
        $errorMessage = 'No matching Contact found for (' . $disp . ')';
        throw new CRM_Core_Exception($errorMessage, CRM_Import_Parser::ERROR);
      }

      if (!empty($paramValues['external_identifier'])) {
        $checkCid = new CRM_Contact_DAO_Contact();
        $checkCid->external_identifier = $paramValues['external_identifier'];
        $checkCid->find(TRUE);
        if ($checkCid->id != $formatted['contact_id']) {
          $errorMessage = 'Mismatch of External ID:' . $paramValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id'];
          throw new CRM_Core_Exception($errorMessage, CRM_Import_Parser::ERROR);
        }
      }
      $newContribution = civicrm_api('contribution', 'create', $formatted);
      if (civicrm_error($newContribution)) {
        if (is_array($newContribution['error_message'])) {
          if ($newContribution['error_message']['params'][0]) {
            throw new CRM_Core_Exception('', CRM_Import_Parser::DUPLICATE);
          }
        }
        else {
          throw new CRM_Core_Exception($newContribution['error_message'], CRM_Import_Parser::ERROR);
        }
      }

      $this->_newContributions[] = $newContribution['id'];
      $formatted['contribution_id'] = $newContribution['id'];

      //return soft valid since we need to show how soft credits were added
      if (!empty($formatted['soft_credit'])) {
        $this->setImportStatus($rowNumber, $this->getStatus(self::SOFT_CREDIT), '', $newContribution['id']);
        return;
      }

      // process pledge payment assoc w/ the contribution
      $this->setImportStatus($rowNumber, $this->processPledgePayments($formatted) ? $this->getStatus(self::PLEDGE_PAYMENT) : $this->getStatus(self::VALID), $newContribution['id']);
      return;

    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, $this->getStatus($e->getErrorCode()), $e->getMessage());
    }
  }

  /**
   * Lookup pre-existing contribution ID.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   *
   * @return int|null
   */
  private function lookupContributionID(array $params): ?int {
    $where = [];
    $labels = [];
    foreach (['id' => 'Contribution ID', 'trxn_id' => 'Transaction ID', 'invoice_id' => 'Invoice ID'] as $field => $label) {
      if (!empty($params[$field])) {
        $where[] = [$field, '=', $params[$field]];
        $labels[] = $label . ' ' . $params[$field];
      }
    }
    if (empty($where)) {
      return NULL;
    }
    $contribution = Contribution::get(FALSE)->setWhere($where)->addSelect('id')->execute()->first();
    if ($contribution['id'] ?? NULL) {
      return $contribution['id'];
    }
    throw new CRM_Core_Exception('Matching Contribution record not found for ' . implode(' AND ', $labels) . '. Row was skipped.', CRM_Import_Parser::ERROR);
  }

  /**
   * Get the status to record.
   *
   * @param int|null $code
   *
   * @return string
   */
  protected function getStatus(?int $code): string {
    $errorMapping = [
      self::SOFT_CREDIT_ERROR => 'soft_credit_error',
      self::PLEDGE_PAYMENT_ERROR => 'pledge_payment_error',
      self::SOFT_CREDIT => 'soft_credit_imported',
      self::PLEDGE_PAYMENT => 'pledge_payment_imported',
      CRM_Import_Parser::DUPLICATE => 'DUPLICATE',
      CRM_Import_Parser::VALID => 'IMPORTED',
    ];
    return $errorMapping[$code] ?? 'ERROR';
  }

  /**
   * Process pledge payments.
   *
   * @param array $formatted
   *
   * @return bool
   */
  private function processPledgePayments(array $formatted): bool {
    if (!empty($formatted['pledge_payment_id']) && !empty($formatted['pledge_id'])) {
      //get completed status
      $completeStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

      //need to update payment record to map contribution_id
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $formatted['pledge_payment_id'],
        'contribution_id', $formatted['contribution_id']
      );

      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($formatted['pledge_id'],
        [$formatted['pledge_payment_id']],
        $completeStatusID,
        NULL,
        $formatted['total_amount']
      );
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the array of successfully imported contribution id's
   *
   * @return array
   */
  public function &getImportedContributions() {
    return $this->_newContributions;
  }

  /**
   * take the input parameter list as specified in the data model and
   * convert it into the same format that we use in QF and BAO object
   *
   * @param array $params
   *   Associative array of property name/value
   *   pairs to insert in new contact.
   * @param array $values
   *   The reformatted properties that we can use internally.
   * @param bool $create
   *
   * @throws \CRM_Core_Exception
   */
  private function deprecatedFormatParams($params, &$values, $create = FALSE): void {
    // copy all the contribution fields as is
    require_once 'api/v3/utils.php';

    foreach ($params as $key => $value) {
      // ignore empty values or empty arrays etc
      if (CRM_Utils_System::isNull($value)) {
        continue;
      }

      switch ($key) {

        case 'contact_type':
          // import contribution record according to select contact type
          require_once 'CRM/Contact/DAO/Contact.php';
          $contactType = new CRM_Contact_DAO_Contact();
          $contactId = $params['contribution_contact_id'] ?? NULL;
          $externalId = $params['external_identifier'] ?? NULL;
          $email = $params['email'] ?? NULL;
          //when insert mode check contact id or external identifier
          if ($contactId || $externalId) {
            $contactType->id = $contactId;
            $contactType->external_identifier = $externalId;
            if ($contactType->find(TRUE)) {
              if ($params['contact_type'] != $contactType->contact_type) {
                throw new CRM_Core_Exception("Contact Type is wrong: $contactType->contact_type", CRM_Import_Parser::ERROR);
              }
            }
          }
          elseif ($email) {

            // get the contact id from duplicate contact rule, if more than one contact is returned
            // we should return error, since current interface allows only one-one mapping
            $ids = CRM_Contact_BAO_Contact::getDuplicateContacts([
              'email' => $email,
              'contact_type' => $params['contact_type'],
            ], $params['contact_type'], 'Unsupervised');

            if (!empty($ids)) {
              $checkDedupe = [
                'is_error' => 1,
                'error_message' => [
                  'code' => CRM_Core_Error::DUPLICATE_CONTACT,
                  'params' => $ids,
                  'level' => 'Fatal',
                  'message' => 'Found matching contacts: ' . implode(',', $ids),
                ],
              ];
            }
            else {
              $checkDedupe = ['is_error' => 0];
            }
            if (!$checkDedupe['is_error']) {
              throw new CRM_Core_Exception("Invalid email address(doesn't exist) $email. Row was skipped", CRM_Import_Parser::ERROR);
            }
            $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
            if (count($matchingContactIds) > 1) {
              throw new CRM_Core_Exception("Invalid email address(duplicate) $email. Row was skipped", CRM_Import_Parser::ERROR);
            }
            if (count($matchingContactIds) == 1) {
              $params['contribution_contact_id'] = $matchingContactIds[0];
            }
          }
          elseif (!empty($params['id'])) {
            // when update mode check contribution id or trxn id or
            // invoice id
            // @todo - this check is obsolete. It survives for now
            // in order to keep the rc patch small & non-conflicty.
            $contactId = new CRM_Contribute_DAO_Contribution();
            if (!empty($params['id'])) {
              $contactId->id = $params['id'];
            }
            if ($contactId->find(TRUE)) {
              $contactType->id = $contactId->contact_id;
              if ($contactType->find(TRUE)) {
                if ($params['contact_type'] != $contactType->contact_type) {
                  throw new CRM_Core_Exception("Contact Type is wrong: $contactType->contact_type", CRM_Import_Parser::ERROR);
                }
              }
            }
          }
          break;

        case 'soft_credit':
          // import contribution record according to select contact type
          // validate contact id and external identifier.
          foreach ($value as $softKey => $softParam) {
            $values['soft_credit'][$softKey] = [
              'contact_id' => $this->lookupMatchingContact($softParam),
              'soft_credit_type_id' => $softParam['soft_credit_type_id'],
            ];
          }
          break;

        case 'pledge_id':
          // get total amount of from import fields
          $totalAmount = $params['total_amount'] ?? NULL;
          // we need to get contact id $contributionContactID to
          // retrieve pledge details as well as to validate pledge ID

          // first need to check for update mode
          if (!empty($params['id'])) {
            $contribution = new CRM_Contribute_DAO_Contribution();
            if ($params['id']) {
              $contribution->id = $params['id'];
            }

            if ($contribution->find(TRUE)) {
              $contributionContactID = $contribution->contact_id;
              if (!$totalAmount) {
                $totalAmount = $contribution->total_amount;
              }
            }
            else {
              throw new CRM_Core_Exception('No match found for specified contact in pledge payment data. Row was skipped.', CRM_Import_Parser::ERROR);
            }
          }
          else {
            // first get the contact id for given contribution record.
            if (!empty($params['contribution_contact_id'])) {
              $contributionContactID = $params['contribution_contact_id'];
            }
            elseif (!empty($params['external_identifier'])) {
              require_once 'CRM/Contact/DAO/Contact.php';
              $contact = new CRM_Contact_DAO_Contact();
              $contact->external_identifier = $params['external_identifier'];
              if ($contact->find(TRUE)) {
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $contact->id;
              }
              else {
                throw new CRM_Core_Exception('No match found for specified contact in pledge payment data. Row was skipped.');
              }
            }
            else {
              // we need to get contribution contact using de dupe
              $error = $this->checkContactDuplicate($params);

              if (isset($error['error_message']['params'][0])) {
                $matchedIDs = (array) $error['error_message']['params'];

                // check if only one contact is found
                if (count($matchedIDs) > 1) {
                  throw new CRM_Core_Exception($error['error_message']['message'], CRM_Import_Parser::ERROR);
                }
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $matchedIDs[0];
              }
              else {
                throw new CRM_Core_Exception('No match found for specified contact in contribution data. Row was skipped.', CRM_Import_Parser::ERROR);
              }
            }
          }

          if (!empty($params['pledge_id'])) {
            if (CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $contributionContactID) {
              throw new CRM_Core_Exception('Invalid Pledge ID provided. Contribution row was skipped.', CRM_Import_Parser::ERROR);
            }
            $values['pledge_id'] = $params['pledge_id'];
          }
          else {
            // check if there are any pledge related to this contact, with payments pending or in progress
            require_once 'CRM/Pledge/BAO/Pledge.php';
            $pledgeDetails = CRM_Pledge_BAO_Pledge::getContactPledges($contributionContactID);

            if (empty($pledgeDetails)) {
              throw new CRM_Core_Exception('No open pledges found for this contact. Contribution row was skipped.', CRM_Import_Parser::ERROR);
            }
            if (count($pledgeDetails) > 1) {
              throw new CRM_Core_Exception('This contact has more than one open pledge. Unable to determine which pledge to apply the contribution to. Contribution row was skipped.', CRM_Import_Parser::ERROR);
            }

            // this mean we have only one pending / in progress pledge
            $values['pledge_id'] = $pledgeDetails[0];
          }

          // we need to check if oldest payment amount equal to contribution amount
          require_once 'CRM/Pledge/BAO/PledgePayment.php';
          $pledgePaymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($values['pledge_id']);

          if ($pledgePaymentDetails['amount'] == $totalAmount) {
            $values['pledge_payment_id'] = $pledgePaymentDetails['id'];
          }
          else {
            throw new CRM_Core_Exception('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.', CRM_Import_Parser::ERROR);
          }
          break;

      }
    }
  }

  /**
   * Get the civicrm_mapping_field appropriate layout for the mapper input.
   *
   * The input looks something like ['street_address', 1]
   * and would be mapped to ['name' => 'street_address', 'location_type_id' =>
   * 1]
   *
   * @param array $fieldMapping
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   */
  public function getMappingFieldFromMapperInput(array $fieldMapping, int $mappingID, int $columnNumber): array {
    return [
      'name' => $fieldMapping[0],
      'mapping_id' => $mappingID,
      'column_number' => $columnNumber,
      // The name of the field to match the soft credit on is (crazily)
      // stored in 'contact_type'
      'contact_type' => $fieldMapping[1] ?? NULL,
      // We also store the field in a sensible key, even if it isn't saved sensibly.
      'soft_credit_match_field' => $fieldMapping[1] ?? NULL,
      // This field is actually not saved at all :-( It is lost each time.
      'soft_credit_type_id' => $fieldMapping[2] ?? NULL,
    ];
  }

  /**
   * Lookup matching contact.
   *
   * This looks up the matching contact from the contact id, external identifier
   * or email. For the email a straight email search is done - this is equivalent
   * to what happens on a dedupe rule lookup when the only field is 'email' - but
   * we can't be sure the rule is 'just email' - and we are not collecting the
   * fields for any other lookup in the case of soft credits (if we
   * extend this function to main-contact-lookup we can handle full dedupe
   * lookups - but note the error messages will need tweaking.
   *
   * @param array $params
   *
   * @return int
   *   Contact ID
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  private function lookupMatchingContact(array $params): int {
    $lookupField = !empty($params['contact_id']) ? 'contact_id' : (!empty($params['external_identifier']) ? 'external_identifier' : 'email');
    if (empty($params['email'])) {
      $contact = Contact::get(FALSE)->addSelect('id')
        ->addWhere($lookupField === 'contact_id' ? 'id' : $lookupField, '=', $params[$lookupField])
        ->execute();
      if (count($contact) !== 1) {
        throw new CRM_Core_Exception(ts("Soft Credit %1 - %2 doesn't exist. Row was skipped.",
          [
            1 => $this->getFieldMetadata($lookupField),
            2 => $params['contact_id'] ?? $params['external_identifier'],
          ]));
      }
      return $contact->first()['id'];
    }

    if (!CRM_Utils_Rule::email($params['email'])) {
      throw new CRM_Core_Exception(ts('Invalid email address %1 provided for Soft Credit. Row was skipped'), [1 => $params['email']]);
    }
    $emails = Email::get(FALSE)
      ->addWhere('contact_id.is_deleted', '=', 0)
      ->addWhere('contact_id.contact_type', '=', $this->getContactType())
      ->addWhere('email', '=', $params['email'])
      ->addSelect('contact_id')->execute();
    if (count($emails) === 0) {
      throw new CRM_Core_Exception(ts("Invalid email address(doesn't exist) %1 for Soft Credit. Row was skipped", [1 => $params['email']]));
    }
    if (count($emails) > 1) {
      throw new CRM_Core_Exception(ts('Invalid email address(duplicate) %1 for Soft Credit. Row was skipped', [1 => $params['email']]));
    }
    return $emails->first()['contact_id'];
  }

  /**
   * @param array $mappedField
   *   Field detail as would be saved in field_mapping table
   *   or as returned from getMappingFieldFromMapperInput
   *
   * @return string
   */
  public function getMappedFieldLabel(array $mappedField): string {
    if (empty($this->importableFieldsMetadata)) {
      $this->setFieldMetadata();
    }
    if ($mappedField['name'] === '') {
      return '';
    }
    $title = [];
    $title[] = $this->getFieldMetadata($mappedField['name'])['title'];
    if ($mappedField['soft_credit_match_field']) {
      $title[] = $this->getFieldMetadata($mappedField['soft_credit_match_field'])['title'];
    }
    if ($mappedField['soft_credit_type_id']) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $mappedField['soft_credit_type_id']);
    }

    return implode(' - ', $title);
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    $uniqueNames = ['contribution_id', 'contribution_contact_id', 'contribution_cancel_date', 'contribution_source', 'contribution_check_number'];
    $fields = [];
    foreach ($uniqueNames as $name) {
      $fields[$this->importableFieldsMetadata[$name]['name']] = $name;
    }
    // Include the parent fields as they could be present if required for matching ...in theory.
    return array_merge($fields, parent::getOddlyMappedMetadataFields());
  }

}
