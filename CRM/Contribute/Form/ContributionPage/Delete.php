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
 * This class is to build the form for Deleting Group
 */
class CRM_Contribute_Form_ContributionPage_Delete extends CRM_Contribute_Form_ContributionPage {

  /**
   * page title
   *
   * @var string
   * @protected
   */
  protected $_title;

  /**
   * Check if there are any related contributions
   *
   */
  protected $_relatedContributions;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //Check if there are contributions related to Contribution Page

    parent::preProcess();

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    $dao = new CRM_Contribute_DAO_Contribution();
    $dao->contribution_page_id = $this->_id;

    if ($dao->find(TRUE)) {
      $this->_relatedContributions = TRUE;
      $this->assign('relatedContributions', TRUE);
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
    $this->assign('title', $this->_title);

    //if there are contributions related to Contribution Page
    //then onle cancel button is displayed
    $buttons = array();
    if (!$this->_relatedContributions) {
      $buttons[] = array(
        'type' => 'next',
        'name' => ts('Delete Contribution Page'),
        'isDefault' => TRUE,
      );
    }

    $buttons[] = array(
      'type' => 'cancel',
      'name' => ts('Cancel'),
    );

    $this->addButtons($buttons);
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $transaction = new CRM_Core_Transaction();

    // first delete the join entries associated with this contribution page
    $dao = new CRM_Core_DAO_UFJoin();

    $params = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_id,
    );
    $dao->copyValues($params);
    $dao->delete();

    //next delete the membership block fields
    $dao               = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id    = $this->_id;
    $dao->delete();

    //next delete the pcp block fields
    $dao               = new CRM_PCP_DAO_PCPBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id    = $this->_id;
    $dao->delete();

    // need to delete premiums. CRM-4586
    CRM_Contribute_BAO_Premium::deletePremium($this->_id);

    // price set cleanup, CRM-5527
    CRM_Price_BAO_PriceSet::removeFrom('civicrm_contribution_page', $this->_id);

    // finally delete the contribution page
    $dao = new CRM_Contribute_DAO_ContributionPage();
    $dao->id = $this->_id;
    $dao->delete();

    $transaction->commit();

    CRM_Core_Session::setStatus(ts("The contribution page '%1' has been deleted.", array(1 => $this->_title)), ts('Deleted'), 'success');
  }
}

