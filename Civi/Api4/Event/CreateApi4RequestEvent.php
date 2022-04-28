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
 * Unused event, kept around to prevent undefined class errors in extensions that listen for it.
 * @deprecated
 */
class CreateApi4RequestEvent extends GenericHookEvent {

}
