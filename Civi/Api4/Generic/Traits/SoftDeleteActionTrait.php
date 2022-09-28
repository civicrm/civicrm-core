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
 * This trait is used by delete actions with a "move to trash" option.
 * @method $this setUseTrash(bool $useTrash) Pass FALSE to force delete and bypass trash
 * @method bool getUseTrash()
 */
trait SoftDeleteActionTrait {

  /**
   * Should $ENTITY be moved to the trash instead of permanently deleted?
   * @var bool
   */
  protected $useTrash = TRUE;

}
