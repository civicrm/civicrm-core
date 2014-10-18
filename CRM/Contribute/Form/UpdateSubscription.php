<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components generic to recurring contributions
 *
 * It delegates the work to lower level subclasses and integrates the changes
 * back in. It also uses a lot of functionality with the CRM API's, so any change
 * made here could potentially affect the API etc. Be careful, be aware, use unit tests.
 *
 */
class CRM_Contribute_Form_UpdateSubscription extends CRM_Core_Form {

  /**
   * The recurring contribution id, used when editing the recurring contribution
   *
   * @var int
   */
  protected $_crid = NULL;

  protected $_coid = NULL;

  protected $_subscriptionDetails = NULL;

  protected $_selfService = FALSE;

  public $_paymentProcessor = NULL;

  public $_paymentProcessorObj = NULL;

  /**
   * the id of the contact associated with this recurring contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  function preProcess() {

    $this->_crid = CRM_Utils_Request::retrieve('crid', 'Integer', $this, FALSE);
    if ($this->_crid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_crid, 'recur', 'info');
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_crid, 'recur', 'obj');
      $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_crid);
    }

    $this->_coid = CRM_Utils_Request::retrieve('coid', 'Integer', $this, FALSE);
    if ($this->_coid) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'info');
      $this->_paymentProcessorObj = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($this->_coid, 'contribute', 'obj');
      $this->_subscriptionDetails = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($this->_coid, 'contribution');
    }

    if ((!$this->_crid && !$this->_coid) ||
      ($this->_subscriptionDetails == CRM_Core_DAO::$_nullObject)
    ) {
      CRM_Core_Error::fatal('Required information missing.');
    }

    if ($this->_subscriptionDetails->membership_id && $this->_subscriptionDetails->auto_renew) {
      CRM_Core_Error::fatal(ts('You cannot update the subscription.'));
    }

    if (!CRM_Core_Permission::check('edit contributions')) {
      $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this, FALSE);
      if (!CRM_Contact_BAO_Contact_Utils::validChecksum($this->_subscriptionDetails->contact_id, $userChecksum)) {
        CRM_Core_Error::fatal(ts('You do not have permission to update subscription.'));
      }
      $this->_selfService = TRUE;
    }
    $this->assign('self_service', $this->_selfService);

    if (!$this->_paymentProcessorObj->isSupported('changeSubscriptionAmount')) {
      $userAlert = "<span class='font-red'>" . ts('Updates made using this form will change the recurring contribution information stored in your CiviCRM database, but will NOT be sent to the payment processor. You must enter the same changes using the payment processor web site.',
        array( 1 => $this->_paymentProcessorObj->_processorName ) ) . '</span>';
      CRM_Core_Session::setStatus($userAlert, ts('Warning'), 'alert');
    }

    $this->assign('isChangeSupported', $this->_paymentProcessorObj->isSupported('changeSubscriptionAmount'));
    $this->assign('paymentProcessor', $this->_paymentProcessor);
    $this->assign('frequency_unit', $this->_subscriptionDetails->frequency_unit);
    $this->assign('frequency_interval', $this->_subscriptionDetails->frequency_interval);

    if ($this->_subscriptionDetails->contact_id) {
      list($this->_donorDisplayName, $this->_donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($this->_subscriptionDetails->contact_id);
    }

    CRM_Utils_System::setTitle(ts('Update Recurring Contribution'));

    // handle context redirection
    CRM_Contribute_BAO_ContributionRecur::setSubscriptionContext();
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {

    $this->_defaults = array();
    $this->_defaults['amount'] = $this->_subscriptionDetails->amount;
    $this->_defaults['installments'] = $this->_subscriptionDetails->installments;
    $this->_defaults['is_notify'] = 1;

    return $this->_defaults;
  }

  /**
   * Function to actually build the components of the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // define the fields
    $this->addMoney('amount', ts('Recurring Contribution Amount'), TRUE,
      array(
        'size' => 20), TRUE,
      'currency', NULL, TRUE
    );

    $this->add('text', 'installments', ts('Number of Installments'), array('size' => 20), TRUE);

    if ($this->_donorEmail) {
      $this->add('checkbox', 'is_notify', ts('Notify Contributor?'));
    }

    $type = 'next';
    if ( $this->_selfService ) {
      $type = 'submit';
    }

    // define the buttons
    $this->addButtons(array(
        array(
          'type' => $type,
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * This function is called after the user submits the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    if ($this->_selfService && $this->_donorEmail) {
      // for self service force notify
      $params['is_notify'] = 1;
    }

    // if this is an update of an existing recurring contribution, pass the ID
    $params['id'] = $this->_subscriptionDetails->recur_id;
    $message = '';

    $params['subscriptionId'] = $this->_subscriptionDetails->subscription_id;
    $updateSubscription = true;
    if ($this->_paymentProcessorObj->isSupported('changeSubscriptionAmount')) {
        $updateSubscription = $this->_paymentProcessorObj->changeSubscriptionAmount($message, $params);
    }
    if (is_a($updateSubscription, 'CRM_Core_Error')) {
        CRM_Core_Error::displaySessionError($updateSubscription);
        $status = ts('Could not update the Recurring contribution details');
        $msgTitle = ts('Update Error');
        $msgType = 'error';
    }
    elseif ($updateSubscription) {
        // save the changes
        $result = CRM_Contribute_BAO_ContributionRecur::add($params);
        $status = ts('Recurring contribution has been updated to: %1, every %2 %3(s) for %4 installments.',
                     array(1 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency),
                           2 => $this->_subscriptionDetails->frequency_interval,
                           3 => $this->_subscriptionDetails->frequency_unit,
                           4 => $params['installments']
                           )
                     );

    $msgTitle = ts('Update Success');
    $msgType = 'success';

        $contactID = $this->_subscriptionDetails->contact_id;

        if ($this->_subscriptionDetails->amount != $params['amount']) {
            $message .= "<br /> " . ts("Recurring contribution amount has been updated from %1 to %2 for this subscription.",
              array(
                1 => CRM_Utils_Money::format($this->_subscriptionDetails->amount, $this->_subscriptionDetails->currency),
                2 => CRM_Utils_Money::format($params['amount'], $this->_subscriptionDetails->currency)
              )) . ' ';
        }

        if ($this->_subscriptionDetails->installments != $params['installments']) {
            $message .= "<br /> " . ts("Recurring contribution installments have been updated from %1 to %2 for this subscription.", array(1 => $this->_subscriptionDetails->installments, 2 => $params['installments'])) . ' ';
        }

        $activityParams = array(
            'source_contact_id' => $contactID,
            'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
            'Update Recurring Contribution',
            'name'
          ),
          'subject' => ts('Recurring Contribution Updated'),
          'details' => $message,
          'activity_date_time' => date('YmdHis'),
          'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
            'Completed',
            'name'
          ),
        );
        $session = CRM_Core_Session::singleton();
        $cid = $session->get('userID');

        if ($cid) {
          $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
          $activityParams['source_contact_id'] = $cid;
        }
        CRM_Activity_BAO_Activity::create($activityParams);

        if (!empty($params['is_notify'])) {
          // send notification
          if ($this->_subscriptionDetails->contribution_page_id) {
            CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id',
              $this->_subscriptionDetails->contribution_page_id, $value, array(
                'title',
                'receipt_from_name',
                'receipt_from_email',
              )
            );
            $receiptFrom = '"' . CRM_Utils_Array::value('receipt_from_name', $value[$this->_subscriptionDetails->contribution_page_id]) . '" <' . $value[$this->_subscriptionDetails->contribution_page_id]['receipt_from_email'] . '>';
          }
          else {
            $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
            $receiptFrom = "$domainValues[0] <$domainValues[1]>";
          }

          list($donorDisplayName, $donorEmail) = CRM_Contact_BAO_Contact::getContactDetails($contactID);

          $tplParams = array(
            'recur_frequency_interval' => $this->_subscriptionDetails->frequency_interval,
            'recur_frequency_unit' => $this->_subscriptionDetails->frequency_unit,
            'amount' => CRM_Utils_Money::format($params['amount']),
            'installments' => $params['installments'],
          );

          $tplParams['contact'] = array('display_name' => $donorDisplayName);
          $tplParams['receipt_from_email'] = $receiptFrom;

          $sendTemplateParams = array(
            'groupName' => 'msg_tpl_workflow_contribution',
            'valueName' => 'contribution_recurring_edit',
            'contactId' => $contactID,
            'tplParams' => $tplParams,
            'isTest' => $this->_subscriptionDetails->is_test,
            'PDFFilename' => 'receipt.pdf',
            'from' => $receiptFrom,
            'toName' => $donorDisplayName,
            'toEmail' => $donorEmail,
          );
          list($sent) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        }
      }

    $session = CRM_Core_Session::singleton();
    $userID  = $session->get('userID');
    if ( $userID && $status) {
      CRM_Core_Session::setStatus($status, $msgTitle, $msgType);
    } else if (!$userID) {
      if ($status)
        CRM_Utils_System::setUFMessage($status);
      // keep result as 1, since we not displaying anything on the redirected page anyway
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/subscriptionstatus',
                                                              "reset=1&task=update&result=1"));
    }
  }
}

