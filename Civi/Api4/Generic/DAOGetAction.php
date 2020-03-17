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


namespace Civi\Api4\Generic;

/**
 * Retrieve $ENTITIES based on criteria specified in the `where` parameter.
 *
 * Use the `select` param to determine which fields are returned, defaults to `[*]`.
 *
 * Perform joins on other related entities using a dot notation.
 */
class DAOGetAction extends AbstractGetAction {
  use Traits\DAOActionTrait;

  /**
   * Fields to return. Defaults to all non-custom fields `[*]`.
   *
   * Use the dot notation to perform joins in the select clause, e.g. selecting `['*', 'contact.*']` from `Email::get()`
   * will select all fields for the email + all fields for the related contact.
   *
   * @var array
   * @inheritDoc
   */
  protected $select = [];

  public function _run(Result $result) {
    $this->setDefaultWhereClause();
    $this->expandSelectClauseWildcards();
    $result->exchangeArray($this->getObjects());
  }

}
