<?php

namespace Civi\Test;

/**
 * Helpers to look up the ids of records such as types and statuses by name.
 *
 * Use these rather than hard-coding ids, which differ between installs (and
 * are deliberately offset in tests).
 *
 * Ex: $this->getFinancialTypeID('Donation')
 */
trait LookupHelperTrait {

  /**
   * Get the id of a financial type.
   *
   * @param string $name
   *   e.g. 'Donation'.
   *
   * @return int
   */
  public function getFinancialTypeID(string $name): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'financial_type_id', $name);
  }

  /**
   * Get the id of a contact type.
   *
   * @param string $name
   *   e.g. 'Individual'.
   *
   * @return int
   */
  protected function getContactTypeID(string $name): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $name, 'id', 'name');
  }

  /**
   * Get the id of a financial account.
   *
   * @param string $name
   *   e.g. 'Deposit Bank Account'.
   *
   * @return int
   */
  protected function getFinancialAccountID(string $name): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $name, 'id', 'name');
  }

  /**
   * Get the id of a payment instrument.
   *
   * @param string $name
   *   e.g. 'Check'.
   *
   * @return int
   */
  protected function getPaymentInstrumentID(string $name): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $name);
  }

  /**
   * Get the id of a location type.
   *
   * @param string $name
   *   e.g. 'Home'.
   *
   * @return int
   */
  protected function getLocationTypeID(string $name): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', $name);
  }

  /**
   * Get the id of a payment processor type.
   *
   * @param string $name
   *   e.g. 'Dummy'.
   *
   * @return int
   */
  protected function getPaymentProcessorTypeID(string $name): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessorType', $name, 'id', 'name');
  }

  /**
   * Get the id of a participant status.
   *
   * @param string $name
   *   e.g. 'Registered'.
   *
   * @return int
   */
  protected function getParticipantStatusID(string $name): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', $name);
  }

  /**
   * Get the id of a membership status.
   *
   * @param string $name
   *   e.g. 'New'.
   *
   * @return int
   */
  protected function getMembershipStatusID(string $name): int {
    return (int) \CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', $name);
  }

  /**
   * Get the id of a relationship type.
   *
   * @param string $nameAB
   *   e.g. 'Employee of'.
   *
   * @return int
   */
  protected function getRelationshipTypeID(string $nameAB): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $nameAB, 'id', 'name_a_b');
  }

  /**
   * Get the id of a profile (UFGroup).
   *
   * @param string $name
   *   e.g. 'name_and_address'.
   *
   * @return int
   */
  protected function getProfileID(string $name): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $name, 'id', 'name');
  }

  /**
   * Get the id of a tag.
   *
   * @param string $name
   *   e.g. 'Major_Donor'.
   *
   * @return int
   */
  protected function getTagID(string $name): int {
    return (int) \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $name, 'id', 'name');
  }

  /**
   * Get the id of the price field of the default contribution price set.
   *
   * @return int
   */
  protected function getDefaultContributionPriceFieldID(): int {
    $priceSet = \CRM_Price_BAO_PriceSet::getDefaultPriceSet();
    return (int) reset($priceSet)['priceFieldID'];
  }

  /**
   * Get the id of the price field value of the default contribution price set.
   *
   * @return int
   */
  protected function getDefaultContributionPriceFieldValueID(): int {
    $priceSet = \CRM_Price_BAO_PriceSet::getDefaultPriceSet();
    return (int) reset($priceSet)['priceFieldValueID'];
  }

}
