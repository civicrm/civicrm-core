<?php

namespace Civi\Api4\Generic;

/**
 * Base class for all "Get" api actions.
 *
 * @package Civi\Api4\Generic
 *
 * @method $this addSelect(string $select)
 * @method $this setSelect(array $selects)
 * @method array getSelect()
 */
abstract class AbstractGetAction extends AbstractQueryAction {

  /**
   * Fields to return. Defaults to all non-custom fields.
   *
   * @var array
   */
  protected $select = [];

}
