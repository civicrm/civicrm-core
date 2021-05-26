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

use Civi\Api4\PriceField;

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
   * Override for the total amount of the order.
   *
   * When there is a single line item the order total may be overriden.
   *
   * @var float
   */
  protected $overrideTotalAmount;

  /**
   * Line items in the order.
   *
   * @var array
   */
  protected $lineItems = [];

  /**
   * Metadata for price fields.
   *
   * @var array
   */
  protected $priceFieldMetadata = [];

  /**
   * Metadata for price sets.
   *
   * @var array
   */
  protected $priceSetMetadata = [];

  /**
   * Get form object.
   *
   * @return \CRM_Core_Form|NULL
   */
  public function getForm(): ?CRM_Core_Form {
    return $this->form;
  }

  /**
   * Set form object.
   *
   * @param \CRM_Core_Form|NULL $form
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
   * @var \CRM_Member_Form_Membership|\CRM_Member_Form_MembershipRenewal
   */
  protected $form;

  /**
   * Get Set override for total amount of the order.
   *
   * @return float|false
   */
  public function getOverrideTotalAmount() {
    if (count($this->getPriceOptions()) !== 1) {
      return FALSE;
    }
    return $this->overrideTotalAmount ?? FALSE;
  }

  /**
   * Set override for total amount.
   *
   * @param float $overrideTotalAmount
   */
  public function setOverrideTotalAmount(float $overrideTotalAmount): void {
    $this->overrideTotalAmount = $overrideTotalAmount;
  }

  /**
   * Get override for total amount.
   *
   * @return int| FALSE
   */
  public function getOverrideFinancialTypeID() {
    if (count($this->getPriceOptions()) !== 1) {
      return FALSE;
    }
    return $this->overrideFinancialTypeID ?? FALSE;
  }

  /**
   * Set override for financial type ID.
   *
   * @param int $overrideFinancialTypeID
   */
  public function setOverrideFinancialTypeID(int $overrideFinancialTypeID) {
    $this->overrideFinancialTypeID = $overrideFinancialTypeID;
  }

  /**
   * Getter for price set id.
   *
   * @return int
   */
  public function getPriceSetID(): int {
    return $this->priceSetID;
  }

  /**
   * Setter for price set id.
   *
   * @param int $priceSetID
   */
  public function setPriceSetID(int $priceSetID) {
    $this->priceSetID = $priceSetID;
  }

  /**
   * Set price set ID based on the contribution page id.
   *
   * @param int $contributionPageID
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setPriceSetIDByContributionPageID(int $contributionPageID): void {
    $this->setPriceSetIDByEntity('contribution_page', $contributionPageID);
  }

  /**
   * Set price set ID based on the event id.
   *
   * @param int $eventID
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setPriceSetIDByEventPageID(int $eventID): void {
    $this->setPriceSetIDByEntity('event', $eventID);
  }

  /**
   * Set the price set id based on looking up the entity.
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
   * @return array
   */
  public function getPriceSelection(): array {
    return $this->priceSelection;
  }

  /**
   * Setter for price selection.
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
   * @param int $id
   *
   * @return array
   */
  public function getPriceFieldSpec(int $id) :array {
    return $this->getPriceFieldsMetadata()[$id];
  }

  /**
   * Get the metadata for the fields in the price set.
   *
   * @return array
   */
  public function getPriceFieldsMetadata(): array {
    if (empty($this->priceFieldMetadata)) {
      $this->getPriceSetMetadata();
      if ($this->getForm()) {
        CRM_Utils_Hook::buildAmount($this->form->getFormContext(), $this->form, $this->priceFieldMetadata);
      }
    }
    return $this->priceFieldMetadata;
  }

  /**
   * Get the metadata for the fields in the price set.
   *
   * @return array
   */
  public function getPriceSetMetadata(): array {
    if (empty($this->priceSetMetadata)) {
      $priceSetMetadata = CRM_Price_BAO_PriceSet::getCachedPriceSetDetail($this->getPriceSetID());
      $this->priceFieldMetadata = $priceSetMetadata['fields'];
      unset($priceSetMetadata['fields']);
      $this->priceSetMetadata = $priceSetMetadata;
    }
    return $this->priceSetMetadata;
  }

  /**
   * Get the financial type id for the order.
   *
   * This may differ to the line items....
   *
   * @return int
   */
  public function getFinancialTypeID(): int {
    return (int) $this->getOverrideFinancialTypeID() ?: $this->getPriceSetMetadata()['financial_type_id'];
  }

  /**
   * Set the price field selection from an array of params containing price fields.
   *
   * This function takes the sort of 'anything & everything' parameters that come in from the
   * form layer and filters them before assigning them to the priceSelection property.
   *
   * @param array $input
   */
  public function setPriceSelectionFromUnfilteredInput(array $input): void {
    foreach ($input as $fieldName => $value) {
      if (strpos($fieldName, 'price_') === 0) {
        $fieldID = substr($fieldName, 6);
        if (is_numeric($fieldID)) {
          $this->priceSelection[$fieldName] = $value;
        }
      }
    }
  }

  /**
   * Get line items.
   *
   * return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function getLineItems():array {
    if (empty($this->lineItems)) {
      $this->lineItems = $this->calculateLineItems();
    }
    return $this->lineItems;
  }

  /**
   * Get line items that specifically relate to memberships.
   *
   * return array
   *
   * @throws \CiviCRM_API3_Exception
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
   * Get an array of all membership types included in the order.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
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

    foreach ($this->getPriceOptions() as $fieldID => $valueID) {
      if (!isset($this->priceSetID)) {
        $this->setPriceSetID(PriceField::get()->addSelect('price_set_id')->addWhere('id', '=', $fieldID)->execute()->first()['price_set_id']);
      }
      $throwAwayArray = [];
      // @todo - still using getLine for now but better to bring it to this class & do a better job.
      $newLines = CRM_Price_BAO_PriceSet::getLine($params, $throwAwayArray, $this->getPriceSetID(), $this->getPriceFieldSpec($fieldID), $fieldID)[1];
      foreach ($newLines as $newLine) {
        $lineItems[$newLine['price_field_value_id']] = $newLine;
      }
    }

    foreach ($lineItems as &$lineItem) {
      // Set any pre-calculation to zero as we will calculate.
      $lineItem['tax_amount'] = 0;
      if ($this->getOverrideFinancialTypeID() !== FALSE) {
        $lineItem['financial_type_id'] = $this->getOverrideFinancialTypeID();
      }
      $taxRate = $this->getTaxRate((int) $lineItem['financial_type_id']);
      if ($this->getOverrideTotalAmount() !== FALSE) {
        if ($taxRate) {
          // Total is tax inclusive.
          $lineItem['tax_amount'] = ($taxRate / 100) * $this->getOverrideTotalAmount() / (1 + ($taxRate / 100));
          $lineItem['line_total'] = $lineItem['unit_price'] = $this->getOverrideTotalAmount() - $lineItem['tax_amount'];
        }
        else {
          $lineItem['line_total'] = $lineItem['unit_price'] = $this->getOverrideTotalAmount();
        }
      }
      elseif ($taxRate) {
        $lineItem['tax_amount'] = ($taxRate / 100) * $lineItem['line_total'];
      }
    }
    return $lineItems;
  }

  /**
   * Get the total amount for the order.
   *
   * @return float
   *
   * @throws \CiviCRM_API3_Exception
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
   * @throws \CiviCRM_API3_Exception
   */
  public function getTotalAmount() :float {
    $amount = 0.0;
    foreach ($this->getLineItems() as $lineItem) {
      $amount += ($lineItem['line_total'] ?? 0.0) + ($lineItem['tax_amount'] ?? 0.0);
    }
    return $amount;
  }

  /**
   * Get the total amount relating to memberships for the order.
   *
   * @return float
   *
   * @throws \CiviCRM_API3_Exception
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

}
