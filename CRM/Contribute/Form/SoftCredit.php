<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * $Id$
 *
 */

/**
 * This class build form elements for select existing or create new soft block
 */
class CRM_Contribute_Form_SoftCredit {

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function preProcess(&$form) {
    $contriDAO = new CRM_Contribute_DAO_Contribution();
    $contriDAO->id = $form->_id;
    $contriDAO->find(TRUE);
    if ($contriDAO->contribution_page_id) {
      $ufJoinParams = array(
        'module' => 'soft_credit',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $contriDAO->contribution_page_id,
      );
      $profileId = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      //check if any honree profile is enabled if yes then assign its profile type to $_honoreeProfileType
      // which will be used to constraint soft-credit contact type in formRule, CRM-13981
      if ($profileId[0]) {
        $form->_honoreeProfileType = CRM_Core_BAO_UFGroup::getContactType($profileId[0]);
      }
    }
  }


  /**
   * Function used to build form element for soft credit block.
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   */
  public static function buildQuickForm(&$form) {
    if (!empty($form->_honor_block_is_active)) {
      $ufJoinDAO = new CRM_Core_DAO_UFJoin();
      $ufJoinDAO->module = 'soft_credit';
      $ufJoinDAO->entity_id = $form->_id;
      if ($ufJoinDAO->find(TRUE)) {
        $jsonData = CRM_Contribute_BAO_ContributionPage::formatMultilingualHonorParams($ufJoinDAO->module_data, TRUE);
        if ($jsonData) {
          foreach (array('honor_block_title', 'honor_block_text') as $name) {
            $form->assign($name, $jsonData[$name]);
          }

          $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

          // radio button for Honor Type
          foreach ($jsonData['soft_credit_types'] as $value) {
            $honorTypes[$value] = $form->createElement('radio', NULL, NULL, $softCreditTypes[$value], $value);
          }
          $form->addGroup($honorTypes, 'soft_credit_type_id', NULL)->setAttribute('allowClear', TRUE);
        }
      }
      return $form;
    }

    // by default generate 10 blocks
    $item_count = 11;

    $showSoftCreditRow = 2;
    if ($form->getAction() & CRM_Core_Action::UPDATE) {
      $form->_softCreditInfo = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($form->_id, TRUE);
    }
    elseif (!empty($form->_pledgeID)) {
      //Check and select most recent completed contrubtion and use it to retrieve
      //soft-credit information to use as default for current pledge payment, CRM-13981
      $pledgePayments = CRM_Pledge_BAO_PledgePayment::getPledgePayments($form->_pledgeID);
      foreach ($pledgePayments as $id => $record) {
        if ($record['contribution_id']) {
          $softCredits = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($record['contribution_id'], TRUE);
          if ($record['status'] == 'Completed' && count($softCredits) > 0) {
            $form->_softCreditInfo = $softCredits;
          }
        }
      }
    }

    if (property_exists($form, "_softCreditInfo")) {
      if (!empty($form->_softCreditInfo['soft_credit'])) {
        $showSoftCreditRow = count($form->_softCreditInfo['soft_credit']);
        $showSoftCreditRow++;
      }
    }

    for ($rowNumber = 1; $rowNumber <= $item_count; $rowNumber++) {
      $form->addEntityRef("soft_credit_contact_id[{$rowNumber}]", ts('Contact'), array('create' => TRUE));

      $form->addMoney("soft_credit_amount[{$rowNumber}]", ts('Amount'), FALSE, NULL, FALSE);

      $form->addSelect("soft_credit_type[{$rowNumber}]", array(
          'entity' => 'contribution_soft',
          'field' => 'soft_credit_type_id',
          'label' => ts('Type'),
        ));
      if (!empty($form->_softCreditInfo['soft_credit'][$rowNumber]['soft_credit_id'])) {
        $form->add('hidden', "soft_credit_id[{$rowNumber}]",
          $form->_softCreditInfo['soft_credit'][$rowNumber]['soft_credit_id']);
      }
    }

    // CRM-7368 allow user to set or edit PCP link for contributions
    $siteHasPCPs = CRM_Contribute_PseudoConstant::pcPage();
    if (!CRM_Utils_Array::crmIsEmptyArray($siteHasPCPs)) {
      $form->assign('siteHasPCPs', 1);
      // Fixme: Not a true entityRef field. Relies on PCP.js.tpl
      $form->add('text', 'pcp_made_through_id', ts('Credit to a Personal Campaign Page'), array('class' => 'twenty', 'placeholder' => ts('- select -')));
      // stores the label
      $form->add('hidden', 'pcp_made_through');
      $form->addElement('checkbox', 'pcp_display_in_roll', ts('Display in Honor Roll?'), NULL);
      $form->addElement('text', 'pcp_roll_nickname', ts('Name (for Honor Roll)'));
      $form->addElement('textarea', 'pcp_personal_note', ts('Personal Note (for Honor Roll)'));
    }
    $form->assign('showSoftCreditRow', $showSoftCreditRow);
    $form->assign('rowCount', $item_count);
    $form->addElement('hidden', 'sct_default_id',
      CRM_Core_OptionGroup::getDefaultValue("soft_credit_type"),
      array('id' => 'sct_default_id')
    );
  }

  /**
   * Function used to set defaults for soft credit block.
   *
   * @param $defaults
   * @param $form
   */
  public static function setDefaultValues(&$defaults, &$form) {
    //Used to hide/unhide PCP and/or Soft-credit Panes
    $noPCP = $noSoftCredit = TRUE;
    if (!empty($form->_softCreditInfo['soft_credit'])) {
      $noSoftCredit = FALSE;
      foreach ($form->_softCreditInfo['soft_credit'] as $key => $value) {
        $defaults["soft_credit_amount[$key]"] = CRM_Utils_Money::format($value['amount'], NULL, '%a');
        $defaults["soft_credit_contact_id[$key]"] = $value['contact_id'];
        $defaults["soft_credit_type[$key]"] = $value['soft_credit_type'];
      }
    }
    if (!empty($form->_softCreditInfo['pcp_id'])) {
      $noPCP = FALSE;
      $pcpInfo = $form->_softCreditInfo;
      $pcpId = CRM_Utils_Array::value('pcp_id', $pcpInfo);
      $pcpTitle = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $pcpId, 'title');
      $contributionPageTitle = CRM_PCP_BAO_PCP::getPcpPageTitle($pcpId, 'contribute');
      $defaults['pcp_made_through'] = CRM_Utils_Array::value('sort_name', $pcpInfo) . " :: " . $pcpTitle . " :: " . $contributionPageTitle;
      $defaults['pcp_made_through_id'] = CRM_Utils_Array::value('pcp_id', $pcpInfo);
      $defaults['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $pcpInfo);
      $defaults['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $pcpInfo);
      $defaults['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $pcpInfo);
    }

    $form->assign('noSoftCredit', $noSoftCredit);
    $form->assign('noPCP', $noPCP);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @param $errors
   * @param $self
   *
   * @return array
   *   Array of errors
   */
  public static function formRule($fields, $errors, $self) {
    $errors = array();

    // if honor roll fields are populated but no PCP is selected
    if (empty($fields['pcp_made_through_id'])) {
      if (!empty($fields['pcp_display_in_roll']) || !empty($fields['pcp_roll_nickname']) ||
        CRM_Utils_Array::value('pcp_personal_note', $fields)
      ) {
        $errors['pcp_made_through_id'] = ts('Please select a Personal Campaign Page, OR uncheck Display in Honor Roll and clear both the Honor Roll Name and the Personal Note field.');
      }
    }

    if (!empty($fields['soft_credit_amount'])) {
      $repeat = array_count_values($fields['soft_credit_contact_id']);
      foreach ($fields['soft_credit_amount'] as $key => $val) {
        if (!empty($fields['soft_credit_contact_id'][$key])) {
          if ($repeat[$fields['soft_credit_contact_id'][$key]] > 1) {
            $errors["soft_credit_contact[$key]"] = ts('You cannot enter multiple soft credits for the same contact.');
          }
          if ($self->_action == CRM_Core_Action::ADD && $fields['soft_credit_amount'][$key]
            && (CRM_Utils_Rule::cleanMoney($fields['soft_credit_amount'][$key]) > CRM_Utils_Rule::cleanMoney($fields['total_amount']))
          ) {
            $errors["soft_credit_amount[$key]"] = ts('Soft credit amount cannot be more than the total amount.');
          }
          if (empty($fields['soft_credit_amount'][$key])) {
            $errors["soft_credit_amount[$key]"] = ts('Please enter the soft credit amount.');
          }
          $contactType = CRM_Contact_BAO_Contact::getContactType($fields['soft_credit_contact_id'][$key]);
          if ($self->_honoreeProfileType && $self->_honoreeProfileType != $contactType) {
            $errors["soft_credit_contact[$key]"] = ts('Please choose a contact of type %1', array(1 => $self->_honoreeProfileType));
          }
        }
      }
    }

    return $errors;
  }

}
