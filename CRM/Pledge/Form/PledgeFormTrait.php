<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements getContactValue + overridable getContactID functions.
 *
 * These are commonly used on forms - although getContactID() would often
 * be overridden. By using these functions it is not necessary to know
 * if the Contact ID has already been defined as `getContactID()` will retrieve
 * them form the values available (unless it is yet to be created).
 */
trait CRM_Pledge_Form_PledgeFormTrait {

  use EntityLookupTrait;

  /**
   * Get id of Pledge being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getPledgeID(): ?int {
    throw new CRM_Core_Exception('`getPledgeID` must be implemented');
  }

  /**
   * Get a value from the Pledge being acted on.
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
   *
   * @throws \CRM_Core_Exception
   */
  public function getPledgeValue(string $fieldName) {
    if ($this->isDefined('Pledge')) {
      return $this->lookup('Pledge', $fieldName);
    }
    $id = $this->getPledgeID();
    if ($id) {
      $this->define('Pledge', 'Pledge', ['id' => $id]);
      return $this->lookup('Pledge', $fieldName);
    }
    return NULL;
  }

}
