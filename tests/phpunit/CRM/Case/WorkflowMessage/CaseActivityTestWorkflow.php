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

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Sample to test test model architecture.
 *
 * @see CRM_Case_BAO_Case::sendActivityCopy
 * @support template-only
 */
class CRM_Case_WorkflowMessage_CaseActivityTestWorkflow extends GenericWorkflowMessage {

  const WORKFLOW = 'case_activity_test';

  /**
   * The recipient of the notification. The `{contact.*}` tokens will reference this person.
   *
   * Example: ['contact_id' => 123, 'display_name' => 'Bob Roberts', role => 'FIXME']
   *
   * @var array|null
   * @scope tokenContext, tplParams
   * @fkEntity Contact
   * @required
   */
  public $contact;

  /**
   * The primary contact associated with this case (eg `civicrm_case_contact.contact_id`).
   *
   * Existing callers are inconsistent about setting this parameter.
   *
   * By default, CiviCRM allows one client on any given case, and this should reflect
   * that contact. However, some systems may enable multiple clients per case.
   * This field may not make sense in the long-term.
   *
   * @var int
   * @scope tplParams as client_id
   * @fkEntity Contact
   */
  public $clientId;

  /**
   * @var string
   * @scope tplParams
   * @required
   */
  public $activitySubject;

  /**
   * @var string
   * @scope tplParams
   * @required
   */
  public $activityTypeName;

  /**
   * Unique ID for this activity. Unique and difficult to guess.
   *
   * @var string
   * @scope tplParams
   * @required
   */
  public $idHash;

  /**
   * @var bool
   * @scope tplParams
   * @required
   */
  public $isCaseActivity;

  /**
   * @var string
   * @scope tplParams
   */
  public $editActURL;

  /**
   * @var string
   * @scope tplParams
   */
  public $viewActURL;

  /**
   * @var string
   * @scope tplParams
   */
  public $manageCaseURL;

  /**
   * List of conventional activity fields.
   *
   * Example: [['label' => ..., 'category' => ..., 'type' => ..., 'value' => ...]]
   *
   * @var array
   * @scope tplParams as activity.fields
   * @required
   */
  public $activityFields;

  /**
   * List of custom activity fields, grouped by CustomGroup.
   *
   * Example: ['My Custom Stuff' => [['label' => ..., 'category' => ..., 'type' => ..., 'value' => ...]]]
   *
   * @var array
   * @scope tplParams as activity.customGroups
   */
  public $activityCustomGroups = [];

}
