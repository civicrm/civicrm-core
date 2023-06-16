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

namespace Civi\Api4\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Unused event.
 * Kept around because of https://lab.civicrm.org/dev/joomla/-/issues/28
 * @see Events::POST_SELECT_QUERY
 * @deprecated
 */
class PostSelectQueryEvent extends GenericHookEvent {

}
