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
class CRM_Contribute_Form_SearchContribution extends CRM_Core_Form {

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'title');
    $attributes['style'] = 'width: 90%';

    $this->add('text', 'title', ts('Find'), $attributes);

    $financial_account = CRM_Contribute_PseudoConstant::financialType();
    foreach ($financial_account as $contributionId => $contributionName) {
      $this->addElement('checkbox', "financial_type_id[$contributionId]", 'Financial Type', $contributionName);
    }

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    if (!empty($params)) {
      $fields = ['title', 'financial_type_id', 'campaign_id'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }

}
