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
use Civi\Api4\ContributionRecur;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * called when action is browse.
   *
   * @throws \CRM_Core_Exception
   */
  public function listContribution(): void {
    $contributions = $this->getContributions();

    foreach ($contributions as &$row) {
      // This is required for tpl logic. We should move away from hard-code this to adding an array of actions to the row
      // which the tpl can iterate through - this should allow us to cope with competing attempts to add new buttons
      // and allow extensions to assign new ones through the pageRun hook
      // We could check for balance_amount > 0 here? It feels more correct but this seems to be working.
      if (in_array($row['contribution_status_id:name'], ['Pending', 'Partially paid'], TRUE)
        && Civi::settings()->get('default_invoice_page')
      ) {
        $row['buttons']['pay'] = [
          'class' => 'button',
          'label' => ts('Pay Now'),
          'url' => CRM_Utils_System::url('civicrm/contribute/transact', [
            'reset' => 1,
            'id' => Civi::settings()->get('default_invoice_page'),
            'ccid' => $row['id'],
            'cs' => $this->getUserChecksum(),
            'cid' => $row['contact_id'],
          ]),
        ];
      }
    }
    unset($row);

    $this->assign('contribute_rows', $contributions);
    $this->assign('contributionSummary', ['total_amount' => civicrm_api3('Contribution', 'getcount', ['contact_id' => $this->_contactId])]);

    //add honor block
    $softCreditContributions = $this->getContributions(TRUE);
    $this->assign('soft_credit_contributions', $softCreditContributions);

    $recurringContributions = (array) ContributionRecur::get(FALSE)
      ->addWhere('contact_id', '=', $this->_contactId)
      ->addWhere('is_test', '=', 0)
      ->setSelect([
        '*',
        'contribution_status_id:label',
      ])->execute();

    $recurRow = [];
    $recurIDs = [];
    foreach ($recurringContributions as $recur) {
      if (empty($recur['payment_processor_id'])) {
        // it's not clear why we continue here as any without a processor id would likely
        // be imported from another system & still seem valid.
        continue;
      }

      // Cast to something Smarty-friendly.
      $recur['recur_status'] = $recur['contribution_status_id:label'];
      $recurRow[$recur['id']] = $recur;

      $action = array_sum(array_keys(CRM_Contribute_Page_Tab::dashboardRecurLinks((int) $recur['id'], (int) $recur['contact_id'])));

      $details = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recur['id']);
      $hideUpdate = $details->membership_id & $details->auto_renew;

      if ($hideUpdate) {
        $action -= CRM_Core_Action::UPDATE;
      }

      $recurRow[$recur['id']]['action'] = CRM_Core_Action::formLink(CRM_Contribute_Page_Tab::dashboardRecurLinks((int) $recur['id'], (int) $this->_contactId),
        $action, [
          'cid' => $this->_contactId,
          'crid' => $recur['id'],
          'cxt' => 'contribution',
        ],
        ts('more'),
        FALSE,
        'contribution.dashboard.recurring',
        'Contribution',
        $recur['id']
      );

      $recurIDs[] = $recur['id'];
    }
    if (is_array($recurIDs) && !empty($recurIDs)) {
      $getCount = CRM_Contribute_BAO_ContributionRecur::getCount($recurIDs);
      foreach ($getCount as $key => $val) {
        $recurRow[$key]['completed'] = $val;
        $recurRow[$key]['link'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&recur=$key"
        );
      }
    }

    $this->assign('recurRows', $recurRow);

  }

  /**
   * Should invoice links be displayed on the template.
   *
   * @todo This should be moved to a hook-like structure on the invoicing class
   * (currently CRM_Utils_Invoicing) with a view to possible removal from core.
   */
  public function isIncludeInvoiceLinks() {
    if (!\Civi::settings()->get('invoicing')) {
      return FALSE;
    }
    $dashboardOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'user_dashboard_options'
    );
    return $dashboardOptions['Invoices / Credit Notes'];
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $this->assign('isIncludeInvoiceLinks', $this->isIncludeInvoiceLinks());
    $this->assign('canViewMyInvoicesOrAccessCiviContribute', CRM_Core_Permission::check([['view my invoices', 'access CiviContribute']]));
    parent::preProcess();
    $this->listContribution();
  }

  /**
   * Get the contact's contributions.
   *
   * @param bool $isSoftCredit
   *   Return contributions for which the contact is the soft credit contact instead.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getContributions(bool $isSoftCredit = FALSE): array {
    $apiQuery = Contribution::get(FALSE)
      ->addOrderBy('receive_date', 'DESC')
      ->setLimit(12)
      ->setSelect([
        'total_amount',
        'contribution_recur_id',
        'receive_date',
        'receipt_date',
        'cancel_date',
        'amount_level',
        'contact_id',
        'contact_id.display_name',
        'contribution_status_id:name',
        'contribution_status_id:label',
        'financial_type_id:label',
        'currency',
        'amount_level',
        'contact_id,',
        'source',
        'balance_amount',
        'id',
      ]);

    if ($isSoftCredit) {
      $apiQuery->addJoin('ContributionSoft AS contribution_soft', 'INNER');
      $apiQuery->addWhere('contribution_soft.contact_id', '=', $this->_contactId);
      $apiQuery->addSelect('contribution_soft.soft_credit_type_id:label');
    }
    else {
      $apiQuery->addWhere('contact_id', '=', $this->_contactId);
    }
    $contributions = (array) $apiQuery->execute();
    foreach ($contributions as $index => $contribution) {
      // QuickForm can't cope with the colons & dots ... cast to a legacy or simplified key.
      $contributions[$index]['financial_type'] = $contribution['financial_type_id:label'];
      $contributions[$index]['contribution_status'] = $contribution['contribution_status_id:label'];
      $contributions[$index]['contribution_status_name'] = $contribution['contribution_status_id:name'];
      $contributions[$index]['display_name'] = $contribution['contact_id.display_name'];
      $contributions[$index]['soft_credit_type'] = $contribution['contribution_soft.soft_credit_type_id:label'] ?? NULL;
      // Add in the api-v3 style naming just in case any extensions are still looking for it.
      $contributions[$index]['contribution_id'] = $contribution['id'];
    }
    return $contributions;
  }

}
