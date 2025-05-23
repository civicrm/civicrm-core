<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements functions to retrieve activity related values.
 */
trait CRM_Campaign_Form_CampaignFormTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the Campaign.
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
  public function getCampaignValue(string $fieldName) {
    if ($this->isDefined('Campaign')) {
      return $this->lookup('Campaign', $fieldName);
    }
    $id = $this->getCampaignID();
    if ($id) {
      $this->define('Campaign', 'Campaign', ['id' => $id]);
      return $this->lookup('Campaign', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Campaign ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getCampaignID(): ?int {
    throw new CRM_Core_Exception('`getCampaignID` must be implemented');
  }

}
