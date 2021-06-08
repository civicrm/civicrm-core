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

namespace Civi\Api4\Generic\Traits;

/**
 * An optionList is a small entity whose primary purpose is to supply a semi-static list of options to fields in other entities.
 *
 * The options appear in the field metadata for other entities that reference this one via pseudoconstant.
 *
 * Note: At time of writing, this trait does nothing except add "OptionList" to the "type" in Entity::get() metadata.
 */
trait OptionList {

}
