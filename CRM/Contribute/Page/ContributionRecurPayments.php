<?php

/**
 * Shows list of contributions done as payments within a recurring contribution.
 */
class CRM_Contribute_Page_ContributionRecurPayments extends CRM_Core_Page {

  /**
   * Contribution ID
   *
   * @var int
   */
  private $id = NULL;

  /**
   * Contact ID
   *
   * @var int
   */
  private $contactId = NULL;

  /**
   * Builds list of contributions for a given recurring contribution.
   *
   * @return null
   */
  public function run() {
    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);

    $this->loadRelatedContributions();

    return parent::run();
  }

  /**
   * Loads contributions associated to the current recurring contribution being
   * viewed.
   */
  private function loadRelatedContributions() {
    $relatedContributions = array();

    $relatedContributionsResult = civicrm_api3('Contribution', 'get', array(
      'sequential' => 1,
      'contribution_recur_id' => $this->id,
      'contact_id' => $this->contactId,
      'options' => array('limit' => 0),
      'contribution_test' => '',
    ));

    foreach ($relatedContributionsResult['values'] as $contribution) {
      $this->insertAmountExpandingPaymentsControl($contribution);
      $this->fixDateFormats($contribution);
      $this->insertStatusLabels($contribution);
      $this->insertContributionActions($contribution);

      if ($contribution['is_test']) {
        $contribution['financial_type'] = CRM_Core_TestEntity::appendTestText($contribution['financial_type']);
      }
      $relatedContributions[] = $contribution;
    }

    if (count($relatedContributions) > 0) {
      $this->assign('contributionsCount', count($relatedContributions));
      $this->assign('relatedContributions', json_encode($relatedContributions));
    }
  }

  /**
   * Inserts a string into the array with the html used to show the expanding
   * payments control, which loads when user clicks on the amount.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data and where the
   *   control will be inserted into
   */
  private function insertAmountExpandingPaymentsControl(&$contribution) {
    $amount = CRM_Utils_Money::format($contribution['total_amount'], $contribution['currency']);

    $expandPaymentsUrl = CRM_Utils_System::url('civicrm/payment',
      array(
        'view' => 'transaction',
        'component' => 'contribution',
        'action' => 'browse',
        'cid' => $this->contactId,
        'id' => $contribution['contribution_id'],
        'selector' => 1,
      ),
      FALSE, NULL, TRUE
    );

    $contribution['amount_control'] = '
      <a class="nowrap bold crm-expand-row" title="view payments" href="' . $expandPaymentsUrl . '">
        &nbsp; ' . $amount . '
      </a>
    ';
  }

  /**
   * Fixes date fields present in the given contribution.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data
   */
  private function fixDateFormats(&$contribution) {
    $config = CRM_Core_Config::singleton();

    $contribution['formatted_receive_date'] = CRM_Utils_Date::customFormat($contribution['receive_date'], $config->dateformatDatetime);
    $contribution['formatted_thankyou_date'] = CRM_Utils_Date::customFormat($contribution['thankyou_date'], $config->dateformatDatetime);
  }

  /**
   * Inserts a contribution_status_label key into the array, with the value
   * showing the current status plus observations on the current status.
   *
   * @param array $contribution
   *   Reference to the array holding the contribution's data and where the new
   *   position will be inserted
   */
  private function insertStatusLabels(&$contribution) {
    $contribution['contribution_status_label'] = $contribution['contribution_status'];

    if ($contribution['is_pay_later'] && CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $contribution['contribution_status_label'] .= ' (' . ts('Pay Later') . ')';
    }
    elseif (CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $contribution['contribution_status_label'] .= ' (' . ts('Incomplete Transaction') . ')';
    }
  }

  /**
   * Inserts into the given array a string with the 'action' key, holding the
   * html to be used to show available actions for the contribution.
   *
   * @param $contribution
   *   Reference to the array holding the contribution's data. It is also the
   *   array where the new 'action' key will be inserted.
   */
  private function insertContributionActions(&$contribution) {
    $contribution['action'] = CRM_Core_Action::formLink(
      $this->buildContributionLinks($contribution),
      $this->getContributionPermissionsMask(),
      array(
        'id' => $contribution['contribution_id'],
        'cid' => $contribution['contact_id'],
        'cxt' => 'contribution',
      ),
      ts('more'),
      FALSE,
      'contribution.selector.row',
      'Contribution',
      $contribution['contribution_id']
    );
  }

  /**
   * Builds list of links for authorized actions that can be done on given
   * contribution.
   *
   * @param array $contribution
   *
   * @return array
   */
  private function buildContributionLinks($contribution) {
    $links = CRM_Contribute_Selector_Search::links($contribution['contribution_id'],
      CRM_Utils_Request::retrieve('action', 'String'),
      NULL,
      NULL
    );

    $isPayLater = FALSE;
    if ($contribution['is_pay_later'] && CRM_Utils_Array::value('contribution_status', $contribution) == 'Pending') {
      $isPayLater = TRUE;

      $links[CRM_Core_Action::ADD] = array(
        'name' => ts('Pay with Credit Card'),
        'url' => 'civicrm/contact/view/contribution',
        'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%&mode=live',
        'title' => ts('Pay with Credit Card'),
      );
    }

    if (in_array($contribution['contribution_status'], array('Partially paid', 'Pending refund')) || $isPayLater) {
      $buttonName = ts('Record Payment');

      if ($contribution['contribution_status'] == 'Pending refund') {
        $buttonName = ts('Record Refund');
      }
      elseif (CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
        $links[CRM_Core_Action::BASIC] = array(
          'name' => ts('Submit Credit Card payment'),
          'url' => 'civicrm/payment/add',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution&mode=live',
          'title' => ts('Submit Credit Card payment'),
        );
      }
      $links[CRM_Core_Action::ADD] = array(
        'name' => $buttonName,
        'url' => 'civicrm/payment',
        'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=add&component=contribution',
        'title' => $buttonName,
      );
    }

    return $links;
  }

  /**
   * Builds a mask with allowed contribution related permissions.
   *
   * @return int
   */
  private function getContributionPermissionsMask() {
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit contributions')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviContribute')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    return CRM_Core_Action::mask($permissions);
  }

}
