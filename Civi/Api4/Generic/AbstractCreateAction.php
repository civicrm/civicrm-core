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

namespace Civi\Api4\Generic;

use Civi\Api4\Event\ValidateValuesEvent;
use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Utils\CoreUtil;

/**
 * Base class for all `Create` api actions.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractCreateAction extends AbstractAction {

  use Traits\GetSetValueTrait;

  /**
   * Field values to set for the new $ENTITY.
   *
   * @var array
   */
  protected $values = [];

  /**
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function validateValues() {
    // FIXME: There should be a protocol to report a full list of errors... Perhaps a subclass of CRM_Core_Exception?
    $unmatched = $this->checkRequiredFields($this->getValues());
    if ($unmatched) {
      throw new \CRM_Core_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }

    if ($this->checkPermissions && !CoreUtil::checkAccessRecord($this, $this->getValues(), \CRM_Core_Session::getLoggedInContactID() ?: 0)) {
      throw new UnauthorizedException("ACL check failed");
    }

    $e = new ValidateValuesEvent($this, [$this->getValues()], new \CRM_Utils_LazyArray(function () {
      return [['old' => NULL, 'new' => $this->getValues()]];
    }));
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

}
