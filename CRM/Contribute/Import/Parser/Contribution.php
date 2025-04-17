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
use Civi\Api4\ContributionSoft;
use Civi\Api4\Email;
use Civi\Api4\Note;

/**
 * Class to parse contribution csv files.
 */
class CRM_Contribute_Import_Parser_Contribution extends CRM_Import_Parser {

  protected $baseEntity = 'Contribution';

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
        'url' => 'civicrm/import/contribution',
      ],
    ];
  }

  /**
   * Contribution-specific result codes
   * @see CRM_Import_Parser result code constants
   */
  const SOFT_CREDIT = 512, SOFT_CREDIT_ERROR = 1024, PLEDGE_PAYMENT = 2048, PLEDGE_PAYMENT_ERROR = 4096;

  /**
   * Get the field mappings for the import.
   *
   * @return array
   *   Array of arrays with each array representing a row in the datasource.
   *   The arrays hold the following keys
   *   - name - field the row maps to
   *   - entity_data - data about the relevant entity ie ['soft_credit' => ['soft_credit_type_id => 9],
   *   In addition the following are returned but will be phased out.
   *   - contact_type - entity_data but json_encoded. Saved to civicrm_mapping_field in contact_type column
   *   - column_number = this is used for saving to civicrm_field_mapping but
   *     may be only legacy now?
   *   - soft_credit_type_id
   *
   * @throws \CRM_Core_Exception
   */
  protected function getFieldMappings(): array {
    $mappedFields = $this->getUserJob()['metadata']['import_mappings'] ?? [];
    if (empty($mappedFields)) {
      foreach ($this->getSubmittedValue('mapper') as $i => $mapperRow) {
        $mappedField = $this->getMappingFieldFromMapperInput($mapperRow, 0, $i);
        // Just for clarity since 0 is a pseudo-value
        unset($mappedField['mapping_id']);
        $mappedFields[] = $mappedField;
      }
    }
    foreach ($mappedFields as $index => $mappedField) {
      $mappedFields[$index]['column_number'] = 0;
      // This is the same data as entity_data - it is stored to the database in the contact_type field
      // slit your eyes & squint while blinking and you can almost read that as entity_type and not
      // hate it. Otherwise go & whinge on https://lab.civicrm.org/dev/core/-/issues/1172
      $mappedFields[$index]['contact_type'] = !empty($mappedField['entity_data']) ? json_encode($mappedField['entity_data']) : NULL;
      $mappedFields[$index]['soft_credit_type_id'] = !empty($mappedField['entity_data']) ? $mappedField['entity_data']['soft_credit']['soft_credit_type_id'] : NULL;
    }
    return $mappedFields;
  }

  /**
   * Get the required fields.
   *
   * @return array
   */
  public function getRequiredFields(): array {
    return [[$this->getRequiredFieldsForMatch(), $this->getRequiredFieldsForCreate()]];
  }

  /**
   * Get required fields to create a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForCreate(): array {
    return ['financial_type_id', 'total_amount'];
  }

  /**
   * Get required fields to match a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [['id'], ['invoice_id'], ['trxn_id']];
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
   * @throws \CRM_Core_Exception
   */
  public function getMappedRow(array $values): array {
    $params = [];
    foreach ($this->getFieldMappings() as $i => $mappedField) {
      if (empty($mappedField['name']) || $mappedField['name'] === 'do_not_import') {
        continue;
      }
      $fieldSpec = $this->getFieldMetadata($mappedField['name']);
      $columnHeader = $this->getUserJob()['metadata']['DataSource']['column_headers'][$i] ?? '';
      // If there is no column header we are dealing with an added value mapping, do not use
      // the database value as it will be for (e.g.) `_status`
      $fieldValue = $columnHeader ? $values[$i] : '';
      if ($fieldValue === '' && isset($mappedField['default_value'])) {
        $fieldValue = $mappedField['default_value'];
      }
      $entity = $fieldSpec['entity_instance'] ?? ($fieldSpec['entity'] ?? 'Contribution');
      // If we move this to the parent we can check if the entity config 'supports_multiple'
      if ($entity === 'SoftCreditContact') {
        $entityKey = json_encode($mappedField['entity_data']);
        if (isset($params[$entity][$entityKey])) {
          $entityInstance = $params[$entity][$entityKey];
        }
        else {
          $entityInstance = (array) ($mappedField['entity_data']['soft_credit']);
          $entityInstance['Contact']['contact_type'] = $this->getContactTypeForEntity($entity);
        }
        $entityInstance['Contact'] = array_merge($entityInstance['Contact'], [$this->getFieldMetadata($mappedField['name'])['name'] => $this->getTransformedFieldValue($mappedField['name'], $fieldValue)]);
        $params[$entity][$entityKey] = $entityInstance;
      }
      else {
        if ($entity === 'Contact' && !isset($params[$entity])) {
          $params[$entity] = [];
          $params[$entity]['contact_type'] = $this->getContactTypeForEntity($entity) ?: $this->getContactType();
        }
        $params[$entity][$this->getFieldMetadata($mappedField['name'])['name']] = $this->getTransformedFieldValue($mappedField['name'], $fieldValue);
      }
    }
    return $this->removeEmptyValues($params);
  }

  /**
   * Validate the import values.
   *
   * This overrides the parent to call the hook - cos the other imports are not
   * yet stable enough to add the hook to. If we add the hook to them now and then
   * later switch them to APIv4 style keys we will have to worry about hook consumers.
   *
   * The values array represents a row in the datasource.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  public function validateValues(array $values): void {
    $params = $this->getMappedRow($values);
    CRM_Utils_Hook::importAlterMappedRow('validate', 'contribution_import', $params, $values, $this->getUserJobID());
    $this->validateParams($params);
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
      $fields = ['' => ['title' => ts('- do not import -')]];

      $note = CRM_Core_DAO_Note::import();
      // @todo - replace this with (array) Contribution::getFields()
      //      ->addWhere('readonly', '=', FALSE)
      //      ->addWhere('usage', 'CONTAINS', 'import')
      $tmpFields = CRM_Contribute_DAO_Contribution::import();
      // Unravel the unique fields - once more metadata work is done on apiv4 we
      // will use that instead to get them.
      foreach (['contribution_cancel_date', 'contribution_check_number', 'contribution_campaign_id', 'contribution_id', 'contribution_contact_id', 'contribution_source'] as $uniqueField) {
        $realField = substr($uniqueField, 13);
        $tmpFields[$realField] = $tmpFields[$uniqueField];
        unset($tmpFields[$uniqueField]);
      }
      // I haven't un-done this unique field yet cos it's more complex.
      $tmpFields['contact_id']['title'] = $tmpFields['contact_id']['html']['label'] = $tmpFields['contact_id']['title'] . ' ' . ts('(match to contact)');
      $tmpFields['contact_id']['contact_type'] = ['Individual' => 'Individual', 'Household' => 'Household', 'Organization' => 'Organization'];
      $tmpFields['contact_id']['match_rule'] = '*';
      $tmpContactField = $this->getContactFields($this->getContactType());
      foreach ($tmpContactField as $contactField) {
        $fields['contact.' . $contactField['name']] = array_merge($contactField, [
          'title' => ts('Contact') . ' ' . $contactField['title'],
          'softCredit' => FALSE,
          'entity' => 'Contact',
          'entity_instance' => 'Contact',
          'entity_prefix' => 'contact.',
        ]);
      }
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, $note);
      $apiv4 = Contribution::getFields(TRUE)->addWhere('custom_field_id', '>', 0)->execute();
      $customFields = [];
      foreach ($apiv4 as $apiv4Field) {
        $customFields[$apiv4Field['name']] = $apiv4Field;
      }
      $fields = array_merge($fields, $customFields);

      $fields['soft_credit.contact.id'] = [
        'title' => ts('Soft Credit Contact ID'),
        'softCredit' => TRUE,
        'name' => 'id',
        'entity' => 'Contact',
        'entity_instance' => 'SoftCreditContact',
        'entity_prefix' => 'soft_credit.contact.',
        'options' => FALSE,
        'type' => CRM_Utils_Type::T_STRING,
        'contact_type' => ['Individual' => 'Individual', 'Household' => 'Household', 'Organization' => 'Organization'],
        'match_rule' => '*',
      ];
      foreach ($tmpContactField as $contactField) {
        $fields['soft_credit.contact.' . $contactField['name']] = array_merge($contactField, [
          'title' => ts('Soft Credit Contact') . ' ' . $contactField['title'],
          'softCredit' => TRUE,
          'entity' => 'Contact',
          'entity_instance' => 'SoftCreditContact',
          'entity_prefix' => 'soft_credit.contact.',
        ]);
      }

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
      $this->importableFieldsMetadata = $fields;
    }
  }

  /**
   * Get a list of entities this import supports.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getImportEntities() : array {
    $softCreditTypes = ContributionSoft::getFields(FALSE)
      ->setLoadOptions(['id', 'name', 'label', 'description'])
      ->addWhere('name', '=', 'soft_credit_type_id')
      ->addSelect('options')->execute()->first()['options'];
    $defaultSoftCreditTypeID = CRM_Core_OptionGroup::getDefaultValue('soft_credit_type');
    foreach ($softCreditTypes as &$softCreditType) {
      if (empty($defaultSoftCreditTypeID)) {
        $defaultSoftCreditTypeID = $softCreditType['id'];
      }
      $softCreditType['text'] = $softCreditType['label'];
    }

    return [
      'Contribution' => [
        'text' => ts('Contribution Fields'),
        'required_fields_update' => $this->getRequiredFieldsForMatch(),
        'required_fields_create' => $this->getRequiredFieldsForCreate(),
        'is_base_entity' => TRUE,
        'supports_multiple' => FALSE,
        'is_required' => TRUE,
        // For now we stick with the action selected on the DataSource page.
        'actions' => $this->isUpdateExisting() ?
          [['id' => 'update', 'text' => ts('Update existing'), 'description' => ts('Skip if no match found')]] :
          [['id' => 'create', 'text' => ts('Create'), 'description' => ts('Skip if already exists')]],
        'default_action' => $this->isUpdateExisting() ? 'update' : 'create',
        'entity_name' => 'Contribution',
        'entity_title' => ts('Contribution'),
        'entity_field_prefix' => '',
        'selected' => ['action' => $this->isUpdateExisting() ? 'update' : 'create'],
      ],
      'Contact' => [
        'text' => ts('Contact Fields'),
        'unique_fields' => ['external_identifier', 'id'],
        'is_contact' => TRUE,
        'supports_multiple' => FALSE,
        'actions' => $this->isUpdateExisting() ? $this->getActions(['ignore', 'update']) : $this->getActions(['select', 'update', 'save']),
        'selected' => [
          'action' => $this->isUpdateExisting() ? 'ignore' : 'select',
          'contact_type' => $this->getSubmittedValue('contactType'),
          'dedupe_rule' => $this->getDedupeRule($this->getContactType())['name'],
        ],
        'entity_field_prefix' => 'contact.',
        'default_action' => 'select',
        'entity_name' => 'Contact',
        'entity_title' => ts('Contribution Contact'),
      ],
      'SoftCreditContact' => [
        'text' => ts('Soft Credit Contact Fields'),
        // It turns out there is actually currently no limit - you can import multiple of the same type.
        'supports_multiple' => TRUE,
        'unique_fields' => ['external_identifier', 'id'],
        'is_contact' => TRUE,
        'is_required' => FALSE,
        'actions' => array_merge([['id' => 'ignore', 'text' => ts('Do not import')]], $this->getActions(['select', 'update', 'save'])),
        'selected' => [
          'contact_type' => 'Individual',
          'soft_credit_type_id' => $defaultSoftCreditTypeID,
          'action' => 'ignore',
          'dedupe_rule' => $this->getDedupeRule('Individual')['name'],
        ],
        'default_action' => 'ignore',
        'entity_name' => 'SoftCreditContact',
        'entity_field_prefix' => 'soft_credit.contact.',
        'entity_title' => ts('Soft Credit Contact'),
        'entity_data' => [
          'soft_credit_type_id' => [
            'title' => ts('Soft Credit Type'),
            'is_required' => TRUE,
            'options' => $softCreditTypes,
            'name' => 'soft_credit_type_id',
          ],
        ],
      ],
    ];
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import(array $values): void {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      CRM_Utils_Hook::importAlterMappedRow('import', 'contribution_import', $params, $values, $this->getUserJobID());

      $contributionParams = $params['Contribution'];
      //CRM-10994
      if (isset($contributionParams['total_amount']) && $contributionParams['total_amount'] == 0) {
        $contributionParams['total_amount'] = '0.00';
      }

      $existingContribution = $this->lookupContribution($contributionParams);
      if (empty($existingContribution) && $this->isUpdateExisting()) {
        throw new CRM_Core_Exception(ts('Matching Contribution record not found. Row was skipped.'), CRM_Import_Parser::ERROR);
      }
      $contributionParams['id'] = $existingContribution['id'] ?? NULL;
      if (empty($contributionParams['id']) && $this->isUpdateExisting()) {
        throw new CRM_Core_Exception('Empty Contribution and Invoice and Transaction ID. Row was skipped.', CRM_Import_Parser::ERROR);
      }
      $contributionParams['contact_id'] = $params['Contact']['id'] = $this->getContactID($params['Contact'] ?? [], $contributionParams['contact_id'] ?? ($existingContribution['contact_id'] ?? NULL), 'Contact', $this->getDedupeRulesForEntity('Contact'));

      $softCreditParams = [];
      foreach ($params['SoftCreditContact'] ?? [] as $index => $softCreditContact) {
        $softCreditParams[$index]['soft_credit_type_id'] = $softCreditContact['soft_credit_type_id'];
        $softCreditParams[$index]['contact_id'] = $this->getContactID($softCreditContact['Contact'], !empty($softCreditContact['Contact']['id']) ? $softCreditContact['Contact']['id'] : NULL, 'SoftCreditContact', $this->getDedupeRulesForEntity('SoftCreditContact'));
        if (empty($softCreditParams[$index]['contact_id']) && in_array($this->getActionForEntity('SoftCreditContact'), ['update', 'select'])) {
          throw new CRM_Core_Exception(ts('Soft Credit Contact not found'));
        }
      }

      $this->deprecatedFormatParams($contributionParams);

      // From this point on we are changing stuff - the prior rows were doing lookups and exiting
      // if the lookups failed.

      foreach ($params['SoftCreditContact'] ?? [] as $index => $softCreditContact) {
        $softCreditParams[$index]['contact_id'] = $this->saveContact('SoftCreditContact', $softCreditContact['Contact']) ?: $softCreditParams[$index]['contact_id'];
      }
      $contributionParams['contact_id'] = $this->saveContact('Contact', $params['Contact'] ?? []) ?: $contributionParams['contact_id'];

      if (isset($params['SoftCreditContact']) && $this->isUpdateExisting()) {
        //need to check existing soft credit contribution, CRM-3968
        $this->deleteExistingSoftCredit($contributionParams['id']);
      }

      if ($contributionParams['id']) {
        $contributionID = Contribution::update()->setValues($contributionParams)->execute()->first()['id'];
      }
      else {
        $contributionID = Contribution::create()->setValues($contributionParams)->execute()->first()['id'];
      }

      if (!empty($softCreditParams)) {
        if (empty($contributionParams['total_amount']) || empty($contributionParams['currency'])) {
          $contributionParams = array_merge($contributionParams, Contribution::get()->addSelect('total_amount', 'currency')->addWhere('id', '=', $contributionID)->execute()->first());
        }
        foreach ($softCreditParams as $softCreditParam) {
          $softCreditParam['contribution_id'] = $contributionID;
          $softCreditParam['amount'] = $contributionParams['total_amount'];
          $softCreditParam['currency'] = $contributionParams['currency'];
          ContributionSoft::create()->setValues($softCreditParam)->execute();
        }
      }
      if (!empty($params['Note'])) {
        $this->processNote($contributionID, $contributionParams['contact_id'], $params['Note']);
      }
      //return soft valid since we need to show how soft credits were added
      // because ? historically we did but this seems a bit obsolete.
      if (!empty($softCreditParams)) {
        $this->setImportStatus($rowNumber, $this->getStatus(self::SOFT_CREDIT), '', $contributionID);
        return;
      }

      // process pledge payment assoc w/ the contribution
      $this->setImportStatus($rowNumber, $this->processPledgePayments($contributionID, $contributionParams) ? $this->getStatus(self::PLEDGE_PAYMENT) : $this->getStatus(self::VALID), '', $contributionID);
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
   * @return array
   */
  private function lookupContribution(array $params): array {
    $where = [];
    foreach (['id' => 'Contribution ID', 'trxn_id' => 'Transaction ID', 'invoice_id' => 'Invoice ID'] as $field => $label) {
      if (!empty($params[$field])) {
        $where[] = [$field, '=', $params[$field]];
      }
    }
    if (empty($where)) {
      return [];
    }
    $contribution = Contribution::get(FALSE)->setWhere($where)->addSelect('id', 'contact_id')->execute()->first();
    if ($contribution['id'] ?? NULL) {
      return $contribution;
    }
    return [];
  }

  /**
   * Get the status to record.
   *
   * @param int|null|string $code
   *
   * @return string
   */
  protected function getStatus($code): string {
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
   * @param int $contributionID
   * @param array $formatted
   *
   * @return bool
   */
  private function processPledgePayments(int $contributionID, array $formatted): bool {
    if (!empty($formatted['pledge_payment_id']) && !empty($formatted['pledge_id'])) {
      $completeStatusID = CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_PledgePayment', 'status_id', 'Completed');

      //need to update payment record to map contribution_id
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $formatted['pledge_payment_id'],
        'contribution_id', $contributionID
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
   * take the input parameter list as specified in the data model and
   * convert it into the same format that we use in QF and BAO object
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  private function deprecatedFormatParams(&$params): void {
    // copy all the contribution fields as is
    if (empty($params['pledge_id'])) {
      return;
    }
    // get total amount of from import fields
    $totalAmount = $params['total_amount'] ?? NULL;
    $contributionContactID = $params['contact_id'];
    // we need to get contact id $contributionContactID to
    // retrieve pledge details as well as to validate pledge ID

    // first need to check for update mode
    if (!empty($params['id'])) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      if ($params['id']) {
        $contribution->id = $params['id'];
      }

      if ($contribution->find(TRUE)) {
        if (!$totalAmount) {
          $totalAmount = $contribution->total_amount;
        }
      }
      else {
        throw new CRM_Core_Exception('No match found for specified contact in pledge payment data. Row was skipped.', CRM_Import_Parser::ERROR);
      }
    }

    if (!empty($params['pledge_id'])) {
      if (CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $contributionContactID) {
        throw new CRM_Core_Exception('Invalid Pledge ID provided. Contribution row was skipped.', CRM_Import_Parser::ERROR);
      }
    }

    // we need to check if oldest payment amount equal to contribution amount
    $pledgePaymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($params['pledge_id']);

    if ($pledgePaymentDetails['amount'] == $totalAmount) {
      $params['pledge_payment_id'] = $pledgePaymentDetails['id'];
    }
    else {
      throw new CRM_Core_Exception('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.', CRM_Import_Parser::ERROR);
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
      // The double __ is a quickform hack - the 'real' name is dotted - eg. 'soft_credit.contact.id'
      'name' => str_replace('__', '.', $fieldMapping[0]),
      'mapping_id' => $mappingID,
      'column_number' => $columnNumber,
      'entity_data' => !empty($fieldMapping[1]) ? ['soft_credit' => ['soft_credit_type_id' => $fieldMapping[1]]] : NULL,
    ];
  }

  /**
   * @param int $contributionID
   *
   * @throws \CRM_Core_Exception
   */
  protected function deleteExistingSoftCredit(int $contributionID): void {
    //Delete all existing soft Contribution from contribution_soft table for pcp_id is_null
    $existingSoftCredit = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contributionID);
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

  /**
   * Save the contact.
   *
   * @param string $entity
   * @param array $contact
   *
   * @return int|null
   *
   * @throws \Civi\API\Exception\UnauthorizedException|\CRM_Core_Exception
   */
  protected function saveContact(string $entity, array $contact): ?int {
    if (in_array($this->getActionForEntity($entity), ['update', 'save', 'create'])) {
      return Contact::save()
        ->setRecords([$contact])
        ->execute()
        ->first()['id'];
    }
    return NULL;
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
    if (empty($mappedField['name'])) {
      return '';
    }
    $title = [];
    $title[] = $this->getFieldMetadata($mappedField['name'])['title'];
    if (isset($mappedField['soft_credit'])) {
      $title[] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $mappedField['soft_credit']['soft_credit_type_id']);
    }

    return implode(' - ', $title);
  }

  /**
   * Create or update the note.
   *
   * @param int $contributionID
   * @param int $contactID
   * @param array $noteParams
   *
   * @throws \CRM_Core_Exception
   */
  protected function processNote(int $contributionID, int $contactID, array $noteParams): void {
    if (!$noteParams['note']) {
      return;
    }
    $noteParams = array_merge([
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contributionID,
      'contact_id' => $contactID,
    ], $noteParams);
    if ($this->isUpdateExisting()) {
      $note = Note::get(FALSE)
        ->addSelect('entity_table', '=', 'civicrm_contribution')
        ->addSelect('entity_id', '=', $contributionID)->execute()->first();
      if (!empty($note)) {
        $noteParams['id'] = $note['id'];
      }
    }
    Note::save(FALSE)->setRecords([$noteParams])->execute();
  }

  /**
   * Get the actions to display in the rich UI.
   *
   * Filter by the input actions - e.g ['update' 'select'] will only return those keys.
   *
   * @param array $actions
   * @param string $entity
   *
   * @return array
   */
  protected function getActions(array $actions, $entity = 'Contact'): array {
    $actionList['Contact'] = [
      'ignore' => [
        'id' => 'ignore',
        'text' => ts('No action'),
        'description' => ts('Contact not altered'),
      ],
      'select' => [
        'id' => 'select',
        'text' => ts('Match existing Contact'),
        'description' => ts('Look up existing contact. Skip row if not found'),
      ],
      'update' => [
        'id' => 'update',
        'text' => ts('Update existing Contact.'),
        'description' => ts('Update existing Contact. Skip row if not found'),
      ],
      'save' => [
        'id' => 'save',
        'text' => ts('Update existing Contact or Create'),
        'description' => ts('Create new contact if not found'),
      ],
    ];
    return array_values(array_intersect_key($actionList[$entity], array_fill_keys($actions, TRUE)));
  }

}
