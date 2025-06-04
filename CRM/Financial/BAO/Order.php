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

use Civi\Api4\Contribution;
use Civi\Api4\Generic\Result;
use Civi\Api4\LineItem;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Order class.
 *
 * This class is intended to become the object to manage orders, including via Order.api.
 *
 * As of writing it is in the process of having appropriate functions built up.
 * It should **NOT** be accessed directly outside of tested core methods as it
 * may change.
 *
 * @internal
 */
class CRM_Financial_BAO_Order {

  /**
   * Price set id.
   *
   * @var int
   */
  protected $priceSetID;

  /**
   * Selected price items in the format we see in forms.
   *
   * ie.
   * [price_3 => 4, price_10 => 7]
   * is equivalent to 'option_value 4 for radio price field 3 and
   * a quantity of 7 for text price field 10.
   *
   * @var array
   */
  protected $priceSelection = [];

  /**
   * Override for financial type id.
   *
   * Used when the financial type id is to be overridden for all line items
   * (as can happen in backoffice forms)
   *
   * @var int
   */
  protected $overrideFinancialTypeID;

  /**
   * Overridable financial type id.
   *
   * When this is set only this financial type will be overridden.
   *
   * This is relevant to repeat transactions where we want to
   * override the type on the line items if it a different financial type has
   * been saved against the recurring contribution. However, it the line item
   * financial type differs from the contribution financial type then we
   * treat this as deliberately uncoupled and don't flow through changes
   * in financial type down to the line items.
   *
   * This is covered in testRepeatTransactionUpdatedFinancialTypeAndNotEquals.
   *
   * @var int
   */
  protected $overridableFinancialTypeID;

  private $isExcludeExpiredFields = FALSE;

  private array $contributionValues;

  private ?int $existingContributionID = NULL;

  /**
   * @param bool $isExcludeExpiredFields
   *
   * @return CRM_Financial_BAO_Order
   */
  public function setIsExcludeExpiredFields(bool $isExcludeExpiredFields): CRM_Financial_BAO_Order {
    $this->isExcludeExpiredFields = $isExcludeExpiredFields;
    return $this;
  }

  /**
   * Get overridable financial type id.
   *
   * If only one financial type id can be overridden at the line item level
   * then get it here, otherwise NULL.
   *
   * @return int|null
   */
  public function getOverridableFinancialTypeID(): ?int {
    return $this->overridableFinancialTypeID;
  }

  /**
   * Set overridable financial type id.
   *
   * If only one financial type id can be overridden at the line item level
   * then set it here.
   *
   * @param int|null $overridableFinancialTypeID
   */
  public function setOverridableFinancialTypeID(?int $overridableFinancialTypeID): void {
    $this->overridableFinancialTypeID = $overridableFinancialTypeID;
  }

  /**
   * Financial type id to use for any lines where is is not provided.
   *
   * @var int
   */
  protected $defaultFinancialTypeID;

  /**
   * ID of a contribution to be used as a template.
   *
   * @var int
   */
  protected $templateContributionID;

  /**
   * Should we permit the line item financial type to be overridden when there is more than one line.
   *
   * Historically the answer is 'yes' for v3 order api and 'no' for repeattransaction
   * and backoffice forms.
   *
   * @var bool
   */
  protected $isPermitOverrideFinancialTypeForMultipleLines = FALSE;

  /**
   * @return bool
   */
  public function isPermitOverrideFinancialTypeForMultipleLines(): bool {
    return $this->isPermitOverrideFinancialTypeForMultipleLines;
  }

  /**
   * @param bool $isPermitOverrideFinancialTypeForMultipleLines
   */
  public function setIsPermitOverrideFinancialTypeForMultipleLines(bool $isPermitOverrideFinancialTypeForMultipleLines): void {
    $this->isPermitOverrideFinancialTypeForMultipleLines = $isPermitOverrideFinancialTypeForMultipleLines;
  }

  /**
   * Number of line items.
   *
   * @var int
   */
  protected $lineItemCount;

  /**
   * @return int
   */
  public function getLineItemCount(): int {
    if (!isset($this->lineItemCount)) {
      $this->lineItemCount = count($this->getPriceOptions()) || count($this->lineItems);
    }
    return $this->lineItemCount;
  }

  /**
   * @param int $lineItemCount
   */
  public function setLineItemCount(int $lineItemCount): void {
    $this->lineItemCount = $lineItemCount;
  }

  /**
   * @return int|null
   */
  public function getTemplateContributionID(): ?int {
    return $this->templateContributionID;
  }

  /**
   * @param int $templateContributionID
   */
  public function setTemplateContributionID(int $templateContributionID): void {
    $this->templateContributionID = $templateContributionID;
  }

  /**
   * @return int
   */
  public function getDefaultFinancialTypeID(): int {
    return $this->defaultFinancialTypeID;
  }

  /**
   * Set the default financial type id to be used when the line has none.
   *
   * @param int|null $defaultFinancialTypeID
   */
  public function setDefaultFinancialTypeID(?int $defaultFinancialTypeID): void {
    $this->defaultFinancialTypeID = $defaultFinancialTypeID;
  }

  /**
   * Override for the total amount of the order.
   *
   * When there is a single line item the order total may be overriden.
   *
   * @var float
   */
  protected $overrideTotalAmount;

  /**
   * Override for the total amount of the order exclusive of tax.
   *
   * We set this when that is what is known by the form, in order to 'prefer'
   * the raw form when it comes to forwards / backwards calculations.
   *
   * When there is a single line item the order total may be overriden.
   *
   * @var float|null
   */
  protected ?float $overrideTotalAmountTaxExclusive = NULL;

  /**
   * Line items in the order.
   *
   * @var array
   */
  protected $lineItems = [];

  /**
   * Array of entities ordered.
   *
   * @var array
   */
  protected $entityParameters = [];

  /**
   * Default price sets for component.
   *
   * @var array
   */
  protected $defaultPriceSets = [];

  /**
   * Cache of the default price field.
   *
   * @var array
   */
  protected $defaultPriceField;

  /**
   * Cache of the default price field value ID.
   *
   * @var array
   */
  protected $defaultPriceFieldValueID;

  /**
   * Get parameters for the entities bought as part of this order.
   *
   * @return array
   *
   * @internal core tested code only.
   *
   */
  public function getEntitiesToCreate(): array {
    $entities = [];
    foreach ($this->entityParameters as $entityToCreate) {
      if (in_array($entityToCreate['entity'], ['participant', 'membership'], TRUE)) {
        $entities[] = $entityToCreate;
      }
    }
    return $entities;
  }

  /**
   * Set parameters for the entities bought as part of this order.
   *
   * @param array $entityParameters
   * @param int|string $key indexing reference
   *
   * @internal core tested code only.
   *
   */
  public function setEntityParameters(array $entityParameters, $key): void {
    $this->entityParameters[$key] = $entityParameters;
  }

  /**
   * Add a line item to an entity.
   *
   * The v3 api supports more than on line item being stored against a given
   * set of entity parameters. There is some doubt as to whether this is a
   * good thing that should be supported in v4 or something that 'seemed
   * like a good idea at the time' - but this allows the lines to be added from the
   * v3 api.
   *
   * @param string $lineIndex
   * @param string $entityKey
   */
  public function addLineItemToEntityParameters(string $lineIndex, string $entityKey): void {
    $this->entityParameters[$entityKey]['entity'] = $this->getLineItemEntity($lineIndex);
    $this->entityParameters[$entityKey]['line_references'][] = $lineIndex;
  }

  /**
   * Metadata for price fields.
   *
   * @var array
   */
  protected $priceFieldMetadata = [];

  /**
   * Metadata for price field values.
   *
   * @var array
   */
  protected $priceFieldValueMetadata = [];

  /**
   * Metadata for price sets.
   *
   * @var array
   */
  protected $priceSetMetadata = [];

  /**
   * Get form object.
   *
   * @internal use in tested core code only.
   *
   * @return \CRM_Core_Form|NULL
   */
  public function getForm(): ?CRM_Core_Form {
    return $this->form;
  }

  /**
   * Set form object.
   *
   * @internal use in tested core code only.
   *
   * @param \CRM_Core_Form|null $form
   */
  public function setForm(?CRM_Core_Form $form): void {
    $this->form = $form;
  }

  /**
   * The serialize & unserialize functions are to prevent the form being serialized & stored.
   *
   * The form could be potentially large & circular.
   *
   * We simply serialize the values needed to re-serialize the form.
   *
   * @return array
   */
  public function _serialize(): array {
    return [
      'OverrideTotalAmount' => $this->getOverrideTotalAmount(),
      'OverrideFinancialType' => $this->getOverrideFinancialTypeID(),
      'PriceSelection' => $this->getPriceSelection(),
      'OverrideTotalAmountTaxExclusive' => $this->getOverrideTotalAmountTaxExclusive(),
    ];
  }

  /**
   * Re-instantiate the the class with non-calculated variables.
   *
   * @param array $data
   */
  public function _unserialize(array $data): void {
    foreach ($data as $key => $value) {
      $fn = 'set' . $key;
      $this->$fn($value);
    }
  }

  /**
   * Form object - if present the buildAmount hook will be called.
   *
   * @var \CRM_Member_Form_Membership|\CRM_Member_Form_MembershipRenewal|\CRM_Contribute_Form_Contribution
   */
  protected $form;

  /**
   * Get Set override for total amount of the order.
   *
   * @internal use in tested core code only.
   *
   * @return float|false
   */
  public function getOverrideTotalAmount() {
    // The override amount is only valid for quick config price sets where more
    // than one field has not been selected.
    if (!$this->overrideTotalAmount || $this->getLineItemCount() > 1) {
      return FALSE;
    }
    return $this->overrideTotalAmount;
  }

  /**
   * Is the line item financial type to be overridden.
   *
   * We have a tested scenario for repeatcontribution where the line item
   * does not match the top level financial type for the order. In this case
   * any financial type override has been determined to NOT apply to the line items.
   *
   * This is locked in via testRepeatTransactionUpdatedFinancialTypeAndNotEquals.
   *
   * @param int $financialTypeID
   *
   * @return bool
   */
  public function isOverrideLineItemFinancialType(int $financialTypeID) {
    if (!$this->getOverrideFinancialTypeID()) {
      return FALSE;
    }
    if (!$this->getOverridableFinancialTypeID()) {
      return TRUE;
    }
    return $this->getOverridableFinancialTypeID() === $financialTypeID;
  }

  /**
   * Set override for total amount.
   *
   * @internal use in tested core code only.
   *
   * @param float|null $overrideTotalAmount
   */
  public function setOverrideTotalAmount(?float $overrideTotalAmount): void {
    $this->overrideTotalAmount = $overrideTotalAmount;
  }

  public function setOverrideTotalAmountTaxExclusive(float $overrideTotalAmountTaxExclusive): void {
    $this->overrideTotalAmountTaxExclusive = $overrideTotalAmountTaxExclusive;
    if ($this->getOverrideFinancialTypeID()) {
      $taxRate = $this->getTaxRate($this->getOverrideFinancialTypeID());
      $this->setOverrideTotalAmount($overrideTotalAmountTaxExclusive + ($overrideTotalAmountTaxExclusive * $taxRate));
    }
  }

  public function getOverrideTotalAmountTaxExclusive(): ?float {
    return $this->overrideTotalAmountTaxExclusive;
  }

  /**
   * Get override for total amount.
   *
   * @internal use in tested core code only.
   *
   * @return int| FALSE
   */
  public function getOverrideFinancialTypeID() {
    // We don't permit overrides if there is more than one line.
    // The reason for this constraint may be more historical since
    // the case could be made that if it is set it should be used and
    // we have built out the tax calculations a lot now.
    if (!$this->isPermitOverrideFinancialTypeForMultipleLines() && $this->getLineItemCount() > 1) {
      return FALSE;
    }
    return $this->overrideFinancialTypeID ?? FALSE;
  }

  /**
   * Set override for financial type ID.
   *
   * @internal use in tested core code only.
   *
   * @param int|null $overrideFinancialTypeID
   */
  public function setOverrideFinancialTypeID(?int $overrideFinancialTypeID): void {
    $this->overrideFinancialTypeID = $overrideFinancialTypeID;
  }

  /**
   * Getter for price set id.
   *
   * @internal use in tested core code only.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  public function getPriceSetID(): int {
    if (!$this->priceSetID) {
      foreach ($this->getPriceOptions() as $fieldID => $valueID) {
        $this->setPriceSetIDFromSelectedField($fieldID);
      }
    }
    return $this->priceSetID;
  }

  /**
   * Setter for price set id.
   *
   * @internal use in tested core code only.
   *
   * @param int $priceSetID
   */
  public function setPriceSetID(int $priceSetID) {
    $this->priceSetID = $priceSetID;
  }

  /**
   * Set price set id to the default.
   *
   * @param string $component [membership|contribution]
   *
   * @throws \CRM_Core_Exception
   * @internal use in tested core code only.
   */
  public function setPriceSetToDefault(string $component): void {
    $this->priceSetID = $this->getDefaultPriceSetForComponent($component);
  }

  /**
   * Set price set ID based on the contribution page id.
   *
   * @internal use in tested core code only.
   *
   * @param int $contributionPageID
   *
   */
  public function setPriceSetIDByContributionPageID(int $contributionPageID): void {
    $this->setPriceSetIDByEntity('contribution_page', $contributionPageID);
  }

  /**
   * Set price set ID based on the event id.
   *
   * @internal use in tested core code only.
   *
   * @param int $eventID
   *
   * @throws \CRM_Core_Exception
   */
  public function setPriceSetIDByEventPageID(int $eventID): void {
    $this->setPriceSetIDByEntity('event', $eventID);
  }

  /**
   * Set the price set id based on looking up the entity.
   *
   * @internal use in tested core code only.
   *
   * @param string $entity
   * @param int $id
   *
   */
  protected function setPriceSetIDByEntity(string $entity, int $id): void {
    $this->priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_' . $entity, $id);
  }

  /**
   * Getter for price selection.
   *
   * @internal use in tested core code only.
   *
   * @return array
   */
  public function getPriceSelection(): array {
    return $this->priceSelection;
  }

  /**
   * Setter for price selection.
   *
   * @internal use in tested core code only.
   *
   * @param array $priceSelection
   */
  public function setPriceSelection(array $priceSelection) {
    $this->priceSelection = $priceSelection;
  }

  /**
   * Price options the simplified price fields selections.
   *
   * ie. the 'price_' is stripped off the key name and the field ID
   * is cast to an integer.
   *
   * @internal use in tested core code only.
   *
   * @return array
   */
  public function getPriceOptions():array {
    $priceOptions = [];
    foreach ($this->getPriceSelection() as $fieldName => $value) {
      $fieldID = substr($fieldName, 6);
      $priceOptions[(int) $fieldID] = $value;
    }
    return $priceOptions;
  }

  /**
   * Get the metadata for the given field.
   *
   * @internal use in tested core code only.
   *
   * @param int $id
   *
   * @return array
   */
  public function getPriceFieldSpec(int $id) :array {
    return $this->getPriceFieldsMetadata()[$id] ?? $this->getPriceFieldMetadata($id);
  }

  /**
   * Get the metadata for the given field value.
   *
   * @internal use in tested core code only.
   *
   * @param int $id
   *
   * @return array
   */
  public function getPriceFieldValueSpec(int $id) :array {
    if (!isset($this->priceFieldValueMetadata[$id])) {
      $this->priceFieldValueMetadata[$id] = PriceFieldValue::get(FALSE)->addWhere('id', '=', $id)->execute()->first();
    }
    return $this->priceFieldValueMetadata[$id];
  }

  /**
   * Get the default values for line items using this price field value id.
   *
   * @param int $id
   *
   * @return array
   */
  public function getPriceFieldValueDefaults(int $id): array {
    $valueDefaults = array_intersect_key($this->getPriceFieldValueSpec($id), array_fill_keys([
      'financial_type_id',
      'non_deductible_amount',
      'membership_type_id',
      'membership_num_terms',
      'amount',
      'description',
      'price_field_id',
      'label',
    ], TRUE));
    $valueDefaults['qty'] = 1;
    $valueDefaults['unit_price'] = $valueDefaults['amount'];
    unset($valueDefaults['amount']);
    return $valueDefaults;
  }

  /**
   * Get the metadata for the fields in the price set.
   *
   * @internal use in tested core code only.
   *
   * @return array
   */
  public function getPriceFieldsMetadata(): array {
    if (empty($this->priceFieldMetadata)) {
      $this->getPriceSetMetadata();
    }
    return $this->priceFieldMetadata;
  }

  /**
   * Get the metadata for the given price field.
   *
   * Note this uses a different method to getPriceFieldMetadata.
   *
   * There is an assumption in the code currently that all purchases
   * are within a single price set. However, discussions have been around
   * the idea that when form-builder supports contributions price sets will
   * not be used as form-builder in itself is a configuration unit.
   *
   * Currently there are couple of unit tests that mix & match & rather than
   * updating the tests to avoid notices when orders are loaded for receipting,
   * the migration to this new method is starting....
   *
   * @param int $id
   *
   * @return array
   */
  public function getPriceFieldMetadata(int $id): array {
    return CRM_Price_BAO_PriceField::getPriceField($id);
  }

  /**
   * Set the metadata for the order.
   *
   * @param array $metadata
   */
  protected function setPriceFieldMetadata(array $metadata): void {
    foreach ($metadata as $index => $priceField) {
      $metadata[$index]['supports_auto_renew'] = FALSE;
      if ($this->isExcludeExpiredFields && !$priceField['is_active']) {
        unset($metadata[$index]);
        continue;
      }
      if ($this->isExcludeExpiredFields && !empty($priceField['active_on']) && time() < strtotime($priceField['active_on'])) {
        unset($metadata[$index]);
        continue;
      }
      elseif ($this->isExcludeExpiredFields && !empty($priceField['expire_on']) && strtotime($priceField['expire_on']) < time()) {
        unset($metadata[$index]);
        continue;
      }
      elseif (!empty($priceField['options'])) {
        foreach ($priceField['options'] as $optionID => $option) {
          if (!$option['is_active']) {
            unset($metadata[$index]['options'][$optionID]);
          }
          elseif (!empty($option['membership_type_id'])) {
            $membershipType = CRM_Member_BAO_MembershipType::getMembershipType((int) $option['membership_type_id']);
            $metadata[$index]['options'][$optionID]['membership_type_id.auto_renew'] = (int) $membershipType['auto_renew'];
            $metadata[$index]['supports_auto_renew'] = $metadata[$index]['supports_auto_renew'] ?? $membershipType['auto_renew'] ?: (bool) $membershipType['auto_renew'];
          }
          else {
            $metadata[$index]['options'][$optionID]['membership_type_id.auto_renew'] = NULL;
          }
        }
      }
    }
    $this->priceFieldMetadata = $metadata;

    if ($this->getForm()) {
      CRM_Utils_Hook::buildAmount($this->form->getFormContext(), $this->form, $this->priceFieldMetadata);
    }
  }

  /**
   * Get the metadata for the fields in the price set.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal use in tested core code only.
   *
   */
  public function getPriceSetMetadata(): array {
    if (empty($this->priceSetMetadata)) {
      $this->priceSetMetadata = CRM_Price_BAO_PriceSet::getCachedPriceSetDetail($this->getPriceSetID());
      $this->priceSetMetadata['id'] = $this->getPriceSetID();
      $this->setPriceFieldMetadata($this->priceSetMetadata['fields']);
      unset($this->priceSetMetadata['fields']);
    }
    return $this->priceSetMetadata;
  }

  public function isMembershipPriceSet() {
    if (!CRM_Core_Component::isEnabled('CiviMember')) {
      return FALSE;
    }
    // Access the property if set, to avoid a potential loop when the hook is called.
    $priceSetMetadata = $this->priceSetMetadata ?: $this->getPriceSetMetadata();
    return in_array(CRM_Core_Component::getComponentID('CiviMember'), $priceSetMetadata['extends'], FALSE);
  }

  /**
   * Get the financial type id for the order.
   *
   * @internal use in tested core code only.
   *
   * This may differ to the line items....
   *
   * @return int
   */
  public function getFinancialTypeID(): int {
    return (int) $this->getOverrideFinancialTypeID() ?: $this->getPriceSetMetadata()['financial_type_id'];
  }

  /**
   * Set the price field selection from an array of params containing price
   * fields.
   *
   * This function takes the sort of 'anything & everything' parameters that
   * come in from the form layer and filters them before assigning them to the
   * priceSelection property.
   *
   * @param array $input
   *
   * @throws \CRM_Core_Exception
   */
  public function setPriceSelectionFromUnfilteredInput(array $input): void {
    foreach ($input as $fieldName => $value) {
      if (str_starts_with($fieldName, 'price_')) {
        $fieldID = substr($fieldName, 6);
        if (is_numeric($fieldID)) {
          $this->priceSelection[$fieldName] = $value;
        }
      }
    }
    if (empty($this->priceSelection) && isset($input['total_amount'])
      && is_numeric($input['total_amount']) && !empty($input['financial_type_id'])) {
      $this->priceSelection['price_' . $this->getDefaultPriceFieldID()] = $input['total_amount'];
      $this->setOverrideFinancialTypeID($input['financial_type_id']);
    }
  }

  /**
   * Get the id of the price field to use when just an amount is provided.
   *
   * @throws \CRM_Core_Exception
   *
   * @return int
   */
  public function getDefaultPriceFieldID():int {
    if (!$this->defaultPriceField) {
      $this->defaultPriceField = PriceField::get(FALSE)
        ->addWhere('name', '=', 'contribution_amount')
        ->addWhere('price_set_id.name', '=', 'default_contribution_amount')
        ->execute()->first();
    }
    return $this->defaultPriceField['id'];
  }

  /**
   * Get the id of the price field to use when just an amount is provided.
   *
   * @throws \CRM_Core_Exception
   *
   * @return int
   */
  public function getDefaultPriceFieldValueID():int {
    if (!$this->defaultPriceFieldValueID) {
      $this->defaultPriceFieldValueID = PriceFieldValue::get(FALSE)
        ->addWhere('name', '=', 'contribution_amount')
        ->addWhere('price_field_id.name', '=', 'contribution_amount')
        ->execute()->first()['id'];
    }
    return $this->defaultPriceFieldValueID;
  }

  /**
   * Get line items.
   *
   * return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getLineItems():array {
    if (empty($this->lineItems)) {
      $this->lineItems = $this->calculateLineItems();
    }
    return $this->lineItems;
  }

  /**
   * Is participant count being used.
   *
   * This would be true if at least one price field value
   * has a count value that is not 0 or NULL. In this case
   * the row value will be used for the participant.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function isUseParticipantCount(): bool {
    foreach ($this->getPriceFieldsMetadata() as $fieldMetadata) {
      foreach ($fieldMetadata['options'] as $option) {
        if (($option['count'] ?? 0) > 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Recalculate the line items.
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  public function recalculateLineItems(): void {
    $this->lineItems = $this->calculateLineItems();
  }

  /**
   * Get line items in a 'traditional' indexing format.
   *
   * This ensures the line items are indexed by
   * price field id - as required by the contribution BAO.
   *
   * @throws \CRM_Core_Exception
   */
  public function getPriceFieldIndexedLineItems(): array {
    $lines = [];
    foreach ($this->getLineItems() as $item) {
      $lines[$item['price_field_id']] = $item;
    }
    return $lines;
  }

  /**
   * Get line items that specifically relate to memberships.
   *
   * return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getMembershipLineItems():array {
    $lines = $this->getLineItems();
    foreach ($lines as $index => $line) {
      if (empty($line['membership_type_id'])) {
        unset($lines[$index]);
        continue;
      }
      if (empty($line['membership_num_terms'])) {
        $lines[$index]['membership_num_terms'] = 1;
      }
    }
    return $lines;
  }

  /**
   * Get line items that specifically relate to participants.
   *
   * return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getParticipantLineItems():array {
    $lines = $this->getLineItems();
    foreach ($lines as $index => $line) {
      if ($line['entity_table'] !== 'civicrm_participant') {
        unset($lines[$index]);
        continue;
      }
    }
    return $lines;
  }

  /**
   * Get an array of all membership types included in the order.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getMembershipTypes(): array {
    $types = [];
    foreach ($this->getMembershipLineItems() as $line) {
      $types[$line['membership_type_id']] = CRM_Member_BAO_MembershipType::getMembershipType((int) $line['membership_type_id']);
    }
    return $types;
  }

  /**
   * Get an array of all membership types included in the order.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getRenewableMembershipTypes(): array {
    $types = [];
    foreach ($this->getMembershipTypes() as $id => $type) {
      if (!empty($type['auto_renew'])) {
        $types[$id] = $type;
      }
    }
    return $types;
  }

  /**
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function calculateLineItems(): array {
    $lineItems = [];
    $params = $this->getPriceSelection();
    if ($this->getOverrideTotalAmount() !== FALSE) {
      // We need to do this to keep getLine from doing weird stuff but the goal
      // is to ditch getLine next round of refactoring
      // and make the code more sane.
      $params['total_amount'] = $this->getOverrideTotalAmount();
    }

    // Dummy value to prevent e-notice in getLine. We calculate tax in this class.
    $params['financial_type_id'] = 0;
    if ($this->getTemplateContributionID()) {
      $lineItems = $this->getLinesFromTemplateContribution();
      // Set the price set ID from the first line item (we need to set this here
      // to prevent a loop later when we retrieve the price field metadata to
      // set the 'title' (as accessed from workflow message templates).
      // Contributions *should* all have line items, but historically, imports did not create them.
      if ($lineItems) {
        $this->setPriceSetID($lineItems[0]['price_field_id.price_set_id']);
      }
    }
    elseif ($this->getExistingContributionID()) {
      $lineItems = $this->getLinesForContribution();
      // Set the price set ID from the first line item (we need to set this here
      // to prevent a loop later when we retrieve the price field metadata to
      // set the 'title' (as accessed from workflow message templates).
      // Contributions *should* all have line items, but historically, imports did not create them.
      if ($lineItems) {
        $firstItem = reset($lineItems);
        $this->setPriceSetID($firstItem['price_field_id.price_set_id']);
      }
    }
    else {
      foreach ($this->getPriceOptions() as $fieldID => $valueID) {
        if ($valueID !== '') {
          $this->setPriceSetIDFromSelectedField($fieldID);
          $throwAwayArray = [];
          $temporaryParams = $params;
          // @todo - still using getLine for now but better to bring it to this class & do a better job.
          $newLines = CRM_Price_BAO_PriceSet::getLine($temporaryParams, $throwAwayArray, $this->getPriceSetID(), $this->getPriceFieldSpec($fieldID), $fieldID)[1];
          foreach ($newLines as $newLine) {
            $lineItems[$newLine['price_field_value_id']] = $newLine;
          }
        }
      }
    }
    // Set the line item count here because it is needed to determine whether
    // we can use overrides and would not be set yet if we have loaded them from
    // a template contribution.
    $this->setLineItemCount(count($lineItems));

    foreach ($lineItems as &$lineItem) {
      // Set the price set id if not set above. Note that the above
      // requires it for line retrieval but we want to fix that as it
      // should not be required at that point.
      $this->setPriceSetIDFromSelectedField($lineItem['price_field_id']);
      // Set any pre-calculation to zero as we will calculate.
      $lineItem['tax_amount'] = 0;
      if ($this->isOverrideLineItemFinancialType($lineItem['financial_type_id']) !== FALSE) {
        $lineItem['financial_type_id'] = $this->getOverrideFinancialTypeID();
      }
      $lineItem['tax_rate'] = $taxRate = $this->getTaxRate((int) $lineItem['financial_type_id']);
      if ($this->getOverrideTotalAmount() !== FALSE) {
        $this->addTotalsToLineBasedOnOverrideTotal((int) $lineItem['financial_type_id'], $lineItem);
      }
      elseif ($taxRate) {
        $lineItem['tax_amount'] = ($taxRate / 100) * $lineItem['line_total'];
      }
      $lineItem['membership_type_id'] ??= NULL;
      if ($lineItem['membership_type_id']) {
        $lineItem['entity_table'] = 'civicrm_membership';
        $lineItem['membership_num_terms'] = $lineItem['membership_num_terms'] ?:1;
      }
      $lineItem['title'] = $this->getLineItemTitle($lineItem);
      $lineItem['tax_rate'] = $taxRate = $this->getTaxRate((int) $lineItem['financial_type_id']);
      if ($this->getOverrideTotalAmount() !== FALSE) {
        $this->addTotalsToLineBasedOnOverrideTotal((int) $lineItem['financial_type_id'], $lineItem);
      }
      elseif ($this->getPriceFieldMetadata($lineItem['price_field_id'])['name'] === 'other_amount') {
        // Other amount is a front end user entered form. It is reasonable to think it would be tax inclusive.
        $lineItem['line_total_inclusive'] = $lineItem['line_total'];
        $lineItem['line_total'] = $lineItem['line_total_inclusive'] ? $lineItem['line_total_inclusive'] / (1 + ($lineItem['tax_rate'] / 100)) : 0;
        $lineItem['tax_amount'] = round($lineItem['line_total_inclusive'] - $lineItem['line_total'], 2);
        // Make sure they still add up to each other afer the rounding.
        $lineItem['line_total'] = $lineItem['line_total_inclusive'] - $lineItem['tax_amount'];
        $lineItem['qty'] = 1;
        $lineItem['unit_price'] = $lineItem['line_total'];

      }
      elseif ($taxRate) {
        $lineItem['tax_amount'] = ($taxRate / 100) * $lineItem['line_total'];
      }
      $lineItem['line_total_inclusive'] = $lineItem['line_total_inclusive'] ?? ($lineItem['line_total'] + $lineItem['tax_amount']);
    }
    return $lineItems;
  }

  /**
   * Get the total amount for the order.
   *
   * @return float
   *
   * @throws \CRM_Core_Exception
   */
  public function getTotalTaxAmount() :float {
    $amount = 0.0;
    foreach ($this->getLineItems() as $lineItem) {
      $amount += $lineItem['tax_amount'] ?? 0.0;
    }
    return $amount;
  }

  /**
   * Get the total amount for the order.
   *
   * @return float
   *
   * @throws \CRM_Core_Exception
   */
  public function getTotalAmount() :float {
    $amount = 0.0;
    foreach ($this->getLineItems() as $lineItem) {
      $amount += ($lineItem['line_total'] ?? 0.0) + ($lineItem['tax_amount'] ?? 0.0);
    }
    return $amount;
  }

  /**
   * Get Amount Level text.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getAmountLevel() : string {
    $amount_level = [];
    $totalParticipant = 0;
    foreach ($this->getLineItems() as $lineItem) {
      if ($lineItem['label'] !== ts('Contribution Amount')) {
        $amount_level[] = $lineItem['label'] . ' - ' . (float) $lineItem['qty'];
      }
      $totalParticipant += (float) ($lineItem['participant_count'] ?? 0);
    }
    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    if (!empty($amount_level)) {
      $amountString = CRM_Utils_Array::implodePadded($amount_level);
      if (!empty($displayParticipantCount)) {
        $amountString = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amount_level) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
      }
    }
    return $amountString ?? '';
  }

  /**
   * Get the total amount relating to memberships for the order.
   *
   * @return float
   *
   * @throws \CRM_Core_Exception
   */
  public function getMembershipTotalAmount() :float {
    $amount = 0.0;
    foreach ($this->getMembershipLineItems() as $lineItem) {
      $amount += ($lineItem['line_total'] ?? 0.0) + ($lineItem['tax_amount'] ?? 0.0);
    }
    return $amount;
  }

  /**
   * Get the tax rate for the given financial type.
   *
   * @param int $financialTypeID
   *
   * @return float
   */
  public function getTaxRate(int $financialTypeID) {
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    if (!isset($taxRates[$financialTypeID])) {
      return 0;
    }
    return $taxRates[$financialTypeID];
  }

  /**
   * @param $fieldID
   *
   * @throws \CRM_Core_Exception
   */
  protected function setPriceSetIDFromSelectedField($fieldID): void {
    if (!isset($this->priceSetID)) {
      $this->setPriceSetID(PriceField::get(FALSE)
        ->addSelect('price_set_id')
        ->addWhere('id', '=', $fieldID)
        ->execute()
        ->first()['price_set_id']);
    }
  }

  /**
   * Set the line item.
   *
   * This function augments the line item where possible. The calling code
   * should not attempt to set taxes. This function allows minimal values
   * to be passed for the default price sets - ie if only membership_type_id is
   * specified the price_field_id and price_value_id will be determined.
   *
   * @param array $lineItem
   * @param int|string $index
   *
   * @throws \CRM_Core_Exception
   * @internal tested core code usage only.
   * @internal use in tested core code only.
   *
   */
  public function setLineItem(array $lineItem, $index): void {
    if (!isset($this->priceSetID)) {
      if (!empty($lineItem['price_field_id'])) {
        $this->setPriceSetIDFromSelectedField($lineItem['price_field_id']);
      }
      else {
        // we are using either the default membership or default contribution
        // If membership type is passed in we use the default price field.
        $component = !empty($lineItem['membership_type_id']) ? 'membership' : 'contribution';
        $this->setPriceSetToDefault($component);
      }
    }
    if (!isset($lineItem['financial_type_id'])) {
      if (!empty($lineItem['price_field_value_id'])
        && $lineItem['price_field_value_id'] !== $this->getDefaultPriceFieldValueID()) {
        // We have a price field value ID and this value is not the default one used for
        // 'generic' line items, so we can load the financial type ID from it.
        $lineItem['financial_type_id'] = $this->getPriceFieldValueSpec($lineItem['price_field_value_id'])['financial_type_id'];
      }
      else {
        $lineItem['financial_type_id'] = $this->getDefaultFinancialTypeID();
      }
    }
    if (!isset($lineItem['membership_type_id']) && !empty($lineItem['price_field_value_id'])) {
      $lineItem['membership_type_id'] = $this->getPriceFieldValueSpec($lineItem['price_field_value_id'])['membership_type_id'];
    }
    if (!isset($lineItem['membership_num_terms']) && !empty($lineItem['price_field_value_id'])) {
      $lineItem['membership_num_terms'] = $this->getPriceFieldValueSpec($lineItem['price_field_value_id'])['membership_num_terms'];
    }
    if (!empty($lineItem['membership_type_id']) && !isset($lineItem['membership_num_terms'])) {
      $lineItem['membership_num_terms'] = 1;
    }
    if ($this->getOverrideTotalAmount()) {
      $this->addTotalsToLineBasedOnOverrideTotal((int) $lineItem['financial_type_id'], $lineItem);
    }
    else {
      if (!empty($lineItem['price_field_value_id'])) {
        // Let's make sure it is an integer not '2' for sanity.
        $lineItem['price_field_value_id'] = (int) $lineItem['price_field_value_id'];
        $lineItem = array_merge($this->getPriceFieldValueDefaults($lineItem['price_field_value_id']), $lineItem);
      }
      if (!isset($lineItem['line_total'])) {
        $lineItem['line_total'] = $lineItem['qty'] * $lineItem['unit_price'];
      }
      $lineItem['tax_rate'] = $this->getTaxRate($lineItem['financial_type_id']);
      $lineItem['tax_amount'] = ($lineItem['tax_rate'] / 100) * $lineItem['line_total'];
      $lineItem['line_total_inclusive'] = $lineItem['tax_amount'] + $lineItem['line_total'];
    }
    if (!empty($lineItem['membership_type_id'])) {
      $lineItem['entity_table'] = 'civicrm_membership';
      if (empty($lineItem['price_field_id']) && empty($lineItem['price_field_value_id'])) {
        $lineItem = $this->fillMembershipLine($lineItem);
      }
    }
    if ($this->getPriceSetID() === $this->getDefaultPriceSetForComponent('contribution')) {
      $this->fillDefaultContributionLine($lineItem);
    }
    if (empty($lineItem['label'])) {
      $lineItem['label'] = PriceFieldValue::get(FALSE)->addWhere('id', '=', (int) $lineItem['price_field_value_id'])->addSelect('label')->execute()->first()['label'];
    }
    if (empty($lineItem['price_field_id']) && !empty($lineItem['membership_type_id'])) {
      // We have to 'guess' the price field since the calling code hasn't
      // passed it in (which it really should but ... history).
      foreach ($this->priceFieldMetadata as $pricefield) {
        foreach ($pricefield['options'] ?? [] as $option) {
          if ((int) $option['membership_type_id'] === $lineItem['membership_type_id']) {
            $lineItem['price_field_id'] = $pricefield['id'];
            $lineItem['price_field_value_id'] = $option['id'];
          }
        }
      }
    }
    if (empty($lineItem['title'])) {
      $lineItem['title'] = $this->getLineItemTitle($lineItem);
    }
    $this->lineItems[$index] = $lineItem;
  }

  /**
   * Set a value on a line item.
   *
   * @internal only use in core tested code.
   *
   * @param string $name
   * @param mixed $value
   * @param string|int $index
   */
  public function setLineItemValue(string $name, $value, $index): void {
    $this->lineItems[$index][$name] = $value;
  }

  /**
   * Set the line item array.
   *
   * This is mostly useful when they have been calculated & stored
   * on the form & we want to rebuild the line item object.
   *
   * @param array $lineItems
   */
  public function setLineItems(array $lineItems): void {
    $this->lineItems = $lineItems;
  }

  /**
   * @param int|string $index
   *
   * @return string
   */
  public function getLineItemEntity($index):string {
    // @todo - ensure entity_table is set in setLineItem, go back to enotices here.
    return str_replace('civicrm_', '', ($this->lineItems[$index]['entity_table'] ?? 'contribution'));
  }

  /**
   * Get the ordered line item.
   *
   * @param string|int $index
   *
   * @return array
   */
  public function getLineItem($index): array {
    return $this->lineItems[$index];
  }

  /**
   * Fills in additional data for the membership line.
   *
   * The minimum requirement is the membership_type_id and that priceSetID is set.
   *
   * @param array $lineItem
   *
   * @return array
   */
  protected function fillMembershipLine(array $lineItem): array {
    $fields = $this->getPriceFieldsMetadata();
    foreach ($fields as $field) {
      if (!isset($lineItem['price_field_value_id'])) {
        foreach ($field['options'] as $option) {
          if ((int) $option['membership_type_id'] === (int) $lineItem['membership_type_id']) {
            $lineItem['price_field_id'] = $field['id'];
            $lineItem['price_field_id.label'] = $field['label'];
            $lineItem['price_field_value_id'] = $option['id'];
            $lineItem['qty'] = 1;
          }
        }
      }
      if (isset($lineItem['price_field_value_id'], $field['options'][$lineItem['price_field_value_id']])) {
        $option = $field['options'][$lineItem['price_field_value_id']];
      }
    }
    $lineItem['unit_price'] = $lineItem['line_total'] ?? $option['amount'];
    $lineItem['label'] ??= $option['label'];
    $lineItem['field_title'] ??= $option['label'];
    $lineItem['financial_type_id'] = $lineItem['financial_type_id'] ?: ($this->getDefaultFinancialTypeID() ?? $option['financial_type_id']);
    return $lineItem;
  }

  /**
   * Add total_amount and tax_amount to the line from the override total.
   *
   * @param int $financialTypeID
   * @param array $lineItem
   *
   * @return void
   */
  protected function addTotalsToLineBasedOnOverrideTotal(int $financialTypeID, array &$lineItem): void {
    $lineItem['tax_rate'] = $taxRate = $this->getTaxRate($financialTypeID);
    if ($taxRate) {
      // Total is tax inclusive.
      $taxExclusiveAmount = $this->getOverrideTotalAmountTaxExclusive();
      if ($taxExclusiveAmount) {
        $lineItem['line_total'] = $taxExclusiveAmount;
        $lineItem['tax_amount'] = ($taxRate / 100) * $taxExclusiveAmount;
        // Set to 1 for consistency with historical behaviour on the only form that calls this section.
        // There may be a case to set unit_price to 1 & qty to x
        $lineItem['qty'] = 1;
      }
      else {
        $lineItem['tax_amount'] = ($taxRate / 100) * $this->getOverrideTotalAmount() / (1 + ($taxRate / 100));
        $lineItem['line_total'] = $this->getOverrideTotalAmount() - $lineItem['tax_amount'];
      }
    }
    else {
      $lineItem['line_total'] = $this->getOverrideTotalAmountTaxExclusive() ?: $this->getOverrideTotalAmount();
      $lineItem['tax_amount'] = 0.0;
      $lineItem['line_total_inclusive'] = $lineItem['line_total'];
    }
    if ($this->getExistingContributionID() && $lineItem['unit_price'] === 1.0) {
      // Perhaps the existing contribution ID check can go...
      $lineItem['qty'] = $lineItem['line_total'];
    }
    if (!empty($lineItem['qty'])) {
      $lineItem['unit_price'] = $lineItem['line_total'] / $lineItem['qty'];
    }
    else {
      $lineItem['unit_price'] = $lineItem['line_total'];
    }
  }

  /**
   * Get the line items from a template.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getLinesFromTemplateContribution(): array {
    // Rekey the array to a 0 index with array_merge.
    $lines = array_merge($this->getLinesForContribution());
    foreach ($lines as &$line) {
      // The apiv4 insists on adding id - so let it get all the details
      // and we will filter out those that are not part of a template here.
      unset($line['id'], $line['contribution_id']);
    }
    return $lines;
  }

  /**
   * Get the constructed line items formatted for the v3 Order api.
   *
   * @return array
   *
   * @internal core tested code only.
   *
   * @throws \CRM_Core_Exception
   */
  public function getLineItemForV3OrderApi(): array {
    $lineItems = [];
    foreach ($this->getLineItems() as $key => $line) {
      $lineItems[] = [
        'line_item' => [$line['price_field_value_id'] => $line],
        'params' => $this->entityParameters[$key] ?? [],
      ];
    }
    return $lineItems;
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getLinesForContribution(): array {
    return (array) LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $this->getExistingContributionID() ?: $this->getTemplateContributionID())
      ->setSelect([
        'contribution_id',
        'entity_id',
        'entity_table',
        'price_field_id',
        'price_field_id.label',
        'price_field_id.price_set_id',
        'price_field_value_id',
        'financial_type_id',
        'label',
        'qty',
        'unit_price',
        'line_total',
        'tax_amount',
        'non_deductible_amount',
        'participant_count',
        'membership_num_terms',
      ])
      ->execute()->indexBy('id');
  }

  /**
   * Get the default price set id for the given component.
   *
   * @param string $component
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getDefaultPriceSetForComponent(string $component): int {
    if (!isset($this->defaultPriceSets[$component])) {
      $this->defaultPriceSets[$component] = PriceSet::get(FALSE)
        ->addWhere('name', '=', ($component === 'membership' ? 'default_membership_type_amount' : 'default_contribution_amount'))
        ->execute()
        ->first()['id'];
    }
    return $this->defaultPriceSets[$component];
  }

  /**
   * Fill in values for a default contribution line item.
   *
   * @param array $lineItem
   *
   * @throws \CRM_Core_Exception
   */
  protected function fillDefaultContributionLine(array &$lineItem): void {
    $defaults = [
      'qty' => 1,
      'price_field_id' => $this->getDefaultPriceFieldID(),
      'price_field_id.label' => $this->defaultPriceField['label'],
      'price_field_value_id' => $this->getDefaultPriceFieldValueID(),
      'entity_table' => 'civicrm_contribution',
      'unit_price' => $lineItem['line_total'],
      'label' => ts('Contribution Amount'),
    ];
    $lineItem = array_merge($defaults, $lineItem);
  }

  /**
   * Get a 'title' for the line item.
   *
   * This descriptor is used in message templates. It could conceivably
   * by used elsewhere but if so determination would likely move to the api.
   *
   * @param array $lineItem
   *
   * @return string
   */
  private function getLineItemTitle(array $lineItem): string {
    // Title is used in output for workflow templates.
    $htmlType = $this->getPriceFieldSpec($lineItem['price_field_id'])['html_type'] ?? NULL;
    $lineItemTitle = (!$htmlType || $htmlType === 'Text') ? $lineItem['label'] : $this->getPriceFieldSpec($lineItem['price_field_id'])['label'] . ' - ' . $lineItem['label'];
    if (!empty($lineItem['price_field_value_id'])) {
      $description = $this->priceFieldValueMetadata[$lineItem['price_field_value_id']]['description'] ?? '';
      if ($description) {
        $lineItemTitle .= ' ' . CRM_Utils_String::ellipsify($description, 30);
      }
    }
    return $lineItemTitle ?? '';
  }

  /**
   * @param array $contributionValues
   *
   * @return \Civi\Api4\Generic\Result
   *
   * @internal Access through apiv4 Order api only. Signature subject to change.
   *
   * @throws \CRM_Core_Exception
   */
  public function save(array $contributionValues): Result {
    $this->contributionValues = $contributionValues;
    foreach ($this->getLineItems() as $index => $lineItem) {
      // Save entities first, so we can get the Entity ID.
      if ($lineItem['entity_table'] !== 'civicrm_contribution') {
        $this->setLineItemValue('entity_id', $this->saveLineItemEntity($lineItem), $index);
      }
    }
    $contributionValues['total_amount'] = $this->getTotalAmount();
    $contributionValues['tax_amount'] = $this->getTotalTaxAmount();
    $contributionValues['amount_level'] = $this->getAmountLevel();
    $contributionValues['contribution_status_id:name'] = 'Pending';
    $contributionValues['line_item'] = [$this->getLineItems()];
    return Contribution::create()
      ->setValues($contributionValues)->execute();
  }

  /**
   * Save the entity related to a given line item.
   *
   * @param array $lineItem
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  private function saveLineItemEntity(array $lineItem): int {
    $entity = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($lineItem['entity_table']);
    $entityValues = empty($lineItem['entity_id']) ? [] : ['id' => $lineItem['entity_id']];
    foreach ($lineItem as $fieldName => $fieldValue) {
      if (str_starts_with($fieldName, 'entity_id.')) {
        $entityValues[substr($fieldName, 10)] = $fieldValue;
      }
      if ($fieldName === 'membership_type_id' && $entity === 'Membership') {
        $entityValues['membership_type_id'] = $fieldValue;
      }
    }
    if (empty($entityValues['id'])) {
      // Not an update, include any relevant values (e.g. contact_id) from the contribution
      // entity values if not present already in EntityFields.
      $fields = (array) civicrm_api4($entity, 'getfields')->indexBy('name');
      $carryOverFields = array_intersect_key($this->contributionValues, $fields);
      $entityValues += $carryOverFields;
      if ($entity === 'Membership' && empty($entityValues['status_id'])) {
        if (empty($entityValues['join_date'])) {
          $entityValues['join_date'] = $this->contributionValues['receive_date'];
        }
        $entityValues['status_id'] = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
          $entityValues['start_date'] ?? NULL,
            $entityValues['end_date'] ?? NULL,
            $entityValues['join_date'] ?? NULL,
          $this->contributionValues['receive_date'],
          TRUE,
          $entityValues['membership_type_id']
        )['id'];
      }
    }
    if (array_keys($entityValues) === ['id']) {
      // Nothing to save.
      return $entityValues['id'];
    }
    return civicrm_api4($entity, 'save', ['records' => [$entityValues]])->first()['id'];
  }

  public function getExistingContributionID(): ?int {
    return $this->existingContributionID;
  }

  public function setExistingContributionID(?int $existingContributionID): void {
    $this->existingContributionID = $existingContributionID;
  }

}
