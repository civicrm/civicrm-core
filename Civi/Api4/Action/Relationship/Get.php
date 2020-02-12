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

namespace Civi\Api4\Action\Relationship;

/**
 * @inheritDoc
 *
 * Set current = true to get active, non past relationships.
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {
  use \Civi\Api4\Generic\Traits\IsCurrentTrait;

}
