<?php

use Civi\API\EntityLookupTrait;

/**
 * A wrapper around EntityLookupTrait for case forms
 *
 * Your form must implement CaseFormInterface.
 */
trait CRM_Case_Form_CaseLookupTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the Case.
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
  public function getCaseValue(string $fieldName) {
    if ($this->isDefined('Case')) {
      return $this->lookup('Case', $fieldName);
    }
    $id = $this->getCaseID();
    if ($id) {
      $this->define('Case', 'Case', ['id' => $id]);
      return $this->lookup('Case', $fieldName);
    }
    return NULL;
  }

}
