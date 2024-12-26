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

namespace Civi\Api4\Action\Mailing;

use Civi\Api4\Generic\DAOUpdateAction;

/**
 * @inheritDoc
 */
class UpdateAction extends DAOUpdateAction {
  use MailingSaveTrait;

}
