<?php

use Civi\API\EntityLookupTrait;

/**
 * Trait implements functions to retrieve contribution related values.
 */
trait CRM_Contribute_Form_ContributeFormTrait {

  use EntityLookupTrait;

  /**
   * Get the value for a field relating to the contribution.
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
  public function getContributionValue(string $fieldName) {
    if ($this->isDefined('Contribution')) {
      return $this->lookup('Contribution', $fieldName);
    }
    $id = $this->getContributionID();
    if ($id) {
      $this->define('Contribution', 'Contribution', ['id' => $id]);
      return $this->lookup('Contribution', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    throw new CRM_Core_Exception('`getContributionID` must be implemented');
  }

  /**
   * Get id of contribution page being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionPageID(): ?int {
    throw new CRM_Core_Exception('`ContributionPageID` must be implemented');
  }

  public function getPledgeBlockValue(string $fieldName): ?int {
    if (!$this->getPledgeBlockID()) {
      return NULL;
    }
    if (!$this->isDefined('PledgeBlock')) {
      $this->define('PledgeBlock', 'PledgeBlock', [
        'id' => $this->getPledgeBlockID(),
        'entity_table' => 'civicrm_contribution_page',
      ]);
    }
    return $this->lookup('PledgeBlock', $fieldName);
  }

  /**
   * Get a value from the contribution being acted on.
   *
   * All values returned in apiv4 format. Escaping may be required.
   *
   * @param string $fieldName
   *
   * @return mixed
   * @noinspection PhpUnhandledExceptionInspection
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   */
  public function getContributionPageValue(string $fieldName) {
    if ($this->isDefined('ContributionPage')) {
      return $this->lookup('ContributionPage', $fieldName);
    }
    $id = $this->getContributionPageID();
    if ($id) {
      $this->define('ContributionPage', 'ContributionPage', ['id' => $id]);
      return $this->lookup('ContributionPage', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the value for a field relating to the contribution recur record.
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
  public function getContributionRecurValue(string $fieldName) {
    if ($this->isDefined('ContributionRecur')) {
      return $this->lookup('ContributionRecur', $fieldName);
    }
    $id = $this->getContributionRecurID();
    if ($id) {
      $this->define('ContributionRecur', 'ContributionRecur', ['id' => $id]);
      return $this->lookup('ContributionRecur', $fieldName);
    }
    return NULL;
  }

  /**
   * Get the selected Contribution Recur ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionRecurID(): ?int {
    throw new CRM_Core_Exception('`getContributionRecurID` must be implemented');
  }

  /**
   * Get the selected Pledge Block ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getPledgeBlockID(): ?int {
    throw new CRM_Core_Exception('`getPledgeBlockID` must be implemented');
  }

  /**
   * It the given entity enabled.
   *
   * This could be moved - maybe to the EntityLookupTrait. For now
   * making it private should make it easy to revisit.
   *
   * @param string $entity
   * @return bool
   */
  private function isEntityEnabled(string $entity): bool {
    return array_key_exists($entity, \Civi::service('action_object_provider')->getEntities());
  }

}
