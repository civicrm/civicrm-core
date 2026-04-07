<?php

/**
 * Interface to provide standardised behaviour from case form classes
 */
interface CRM_Case_Form_CaseFormInterface {

  /**
   * Get the selected Case ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getCaseID(): ?int;

}
