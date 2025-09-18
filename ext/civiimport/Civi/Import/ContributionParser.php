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

namespace Civi\Import;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Contribution;
use Civi\Api4\ContributionSoft;
use Civi\Api4\Note;
use Civi\Api4\Order;
use Civi\Api4\Payment;

/**
 * Class to parse contribution csv files.
 */
class ContributionParser extends ImportParser {

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
   * Get the field mappings for the import.
   *
   * @return array
   *   Array of arrays with each array representing a row in the datasource.
   *   The arrays hold the following keys
   *   - name - field the row maps to
   *   In addition the following are returned but will be phased out.
   *   - contact_type - entity_data but json_encoded. Saved to civicrm_mapping_field in contact_type column
   *   - column_number = this is used for saving to civicrm_field_mapping but
   *     may be only legacy now?
   *   - soft_credit_type_id
   *
   * @throws \CRM_Core_Exception
   */
  protected function getFieldMappings(): array {
    return $this->getUserJob()['metadata']['import_mappings'] ?? [];
  }

  /**
   * Get required fields to create a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForCreate(): array {
    return ['Contribution.financial_type_id', 'Contribution.total_amount'];
  }

  /**
   * Get required fields to match a contribution.
   *
   * @return array
   */
  public function getRequiredFieldsForMatch(): array {
    return [['Contribution.id'], ['Contribution.invoice_id'], ['Contribution.trxn_id']];
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
    \CRM_Utils_Hook::importAlterMappedRow('validate', 'contribution_import', $params, $values, $this->getUserJobID());
    $this->validateParams($params);
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
      $this->validateRequiredFields($this->getRequiredFields(), $params['Contribution'], 'Contribution');
    }
    $errors = [];
    foreach ($params as $entity => $values) {
      foreach ($values as $key => $value) {
        $errors = array_merge($this->getInvalidValues($value, $key, $entity . ' '), $errors);
      }
    }
    if ($errors) {
      throw new \CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }
  }

  /**
   * The initializer code, called before the processing
   *
   * @throws \CRM_Core_Exception
   */
  public function init() {
    // Force re-load of user job.
    unset($this->userJob);
    $this->setFieldMetadata();
  }

  /**
   * Set field metadata.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = [
        '' => [

          'title' => '- ' . ts('do not import') . ' -',
        ],
      ];
      $contributionFields = $this->getImportFieldsForEntity('Contribution');
      $note = $this->getImportFieldsForEntity('Note');
      foreach ($contributionFields + $note as $fieldName => $field) {
        $field['entity_instance'] = 'Contribution';
        $field['entity_prefix'] = 'Contribution.';
        $fields['Contribution.' . $fieldName] = $field;
      }
      $fields['Contribution.note']['entity_instance'] = 'Note';
      foreach ($this->getImportFieldsForEntity('FinancialTrxn') as $paymentField) {
        $paymentField['entity_name'] = 'Contribution';
        $paymentField['entity_instance'] = 'Payment';
        $fields['Payment.' . $paymentField['name']] = $paymentField;
      }

      $contactFields = $this->getContactFields($this->getContactType(), 'Contact');
      $fields['Contribution.contact_id'] = $contactFields['Contact.id'];
      $fields['Contribution.contact_id']['match_rule'] = '*';
      $fields['Contribution.contact_id']['entity'] = 'Contribution';
      $fields['Contribution.contact_id']['html']['label'] = $fields['Contribution.contact_id']['title'];
      $fields['Contribution.contact_id']['title'] .= ' ' . ts('(match to contact)');
      $fields['Contribution.contact_id']['name'] = 'contact_id';
      $fields['Contribution.contact_id']['entity_instance'] = 'Contribution';
      $fields['Contribution.contact_id']['contact_type'] = ['Individual' => 'Individual', 'Household' => 'Household', 'Organization' => 'Organization'];
      unset($contactFields['Contact.id']);
      $fields += $contactFields + $this->getContactFields($this->getContactType(), 'SoftCreditContact');

      $fields['SoftCredit.contact.id'] = [
        'title' => ts('Soft Credit Contact ID'),
        'softCredit' => TRUE,
        'name' => 'id',
        'entity'  => 'Contact',
        'entity_instance' => 'SoftCreditContact',
        'entity_prefix' => 'soft_credit.contact.',
        'options' => FALSE,
        'type' => \CRM_Utils_Type::T_STRING,
        'contact_type' => ['Individual' => 'Individual', 'Household' => 'Household', 'Organization' => 'Organization'],
        'match_rule' => '*',
      ];

      // add pledge fields only if its is enabled
      if (\CRM_Core_Permission::access('CiviPledge')) {
        $pledgeFields = [
          'pledge_id' => [
            'title' => ts('Pledge ID'),
            'headerPattern' => '/Pledge ID/i',
            'name' => 'pledge_id',
            // This is handled as a contribution field & the goal is
            // to make it pseudofield on the contribution.
            'entity' => 'Contribution',
            'type' => \CRM_Utils_Type::T_INT,
            'options' => FALSE,
            // We mock this into the contribution because it as special handling
            // coded for this field.
            'entity_instance' => 'Contribution',
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
    $defaultSoftCreditTypeID = \CRM_Core_OptionGroup::getDefaultValue('soft_credit_type');
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
        // For now we offer create & update, but not save.
        'actions' => [
          ['id' => 'update', 'text' => ts('Update existing'), 'description' => ts('Skip if no match found')],
          ['id' => 'create', 'text' => ts('Create'), 'description' => ts('Skip if already exists')],
        ],
        'default_action' => 'create',
        'entity_name' => 'Contribution',
        'entity_title' => ts('Contribution'),
        'entity_type' => 'Contribution',
        'selected' => ['action' => 'create'],
      ],
      'Contact' => [
        'text' => ts('Contact Fields'),
        'unique_fields' => ['external_identifier', 'id'],
        'entity_type' => 'Contact',
        'supports_multiple' => FALSE,
        'actions' => $this->isUpdateExisting() ? $this->getActions(['ignore', 'update']) : $this->getActions(['select', 'update', 'save']),
        'selected' => [
          'action' => $this->isUpdateExisting() ? 'ignore' : 'select',
          'contact_type' => 'Individual',
          'dedupe_rule' => (array) $this->getDedupeRule('Individual')['name'],
        ],
        'default_action' => 'select',
        'entity_name' => 'Contact',
        'entity_title' => ts('Contribution Contact'),
      ],
      'SoftCreditContact' => [
        'text' => ts('Soft Credit Contact Fields'),
        // It turns out there is actually currently no limit - you can import multiple of the same type.
        'supports_multiple' => TRUE,
        'unique_fields' => ['external_identifier', 'id'],
        'entity_type' => 'Contact',
        'is_required' => FALSE,
        'actions' => array_merge([['id' => 'ignore', 'text' => ts('Do not import')]], $this->getActions(['select', 'update', 'save'])),
        'selected' => [
          'contact_type' => 'Individual',
          'soft_credit_type_id' => $defaultSoftCreditTypeID,
          'action' => 'ignore',
          'dedupe_rule' => (array) $this->getDedupeRule('Individual')['name'],
        ],
        'default_action' => 'ignore',
        'entity_name' => 'SoftCreditContact',
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
    $values = array_values($values);
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      \CRM_Utils_Hook::importAlterMappedRow('import', 'contribution_import', $params, $values, $this->getUserJobID());

      $contributionParams = $params['Contribution'];
      //CRM-10994
      if (isset($contributionParams['total_amount']) && $contributionParams['total_amount'] == 0) {
        $contributionParams['total_amount'] = '0.00';
      }

      $existingContribution = $this->lookupContribution($contributionParams);
      if (empty($existingContribution) && $this->isUpdateExisting()) {
        throw new \CRM_Core_Exception(ts('Matching Contribution record not found. Row was skipped.'), \CRM_Import_Parser::ERROR);
      }
      $contributionParams['id'] = $existingContribution['id'] ?? NULL;
      if (empty($contributionParams['id']) && $this->isUpdateExisting()) {
        throw new \CRM_Core_Exception('Empty Contribution and Invoice and Transaction ID. Row was skipped.', \CRM_Import_Parser::ERROR);
      }
      $contributionParams['contact_id'] = $params['Contact']['id'] = $this->getContactID($params['Contact'] ?? [], $contributionParams['contact_id'] ?? ($existingContribution['contact_id'] ?? NULL), 'Contact', $this->getDedupeRulesForEntity('Contact'));

      $softCreditParams = [];
      // A hook could have change it to be an array of arrays.
      $softCreditEntities = isset($params['SoftCreditContact']['soft_credit_type_id']) ? [$params['SoftCreditContact']] : $params['SoftCreditContact'] ?? [];
      foreach ($softCreditEntities as $index => $softCreditContact) {
        $softCreditParams[$index]['soft_credit_type_id'] = $softCreditContact['soft_credit_type_id'];
        $softCreditEntities[$index]['id'] = $this->getContactID($softCreditContact, !empty($softCreditContact['id']) ? $softCreditContact['id'] : NULL, 'SoftCreditContact', $this->getDedupeRulesForEntity('SoftCreditContact'));
        if (empty($softCreditEntities[$index]['id']) && in_array($this->getActionForEntity('SoftCreditContact'), ['update', 'select'])) {
          throw new \CRM_Core_Exception(ts('Soft Credit Contact not found'));
        }
      }

      $this->deprecatedFormatParams($contributionParams);

      // From this point on we are changing stuff - the prior rows were doing lookups and exiting
      // if the lookups failed.

      foreach ($softCreditEntities as $index => $softCreditContact) {
        $softCreditParams[$index]['contact_id'] = $this->saveContact('SoftCreditContact', $softCreditContact) ?: $softCreditContact['id'];
      }
      $contributionParams['contact_id'] = $this->saveContact('Contact', $params['Contact'] ?? []) ?: $contributionParams['contact_id'];

      if ($softCreditEntities && $this->isUpdateExisting()) {
        //need to check existing soft credit contribution, CRM-3968
        $this->deleteExistingSoftCredit($contributionParams['id']);
      }

      if ($contributionParams['id']) {
        if (!empty($params['Payment'])) {
          throw new \CRM_Core_Exception(ts('pan_truncation is mapped but not importable'));
        }
        $contributionID = Contribution::update()->setValues($contributionParams)->execute()->first()['id'];
      }
      else {
        $contributionStatus = \CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionParams['contribution_status_id'] ?? NULL) ?? 'Completed';
        if (in_array($contributionStatus, ['Cancelled', 'Refunded', 'Chargeback'])) {
          if (!empty($params['Payment'])) {
            throw new \CRM_Core_Exception(ts('pan_truncation is mapped but not importable'));
          }
          // Unit test testContributionStatusLabel shows an issue with doing Order->Update for
          // Cancelled - in terms of the created financial entities not passing validatePayments().
          // We should remove this & figure out te necessary for that test to pass but in the interim
          // let us keep the pre-order-save behaviour.
          $contributionID = Contribution::create()->setValues($contributionParams)->execute()->first()['id'];
        }
        else {
          unset($contributionParams['contribution_status_id']);
          $contributionID = Order::create()
            ->setContributionValues($contributionParams)
            ->addLineItem(['line_total_inclusive' => $contributionParams['total_amount']])
            ->execute()->first()['id'];
          if ($contributionStatus === 'Completed') {
            // Use values from Contribution as saved, in case the hooks changed any.
            $contribution = Contribution::get()
              ->addWhere('id', '=', $contributionID)
              ->execute()->single();
            Payment::create()
              ->setNotificationForCompleteOrder(FALSE)
              ->setValues(($params['Payment'] ?? []) + [
                'contribution_id' => $contributionID,
                'check_number' => $contribution['check_number'],
                'payment_instrument_id' => $contribution['payment_instrument_id'],
                'total_amount' => $contribution['total_amount'],
                'net_amount' => $contribution['net_amount'],
                'fee_amount' => $contribution['fee_amount'],
                'trxn_date' => $contribution['receive_date'],
                'trxn_id' => $contribution['trxn_id'],
                'currency' => $contribution['currency'],
              ])
              ->execute();
          }
          elseif ($contributionStatus !== 'Pending') {
            Contribution::update()
              ->addWhere('id', '=', $contributionID)
              ->setValues(['contribution_status_id:name' => $contributionStatus])
              ->execute();
          }
        }
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
        $this->setImportStatus($rowNumber, $this->getStatus(\CRM_Import_Parser::SOFT_CREDIT), '', $contributionID);
        return;
      }

      // process pledge payment assoc w/ the contribution
      $this->setImportStatus($rowNumber, $this->processPledgePayments($contributionID, $contributionParams) ? $this->getStatus(\CRM_Import_Parser::PLEDGE_PAYMENT) : $this->getStatus(self::VALID), '', $contributionID);
      return;

    }
    catch (\CRM_Core_Exception $e) {
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
      \CRM_Import_Parser::SOFT_CREDIT_ERROR => 'soft_credit_error',
      \CRM_Import_Parser::PLEDGE_PAYMENT_ERROR => 'pledge_payment_error',
      \CRM_Import_Parser::SOFT_CREDIT => 'soft_credit_imported',
      \CRM_Import_Parser::PLEDGE_PAYMENT => 'pledge_payment_imported',
      \CRM_Import_Parser::DUPLICATE => 'DUPLICATE',
      \CRM_Import_Parser::VALID => 'IMPORTED',
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
      $completeStatusID = \CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_PledgePayment', 'status_id', 'Completed');

      //need to update payment record to map contribution_id
      \CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $formatted['pledge_payment_id'],
        'contribution_id', $contributionID
      );

      \CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($formatted['pledge_id'],
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
    if (\CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $params['contact_id']) {
      throw new \CRM_Core_Exception('Invalid Pledge ID provided. Contribution row was skipped.', \CRM_Import_Parser::ERROR);
    }
    // get total amount of from import fields
    $totalAmount = $params['total_amount'] ?? NULL;

    // first need to check for update mode
    if (!empty($params['id'])) {
      $contribution = new \CRM_Contribute_DAO_Contribution();
      if ($params['id']) {
        $contribution->id = $params['id'];
      }

      if ($contribution->find(TRUE)) {
        if (!$totalAmount) {
          $totalAmount = $contribution->total_amount;
        }
      }
      else {
        throw new \CRM_Core_Exception('No match found for specified contact in pledge payment data. Row was skipped.', \CRM_Import_Parser::ERROR);
      }
    }

    // we need to check if oldest payment amount equal to contribution amount
    $pledgePaymentDetails = \CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($params['pledge_id']);

    if ($pledgePaymentDetails['amount'] == $totalAmount) {
      $params['pledge_payment_id'] = $pledgePaymentDetails['id'];
    }
    else {
      throw new \CRM_Core_Exception('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.', \CRM_Import_Parser::ERROR);
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
    ];
  }

  /**
   * @param int $contributionID
   *
   * @throws \CRM_Core_Exception
   */
  protected function deleteExistingSoftCredit(int $contributionID): void {
    //Delete all existing soft Contribution from contribution_soft table for pcp_id is_null
    $existingSoftCredit = \CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contributionID);
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
   * @param array $mappedField
   *   Field detail as would be saved in field_mapping table
   *   or as returned from getMappingFieldFromMapperInput
   *
   * @return string
   * @throws \CRM_Core_Exception
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
      $title[] = \CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $mappedField['soft_credit']['soft_credit_type_id']);
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

}
