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
 * This class provides the register functionality from a search context.
 *
 * Originally the functionality was all munged into the main Participant form.
 *
 * Ideally it would be entirely separated but for now this overrides the main form,
 * just providing a better separation of the functionality for the search vs main form.
 */
class CRM_Event_Form_Task_Register extends CRM_Event_Form_Participant {


  /**
   * Are we operating in "single mode", i.e. adding / editing only
   * one participant record, or is this a batch add operation
   *
   * ote the goal is to disentangle all the non-single stuff
   * into this form and discontinue this param.
   *
   * @var bool
   */
  public $_single = FALSE;

}
