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
 * This class is to build the form for Deleting Group.
 */
class CRM_Contribute_Form_ContributionPage_Delete extends CRM_Contribute_Form_ContributionPage {

  /**
   * Page title.
   *
   * @var string
   */
  protected $_title;

  /**
   * Check if there are any related contributions.
   * @var bool
   */
  protected $_relatedContributions;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    //Check if there are contributions related to Contribution Page

    parent::preProcess();

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $dao = new CRM_Contribute_DAO_Contribution();
    $dao->contribution_page_id = $this->_id;

    if ($dao->find(TRUE)) {
      $this->_relatedContributions = TRUE;
      $this->assign('relatedContributions', TRUE);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->_title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
    $this->assign('title', $this->_title);

    //if there are contributions related to Contribution Page
    //then onle cancel button is displayed
    $buttons = [];
    if (!$this->_relatedContributions) {
      $buttons[] = [
        'type' => 'next',
        'name' => ts('Delete Contribution Page'),
        'isDefault' => TRUE,
      ];
    }

    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];

    $this->addButtons($buttons);
  }

  /**
   * Process the form when submitted.
   */
  public function postProcess() {
    $transaction = new CRM_Core_Transaction();

    // first delete the join entries associated with this contribution page
    $dao = new CRM_Core_DAO_UFJoin();

    $params = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_id,
    ];
    $dao->copyValues($params);
    $dao->delete();

    //next delete the membership block fields
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $this->_id;
    $dao->delete();

    //next delete the pcp block fields
    $dao = new CRM_PCP_DAO_PCPBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $this->_id;
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

    CRM_Core_Session::setStatus(ts("The contribution page '%1' has been deleted.", [1 => $this->_title]), ts('Deleted'), 'success');
  }

}
