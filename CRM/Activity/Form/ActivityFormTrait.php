<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements functions to retrieve activity related values.
 */
trait CRM_Activity_Form_ActivityFormTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the activity.
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
  public function getActivityValue(string $fieldName) {
    if ($this->isDefined('Activity')) {
      return $this->lookup('Activity', $fieldName);
    }
    $id = $this->getActivityID();
    if ($id) {
      $this->define('Activity', 'Activity', ['id' => $id]);
      return $this->lookup('Activity', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Activity ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getActivityID(): ?int {
    throw new CRM_Core_Exception('`getActivityID` must be implemented');
  }

}
