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

use Civi\Api4\Contribution;

/**
 * Class CRM_Core_Payment_BaseIPN.
 */
class CRM_Core_Payment_BaseIPN {

  public static $_now = NULL;

  /**
   * Input parameters from payment processor. Store these so that
   * the code does not need to keep retrieving from the http request
   * @var array
   */
  protected $_inputParameters = [];

  /**
   * Only used by AuthorizeNetIPN.
   * @var bool
   *
   * @deprecated
   *
   */
  protected $_isRecurring = FALSE;

  /**
   * Only used by AuthorizeNetIPN.
   * @var bool
   *
   * @deprecated
   *
   */
  protected $_isFirstOrLastRecurringPayment = FALSE;

  /**
   * Constructor.
   */
  public function __construct() {
    CRM_Core_Error::deprecatedWarning('CRM_Core_Payment_BaseIPN will be removed around 6.6 - if you see this warning your payment processor needs to be updated!');
    self::$_now = date('YmdHis');
  }

  /**
   * Store input array on the class.
   *
   * @param array $parameters
   *
   * @throws CRM_Core_Exception
   */
  public function setInputParameters($parameters) {
    if (!is_array($parameters)) {
      throw new CRM_Core_Exception('Invalid input parameters');
    }
    $this->_inputParameters = $parameters;
  }

  /**
   * Set contribution to failed.
   *
   * @param array $objects
   *
   * @deprecated use the api.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function failed($objects) {
    CRM_Core_Error::deprecatedFunctionWarning('use the api');
    $contribution = &$objects['contribution'];

    $participant = &$objects['participant'];
    $contribution->contribution_status_id = CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Failed');
    $contribution->save();

    if ($participant) {
      $this->cancelParticipant($participant->id);
    }

    Civi::log()->debug("Setting contribution status to Failed");
    return TRUE;
  }

  /**
   * Handled pending contribution status.
   *
   * @deprecated
   *
   * @param array $objects
   * @param object $transaction
   *
   * @return bool
   */
  public function pending(&$objects, &$transaction) {
    CRM_Core_Error::deprecatedFunctionWarning('This function will be removed at some point');
    $transaction->commit();
    Civi::log()->debug('Returning since contribution status is Pending');
    echo 'Success: Returning since contribution status is pending<p>';
    return TRUE;
  }

  /**
   * Process cancelled payment outcome.
   *
   * @deprecated The intended replacement code is
   *
   * Contribution::update(FALSE)->setValues([
   *  'cancel_date' => 'now',
   *  'contribution_status_id:name' => 'Cancelled',
   * ])->addWhere('id', '=', $contribution->id)->execute();
   *
   * @param array $objects
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function cancelled($objects) {
    CRM_Core_Error::deprecatedFunctionWarning('Use Contribution create api to cancel the contribution');
    $contribution = &$objects['contribution'];

    if (empty($contribution->id)) {
      // This code is believed to be unreachable.
      // this entire function is due to be deprecated in the near future so
      // this code will live in a deprecated function until it gets removed.
      $addLineItems = TRUE;
      // CRM-15546
      $contributionStatuses = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id', [
        'labelColumn' => 'name',
        'flip' => 1,
      ]);
      $contribution->contribution_status_id = $contributionStatuses['Cancelled'];
      $contribution->cancel_date = self::$_now;
      $contribution->save();

      $memberships = [];
      if (!empty($objects['membership'])) {
        $memberships = &$objects['membership'];
        if (is_numeric($memberships)) {
          $memberships = [$objects['membership']];
        }
      }
      if (!empty($memberships)) {
        foreach ($memberships as $membership) {
          if ($membership) {
            $this->cancelMembership($membership, $membership->status_id);
          }
        }
      }
      $participant = &$objects['participant'];

      if ($participant) {
        $this->cancelParticipant($participant->id);
      }
    }
    else {
      Contribution::update(FALSE)->setValues([
        'cancel_date' => 'now',
        'contribution_status_id:name' => 'Cancelled',
      ])->addWhere('id', '=', $contribution->id)->execute();
    }

    Civi::log()->debug("Setting contribution status to Cancelled");
    return TRUE;
  }

  /**
   * Rollback unhandled outcomes.
   *
   * @deprecated
   *
   * @param array $objects
   * @param CRM_Core_Transaction $transaction
   *
   * @return bool
   */
  public function unhandled(&$objects, &$transaction) {
    CRM_Core_Error::deprecatedFunctionWarning('This function will be removed at some point');
    $transaction->rollback();
    Civi::log()->debug('Returning since contribution status is not handled');
    echo 'Failure: contribution status is not handled<p>';
    return FALSE;
  }

  /**
   * Logic to cancel a participant record when the related contribution changes to failed/cancelled.
   * @todo This is part of a bigger refactor for dev/core/issues/927 - "duplicate" functionality exists in CRM_Contribute_BAO_Contribution::cancel()
   *
   * @deprecated
   *
   * @param $participantID
   *
   * @throws \CRM_Core_Exception
   */
  private function cancelParticipant($participantID) {
    // @fixme https://lab.civicrm.org/dev/core/issues/927 Cancelling membership etc is not desirable for all use-cases and we should be able to disable it
    $participantParams['id'] = $participantID;
    $participantParams['status_id'] = 'Cancelled';
    civicrm_api3('Participant', 'create', $participantParams);
  }

  /**
   * @deprecated
   * Get site billing ID.
   *
   * @param array $ids
   *
   * @return bool
   */
  public function getBillingID(&$ids) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_BAO_LocationType::getBilling()');
    $ids['billing'] = CRM_Core_BAO_LocationType::getBilling();
    if (!$ids['billing']) {
      CRM_Core_Error::debug_log_message(ts('Please set a location type of %1', [1 => 'Billing']));
      echo "Failure: Could not find billing location type<p>";
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @deprecated
   *
   * @todo confirm this function is not being used by any payment processor outside core & remove.
   *
   * Note that the compose message part has been moved to contribution
   * In general LoadObjects is called first to get the objects but the composeMessageArray function now calls it
   *
   * @param array $input
   *   Incoming data from Payment processor.
   * @param array $ids
   *   Related object IDs.
   * @param array $objects
   *
   * @throws \CRM_Core_Exception
   */
  public function sendMail($input, $ids, $objects) {
    CRM_Core_Error::deprecatedFunctionWarning('this should be done via completetransaction api');
    civicrm_api3('Contribution', 'sendconfirmation', [
      'id' => $objects['contribution']->id,
    ]);
  }

}
