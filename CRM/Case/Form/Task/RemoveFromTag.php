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
 */

/**
 * Search task to remove tags from activities.
 */
class CRM_Case_Form_Task_RemoveFromTag extends CRM_Case_Form_Task {

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  use CRM_Core_Form_Task_TagTrait;

}
