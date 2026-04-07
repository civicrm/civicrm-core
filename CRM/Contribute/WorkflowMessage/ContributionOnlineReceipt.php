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
 * Receipt sent when confirming contribution add payment.
 *
 * Add payment, complete order and the front end contribution form
 * result in an email send using this, unless an event is involved.
 * In addition the api contribution.sendconfirmation and the search task
 * call this.
 *
 * @support template-only
 * @see CRM_Contribute_BAO_ContributionPage::sendMail
 */
class CRM_Contribute_WorkflowMessage_ContributionOnlineReceipt extends GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  use CRM_Core_WorkflowMessage_SingleProfileTrait;
  public const WORKFLOW = 'contribution_online_receipt';

  /**
   * Array of soft credit types.
   *
   * Only used when this template is used for non-online receipts - ie no
   * contribution page.
   *
   * This is pretty clumsy & would be good to replace. The template
   * has to combine 2 arrays. Definitely do not expand to other templates.
   *
   * @var array
   *
   * @scope tplParams as softCreditTypes
   */
  public $softCreditTypes;

  /**
   * The soft credit type of the honor block profile.
   *
   * @var string
   *
   * @scope tplParams as soft_credit_type
   */
  public $softCreditType;

  /**
   * Array of soft credits.
   *
   * This is pretty clumsy & would be good to replace. The template
   * has to combine 2 arrays. Definitely do not expand to other templates.
   *
   * @var array
   *
   * @scope tplParams as softCredits
   */
  public $softCreditsForOffline;

  public function getSoftCreditForOffline(): array {
    $offlineSoftCredits = [];
    if (!$this->getProfilesByModule('soft_credit')) {
      foreach ($this->getSoftCredits() as $softCredit) {
        $offlineSoftCredits[] = [
          ts('Name') => $softCredit['contact_id.display_name'],
          ts('Amount') => CRM_Utils_Money::format($softCredit['amount'], $softCredit['currency']),
        ];
      }
    }
    return $offlineSoftCredits;
  }

}
