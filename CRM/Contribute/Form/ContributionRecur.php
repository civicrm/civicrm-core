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
 * Shared parent class for recurring contribution forms.
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Contribution ID.
   *
   * @var int
   */
  protected $_coid = NULL;

  /**
   * Contribution Recur ID.
   *
   * @var int
   */
  protected $_crid = NULL;

  /**
   * The recurring contribution id, used when editing the recurring contribution.
   *
   * For historical reasons this duplicates _crid & since the name is more meaningful
   * we should probably deprecate $_crid.
   *
   * @var int
   */
  protected $contributionRecurID = NULL;

  /**
   * Membership ID.
   *
   * @var int
   */
  protected $_mid = NULL;

  /**
   * Payment processor object.
   *
   * @var \CRM_Core_Payment
   */
  protected $_paymentProcessorObj = NULL;

  /**
   * Current payment processor.
   *
   * This includes a copy of the object in 'object' key for legacy reasons.
   *
   * @var array
   */
  public $_paymentProcessor = [];

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (option) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   * @var array
   */
  protected $entityFields = [];

  /**
   * Details of the subscription (recurring contribution) to be altered.
   *
   * @var array
   */
  protected $subscriptionDetails = [];

  /**
   * Is the form being accessed by a front end user to update their own recurring.
   *
   * @var bool
   */
  protected $selfService;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'ContributionRecur';
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->contributionRecurID;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->setAction(CRM_Core_Action::UPDATE);
    $this->_mid = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE);
    $this->_crid = CRM_Utils_Request::retrieve('crid', 'Integer', $this, FALSE);
    $this->contributionRecurID = $this->_crid;
    $this->_coid = CRM_Utils_Request::retrieve('coid', 'Integer', $this, FALSE);
    $this->setSubscriptionDetails();
    $this->setPaymentProcessor();
    if ($this->getSubscriptionContactID()) {
      $this->set('cid', $this->getSubscriptionContactID());
    }
  }

  /**
   * Set the payment processor object up.
   *
   * This is a function that needs to be better consolidated between the inheriting forms
   * but this is good choice of function to call.
   */
  protected function setPaymentProcessor() {
    if ($this->_crid) {
      $this->_paymentProcessor = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessor($this->contributionRecurID);
      if (!$this->_paymentProcessor) {
        CRM_Core_Error::statusBounce(ts('There is no valid processor for this subscription so it cannot be updated'));
      }
      $this->_paymentProcessorObj = $this->_paymentProcessor['object'];
    }
    elseif ($this->_mid) {
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_mid, 'membership', 'obj');
      $this->_paymentProcessor = $this->_paymentProcessorObj->getPaymentProcessor();
    }
  }

  /**
   * Set the subscription details on the form.
   */
  protected function setSubscriptionDetails() {
    if ($this->contributionRecurID) {
      $this->subscriptionDetails = $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_crid);
    }
    elseif ($this->_coid) {
      $this->subscriptionDetails = $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_coid, 'contribution');
    }
    elseif ($this->_mid) {
      $this->subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_mid, 'membership');
    }
    // This is being set temporarily - we should eventually just use the getter fn.
    $this->_subscriptionDetails = $this->subscriptionDetails;
  }

  /**
   * Get details for the recurring contribution being altered.
   *
   * @return array
   */
  public function getSubscriptionDetails() {
    return $this->subscriptionDetails;
  }

  /**
   * Get the contact ID for the subscription.
   *
   * @return int|false
   */
  protected function getSubscriptionContactID() {
    $sub = $this->getSubscriptionDetails();
    return isset($sub->contact_id) ? $sub->contact_id : FALSE;
  }

  /**
   * Is this being used by a front end user to update their own recurring.
   *
   * @return bool
   */
  protected function isSelfService() {
    if (!is_null($this->selfService)) {
      return $this->selfService;
    }
    $this->selfService = FALSE;
    if (!CRM_Core_Permission::check('edit contributions')) {
      if ($this->_subscriptionDetails->contact_id != $this->getContactID()) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to cancel this recurring contribution.'));
      }
      $this->selfService = TRUE;
    }
    return $this->selfService;
  }

}
