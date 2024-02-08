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
use Civi\Token\TokenProcessor;

/**
 * This trait provides the common functionality for tasks that send sms.
 */
trait CRM_Contact_Form_Task_SMSTrait {

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    CRM_Contact_Form_Task_SMSCommon::postProcess($this);
  }

  protected function bounceOnNoActiveProviders(): void {
    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
    if (!$providersCount) {
      CRM_Core_Error::statusBounce(ts('There are no SMS providers configured, or no SMS providers are set active'));
    }
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens(): array {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contactId']]);
    return $tokenProcessor->listTokens();
  }

}
