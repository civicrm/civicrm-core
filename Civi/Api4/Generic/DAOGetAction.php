<?php

namespace Civi\Api4\Generic;

/**
 * Retrieve items based on criteria specified in the 'where' param.
 *
 * Use the 'select' param to determine which fields are returned, defaults to *.
 *
 * Perform joins on other related entities using a dot notation.
 */
class DAOGetAction extends AbstractGetAction {
  use Traits\DAOActionTrait;

  public function _run(Result $result) {
    $this->setDefaultWhereClause();
    $result->exchangeArray($this->getObjects());
  }

}
