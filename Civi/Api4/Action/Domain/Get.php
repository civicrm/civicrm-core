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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


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
