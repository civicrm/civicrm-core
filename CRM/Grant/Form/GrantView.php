<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a Grant
 *
 */
class CRM_Grant_Form_GrantView extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_id        = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $context          = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $context);

    $values = array();
    $params['id'] = $this->_id;
    CRM_Grant_BAO_Grant::retrieve($params, $values);
    $grantType = CRM_Grant_PseudoConstant::grantType();
    $grantStatus = CRM_Grant_PseudoConstant::grantStatus();
    $this->assign('grantType', $grantType[$values['grant_type_id']]);
    $this->assign('grantStatus', $grantStatus[$values['status_id']]);
    $grantTokens = array(
      'amount_total', 'amount_requested', 'amount_granted',
      'rationale', 'grant_report_received', 'application_received_date',
      'decision_date', 'money_transfer_date', 'grant_due_date',
    );

    foreach ($grantTokens as $token) {
      $this->assign($token, CRM_Utils_Array::value($token, $values));
    }

    if (isset($this->_id)) {
      $noteDAO = new CRM_Core_BAO_Note();
      $noteDAO->entity_table = 'civicrm_grant';
      $noteDAO->entity_id = $this->_id;
      if ($noteDAO->find(TRUE)) {
        $this->_noteId = $noteDAO->id;
      }
    }

    if (isset($this->_noteId)) {
      $this->assign('note', CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $this->_noteId, 'note'));
    }


    // add Grant to Recent Items
    $url = CRM_Utils_System::url('civicrm/contact/view/grant',
      "action=view&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
    );

    $title = CRM_Contact_BAO_Contact::displayName($values['contact_id']) . ' - ' . ts('Grant') . ': ' . CRM_Utils_Money::format($values['amount_total']) . ' (' . $grantType[$values['grant_type_id']] . ')';

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
        "action=update&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
        "action=delete&reset=1&id={$values['id']}&cid={$values['contact_id']}&context=home"
      );
    }
    CRM_Utils_Recent::add($title,
      $url,
      $values['id'],
      'Grant',
      $values['contact_id'],
      NULL,
      $recentOther
    );

    $attachment = CRM_Core_BAO_File::attachmentInfo('civicrm_grant', $this->_id);
    $this->assign('attachment', $attachment);

    $grantType = CRM_Core_DAO::getFieldValue("CRM_Grant_DAO_Grant", $this->_id, "grant_type_id");
    $groupTree = &CRM_Core_BAO_CustomGroup::getTree("Grant", $this, $this->_id, 0, $grantType);
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree);

    $this->assign('id', $this->_id);
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'cancel',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
      )
    );
  }
}

