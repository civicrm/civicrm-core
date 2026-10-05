<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements functions to retrieve membership related values.
 */
trait CRM_Member_Form_MembershipFormTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the Membership.
   *
   * All values returned in apiv4 format. Escaping may be required.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @param string $fieldName
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getMembershipValue(string $fieldName) {
    if ($this->isDefined('Membership')) {
      return $this->lookup('Membership', $fieldName);
    }
    $id = $this->getMembershipID();
    if ($id) {
      $this->define('Membership', 'Membership', ['id' => $id]);
      return $this->lookup('Membership', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Membership ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getMembershipID(): ?int {
    throw new CRM_Core_Exception('`getMembershipID` must be implemented');
  }

  /**
   * Get the value for a field relating to the Membership Type of the
   * selected Membership (see getMembershipTypeID()).
   *
   * All values returned in apiv4 format. Escaping may be required.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @param string $fieldName
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getMembershipTypeValue(string $fieldName) {
    if ($this->isDefined('MembershipType')) {
      return $this->lookup('MembershipType', $fieldName);
    }
    $id = $this->getMembershipTypeID();
    if ($id) {
      $this->define('MembershipType', 'MembershipType', ['id' => $id]);
      return $this->lookup('MembershipType', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Membership Type ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getMembershipTypeID(): ?int {
    throw new CRM_Core_Exception('`getMembershipTypeID` must be implemented');
  }

}
