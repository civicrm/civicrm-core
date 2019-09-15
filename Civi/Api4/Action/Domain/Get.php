<?php

namespace Civi\Api4\Action\Domain;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * Return only the current domain.
   *
   * @var bool
   */
  protected $currentDomain = FALSE;

  /**
   * @inheritDoc
   */
  protected function getObjects() {
    if ($this->currentDomain) {
      $this->addWhere('id', '=', \CRM_Core_Config::domainID());
    }
    return parent::getObjects();
  }

}
