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
 * This class generates form components for Pledge
 */
class CRM_Pledge_Form_PledgeView extends CRM_Core_Form {
  use CRM_Pledge_Form_PledgeFormTrait;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {

    $id = $this->getPledgeID();
    $contactID = $this->getPledgeValue('contact_id');
    $params = ['id' => $this->getPledgeID()];
    // handle custom data.
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Pledge', NULL, $params['id'], NULL, [], NULL,
      TRUE, NULL, FALSE, CRM_Core_Permission::VIEW);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $params['id']);

    $displayName = $this->getPledgeValue('contact_id.display_name');

    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($contactID)) {
      $displayName .= ' (' . ts('default organization') . ')';
    }
    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    $this->setTitle(ts('View Pledge by') . ' ' . $displayName);
    $this->assign('displayName', $displayName);

    $title = $displayName .
      ' - (' . ts('Pledged') . ' ' . CRM_Utils_Money::format($this->getPledgeValue('pledge_amount')) .
      ' - ' . $this->getPledgeValue('financial_type_id:label') . ')';

    $url = CRM_Utils_System::url('civicrm/contact/view/pledge',
      "action=view&reset=1&id={$id}&cid={$contactID}&context=home"
    );

    $recentOther = [];
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=update&reset=1&id={$id}&cid={$contactID}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=delete&reset=1&id={$id}&cid={$contactID}&context=home"
      );
    }
    // add Pledge to Recent Items
    CRM_Utils_Recent::add($title,
      $url,
      $this->getPledgeID(),
      'Pledge',
      $this->getPledgeValue('contact_id'),
      NULL,
      $recentOther
    );

    $this->assign('campaign', $this->getPledgeValue('campaign_id:label'));
    $currentInstallment = $this->getPledgeValue('amount') / $this->getPledgeValue('installments');
    $this->assign('currentInstallment', $currentInstallment);
    $this->assign('originalPledgeAmount', $currentInstallment === $this->getPledgeValue('original_installment_amount') ? NULL : $this->getPledgeValue('installments') * $this->getPledgeValue('original_installment_amount'));
    $this->assign('start_date', $this->getPledgeValue('start_date'));
    $this->assign('create_date', $this->getPledgeValue('create_date'));
    $this->assign('end_date', $this->getPledgeValue('end_date'));
    $this->assign('cancel_date', $this->getPledgeValue('cancel_date'));
    $this->assign('is_test', $this->getPledgeValue('is_test'));
    $this->assign('acknowledge_date', $this->getPledgeValue('acknowledge_date'));
    $this->assign('pledge_status', $this->getPledgeValue('status_id:label'));
    $this->assign('pledge_status_name', $this->getPledgeValue('status_id:name'));
    $this->assign('amount', $this->getPledgeValue('amount'));
    $this->assign('currency', $this->getPledgeValue('currency'));
    $this->assign('initial_reminder_day', $this->getPledgeValue('initial_reminder_day'));
    $this->assign('additional_reminder_day', $this->getPledgeValue('additional_reminder_day'));
    $this->assign('max_reminders', $this->getPledgeValue('max_reminders'));
    $this->assign('installments', $this->getPledgeValue('installments'));
    $this->assign('original_installment_amount', $this->getPledgeValue('original_installment_amount'));
    $this->assign('frequency_interval', $this->getPledgeValue('frequency_interval'));
    $this->assign('frequency_day', $this->getPledgeValue('frequency_day'));
    $this->assign('contribution_page', $this->getPledgeValue('contribution_page_id.title'));
    $this->assign('contact_id', $this->getPledgeValue('contact_id'));
    $this->assign('financial_type', $this->getPledgeValue('financial_type_id:label'));
    $this->assign('frequencyUnit', ts('%1(s)', [1 => $this->getPledgeValue('frequency_unit')]));
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm(): void {
    $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
    ]);
  }

  /**
   * Get id of Pledge being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   */
  public function getPledgeID(): int {
    return (int) $this->get('id');
  }

}
