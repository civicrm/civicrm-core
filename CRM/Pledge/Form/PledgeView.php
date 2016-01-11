<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form components for Pledge
 */
class CRM_Pledge_Form_PledgeView extends CRM_Core_Form {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    $values = $ids = array();
    $params = array('id' => $this->get('id'));
    CRM_Pledge_BAO_Pledge::getValues($params,
      $values,
      $ids
    );

    $values['frequencyUnit'] = ts('%1(s)', array(1 => $values['frequency_unit']));

    if (isset($values["honor_contact_id"]) && $values["honor_contact_id"]) {
      $sql = "SELECT display_name FROM civicrm_contact WHERE id = " . $values["honor_contact_id"];
      $dao = new CRM_Core_DAO();
      $dao->query($sql);
      if ($dao->fetch()) {
        $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$values[honor_contact_id]");
        $values["honor_display"] = "<A href = $url>" . $dao->display_name . "</A>";
      }
      $honor = CRM_Core_PseudoConstant::get('CRM_Pledge_DAO_Pledge', 'honor_type_id');
      $values['honor_type'] = $honor[$values['honor_type_id']];
    }

    // handle custom data.
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Pledge', $this, $params['id']);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $params['id']);

    if (!empty($values['contribution_page_id'])) {
      $values['contribution_page'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $values['contribution_page_id'], 'title');
    }

    $values['financial_type'] = CRM_Utils_Array::value($values['financial_type_id'], CRM_Contribute_PseudoConstant::financialType());

    if ($values['status_id']) {
      $values['pledge_status'] = CRM_Utils_Array::value($values['status_id'], CRM_Contribute_PseudoConstant::contributionStatus());
    }

    $url = CRM_Utils_System::url('civicrm/contact/view/pledge',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=update&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=delete&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }

    $displayName = CRM_Contact_BAO_Contact::displayName($values['contact_id']);
    $this->assign('displayName', $displayName);

    $title = $displayName .
      ' - (' . ts('Pledged') . ' ' . CRM_Utils_Money::format($values['pledge_amount']) .
      ' - ' . $values['financial_type'] . ')';

    // add Pledge to Recent Items
    CRM_Utils_Recent::add($title,
      $url,
      $values['id'],
      'Pledge',
      $values['contact_id'],
      NULL,
      $recentOther
    );

    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($values['contact_id'])) {
      $displayName .= ' (' . ts('default organization') . ')';
    }
    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    CRM_Utils_System::setTitle(ts('View Pledge by') . ' ' . $displayName);

    // do check for campaigns
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $values)) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $values['campaign'] = $campaigns[$campaignId];
    }

    $this->assign($values);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }

}
