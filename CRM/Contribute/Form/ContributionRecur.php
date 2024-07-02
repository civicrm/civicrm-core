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
use Civi\Api4\Membership;

/**
 * Shared parent class for recurring contribution forms.
 */
class CRM_Contribute_Form_ContributionRecur extends CRM_Core_Form {

  use CRM_Core_Form_EntityFormTrait;
  use CRM_Contribute_Form_ContributeFormTrait;
  use CRM_Member_Form_MembershipFormTrait;
  use CRM_Financial_Form_PaymentProcessorFormTrait;

  /**
   * Contribution ID.
   *
   * @var int
   *
   * @internal
   */
  protected $_coid = NULL;

  /**
   * Contribution Recur ID.
   *
   * @var int
   *
   * @deprecated
   */
  protected $_crid = NULL;

  /**
   * The recurring contribution id, used when editing the recurring contribution.
   *
   * For historical reasons this duplicates _crid & since the name is more meaningful
   * we should probably deprecate $_crid.
   *
   * @var int
   *
   * @internal
   */
  protected int $contributionRecurID;

  /**
   * Membership ID.
   *
   * @var int|null
   *
   * @internal
   */
  protected ?int $_mid;

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
   * Details of the subscription (recurring contribution) to be altered.
   *
   * @var \CRM_Core_DAO
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
    // These 2 gets shouldn't be needed long term but for now they ensure the
    // properties are set - but we should deprecate the properties in favour of the
    // standardised get methods.
    $this->getMembershipID();
    $this->getContributionID();
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
    if ($this->getContributionRecurID()) {
      $this->_paymentProcessor = CRM_Contribute_BAO_ContributionRecur::getPaymentProcessor($this->getContributionRecurID());
      if (!$this->_paymentProcessor) {
        CRM_Core_Error::statusBounce(ts('There is no valid processor for this subscription so it cannot be updated'));
      }
      $this->_paymentProcessorObj = $this->_paymentProcessor['object'];
    }
  }

  /**
   * Set the subscription details on the form.
   */
  protected function setSubscriptionDetails() {
    $this->subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->getContributionRecurID());
  }

  /**
   * Get details for the recurring contribution being altered.
   *
   * @return \CRM_Core_DAO
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
    return $sub->contact_id ? (int) $sub->contact_id : FALSE;
  }

  /**
   * Get the recurring contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int
   */
  public function getContributionRecurID(): int {
    if (!isset($this->contributionRecurID)) {
      $id = CRM_Utils_Request::retrieve('crid', 'Integer', $this, FALSE);
      if (!$id && $this->getContributionID()) {
        $id = $this->getContributionValue('contribution_recur_id');
      }
      if (!$id) {
        $id = $this->getMembershipValue('contribution_recur_id');
      }
      $this->contributionRecurID = $this->_crid = $id;
    }
    return (int) $this->contributionRecurID;
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    $this->_coid = CRM_Utils_Request::retrieve('coid', 'Integer', $this);
    return $this->_coid ? (int) $this->_coid : NULL;
  }

  /**
   * Get the membership ID.
   *
   * @return int
   * @throws \CRM_Core_Exception
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   */
  protected function getMembershipID(): ?int {
    if (!CRM_Core_Component::isEnabled('CiviMember')) {
      return NULL;
    }
    $membershipID = CRM_Utils_Request::retrieve('mid', 'Integer', $this);
    if (!isset($this->contributionRecurID)) {
      // This is being called before the contribution recur ID is set - return quickly to avoid a loop.
      return $membershipID ? (int) $membershipID : NULL;
    }
    if (!isset($this->_mid)) {
      $this->_mid = NULL;
      if (!$this->isDefined('Membership')) {
        $membership = Membership::get(FALSE)
          ->addWhere('contribution_recur_id', '=', $this->getContributionRecurID())
          ->addSelect('*', 'membership_type_id.name')
          ->execute()->first();
        if ($membershipID && (!$membership || ($membership['id'] !== $membershipID))) {
          // this feels unreachable
          throw new CRM_Core_Exception(ts('invalid membership ID'));
        }
        if ($membership) {
          $this->define('Membership', 'Membership', $membership);
          $this->_mid = $membership['id'];
        }
      }
    }
    return $this->_mid;
  }

  /**
   * Is this being used by a front end user to update their own recurring.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function isSelfService() {
    if ($this->selfService !== NULL) {
      return $this->selfService;
    }
    $this->selfService = FALSE;
    if (!CRM_Core_Permission::check('edit contributions')) {
      if ($this->getSubscriptionContactID() !== $this->getContactIDIfAccessingOwnRecord()) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to cancel this recurring contribution.'));
      }
      $this->selfService = TRUE;
    }
    return $this->selfService;
  }

}
