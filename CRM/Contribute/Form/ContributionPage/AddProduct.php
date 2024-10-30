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
 * form to process actions fo adding product to contribution page
 */
class CRM_Contribute_Form_ContributionPage_AddProduct extends CRM_Contribute_Form_ContributionPage {

  protected $_products;

  protected $_pid;

  /**
   * Pre process the form.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_products = CRM_Contribute_PseudoConstant::products($this->_id);
    $this->_pid = CRM_Utils_Request::retrieve('pid', 'Positive',
      $this, FALSE, 0
    );

    if ($this->_pid) {
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->id = $this->_pid;
      $dao->find(TRUE);
      $temp = CRM_Contribute_PseudoConstant::products();
      $this->_products[$dao->product_id] = $temp[$dao->product_id];
    }

    //$this->_products = array_merge(array('' => '-- Select Product --') , $this->_products );
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = [];

    if ($this->_pid) {
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->id = $this->_pid;
      $dao->find(TRUE);
      $defaults['product_id'] = $dao->product_id;
      $defaults['financial_type_id'] = $dao->financial_type_id;
      $defaults['weight'] = $dao->weight;
    }
    else {
      $dao = new CRM_Contribute_DAO_Product();
      $dao->id = key($this->_products);
      $dao->find(TRUE);
      $defaults['financial_type_id'] = $dao->financial_type_id;
    }
    if (!isset($defaults['weight']) || !($defaults['weight'])) {
      $pageID = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
      );
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $pageID;
      $dao->find(TRUE);
      $premiumID = $dao->id;

      $sql = 'SELECT max( weight ) as max_weight FROM civicrm_premiums_product WHERE premiums_id = %1';
      $params = [1 => [$premiumID, 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $dao->fetch();
      $defaults['weight'] = $dao->max_weight + 1;
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $urlParams = 'civicrm/admin/contribute/premium';
    if ($this->_action & CRM_Core_Action::DELETE) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      $session->pushUserContext($url);
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',
        CRM_Core_DAO::$_nullObject, '', '', 'GET'
      )
      ) {
        $dao = new CRM_Contribute_DAO_PremiumsProduct();
        $dao->id = $this->_pid;
        $dao->delete();
        CRM_Core_Session::setStatus(ts('Selected Premium Product has been removed from this Contribution Page.'), ts('Saved'), 'success');
        CRM_Utils_System::redirect($url);
      }

      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->id = $this->_pid;
      $dao->find(TRUE);
      $productID = $dao->product_id;

      CRM_Contribute_BAO_Premium::buildPremiumPreviewBlock($this, $productID);
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Done with Preview'),
          'isDefault' => TRUE,
        ],
      ]);
      return;
    }

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
    $session->pushUserContext($url);

    $this->add('select', 'product_id', ts('Select the Product') . ' ', $this->_products, TRUE);

    $this->addElement('text', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_PremiumsProduct', 'weight'));

    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $premiumFinancialType = [];
    CRM_Core_PseudoConstant::populate(
      $premiumFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      'account_relationship = 8'
    );

    $costFinancialType = [];
    CRM_Core_PseudoConstant::populate(
      $costFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      'account_relationship = 7'
    );
    $productFinancialType = array_intersect($costFinancialType, $premiumFinancialType);
    foreach ($financialType as $key => $financialTypeName) {
      if (!in_array($key, $productFinancialType)) {
        unset($financialType[$key]);
      }
    }
    // Check permissioned financial types
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialType, CRM_Core_Action::ADD);
    if (count($financialType)) {
      $this->assign('financialType', $financialType);
    }
    $this->add(
      'select',
      'financial_type_id',
      ts('Financial Type'),
      ['' => ts('- select -')] + $financialType
    );
    $this->addRule('weight', ts('Please enter integer value for weight'), 'integer');
    $session->pushUserContext(CRM_Utils_System::url($urlParams, 'action=update&reset=1&id=' . $this->_id));

    parent::buildQuickForm();
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $urlParams = 'civicrm/admin/contribute/premium';
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      CRM_Utils_System::redirect($url);
      return;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->id = $this->_pid;
      $dao->delete();
      CRM_Core_Session::setStatus(ts('Selected Premium Product has been removed from this Contribution Page.'), ts('Saved'), 'success');
      CRM_Utils_System::redirect($url);
    }
    else {
      $url = CRM_Utils_System::url($urlParams, 'reset=1&action=update&id=' . $this->_id);
      if ($this->_pid) {
        $params['id'] = $this->_pid;
      }
      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->_id;
      $dao->find(TRUE);
      $premiumID = $dao->id;
      $params['premiums_id'] = $premiumID;

      $oldWeight = NULL;
      if ($this->_pid) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_PremiumsProduct', $this->_pid, 'weight', 'id');
      }

      // updateOtherWeights needs to filter on premiums_id
      $filter = ['premiums_id' => $params['premiums_id']];
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Contribute_DAO_PremiumsProduct', $oldWeight, $params['weight'], $filter);

      $dao = new CRM_Contribute_DAO_PremiumsProduct();
      $dao->copyValues($params);
      $dao->save();
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Add Premium to Contribution Page');
  }

}
