<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class for contribute form task actions.
 * FIXME: This needs refactoring to properly inherit from CRM_Core_Form_Task and share more functions.
 */
class CRM_Contribute_Form_Task extends CRM_Core_Form_Task {

  /**
   * The array that holds all the contribution ids.
   *
   * @var array
   */
  protected $_contributionIds;

  /**
   * The array that holds all the mapping contribution and contact ids.
   *
   * @var array
   */
  protected $_contributionContactIds = [];

  /**
   * The flag to tell if there are soft credits included.
   *
   * @var bool
   */
  public $_includesSoftCredits = FALSE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function preProcessCommon(&$form) {
    $form->_contributionIds = [];

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = CRM_Utils_Array::value('task', $values);
    $contributeTasks = CRM_Contribute_Task::tasks();
    $form->assign('taskName', CRM_Utils_Array::value($form->_task, $contributeTasks));

    $ids = [];
    if (isset($values['radio_ts']) && $values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      $queryParams = $form->get('queryParams');
      $isTest = FALSE;
      if (is_array($queryParams)) {
        foreach ($queryParams as $fields) {
          if ($fields[0] == 'contribution_test') {
            $isTest = TRUE;
            break;
          }
        }
      }
      if (!$isTest) {
        $queryParams[] = [
          'contribution_test',
          '=',
          0,
          0,
          0,
        ];
      }
      $returnProperties = ['contribution_id' => 1];
      $sortOrder = $sortCol = NULL;
      if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
        $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
        //Include sort column in select clause.
        $sortCol = trim(str_replace(['`', 'asc', 'desc'], '', $sortOrder));
        $returnProperties[$sortCol] = 1;
      }

      $form->_includesSoftCredits = CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($queryParams);
      $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties, NULL, FALSE, FALSE,
        CRM_Contact_BAO_Query::MODE_CONTRIBUTE
      );
      // @todo the function CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled should handle this
      // can we remove? if not why not?
      if ($form->_includesSoftCredits) {
        $contactIds = $contributionContactIds = [];
        $query->_rowCountClause = " count(civicrm_contribution.id)";
        $query->_groupByComponentClause = " GROUP BY contribution_search_scredit_combined.id, contribution_search_scredit_combined.contact_id, contribution_search_scredit_combined.scredit_id ";
      }
      else {
        $query->_distinctComponentClause = ' civicrm_contribution.id';
        $query->_groupByComponentClause = ' GROUP BY civicrm_contribution.id ';
      }
      $result = $query->searchQuery(0, 0, $sortOrder);
      while ($result->fetch()) {
        $ids[] = $result->contribution_id;
        if ($form->_includesSoftCredits) {
          $contactIds[$result->contact_id] = $result->contact_id;
          $contributionContactIds["{$result->contact_id}-{$result->contribution_id}"] = $result->contribution_id;
        }
      }
      $form->assign('totalSelectedContributions', $form->get('rowCount'));
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_contribution.id IN ( ' . implode(',', $ids) . ' ) ';

      $form->assign('totalSelectedContributions', count($ids));
    }
    if (!empty($form->_includesSoftCredits) && !empty($contactIds)) {
      $form->_contactIds = $contactIds;
      $form->_contributionContactIds = $contributionContactIds;
    }

    $form->_contributionIds = $form->_componentIds = $ids;
    $form->set('contributionIds', $form->_contributionIds);

    //set the context for redirection for any task actions
    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $searchFormName = strtolower($form->get('searchFormName'));
    if ($searchFormName == 'search') {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/search', $urlParams));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url("civicrm/contact/search/$searchFormName",
        $urlParams
      ));
    }
  }

  /**
   * Sets contribution Ids for unit test.
   *
   * @param array $contributionIds
   */
  public function setContributionIds($contributionIds) {
    $this->_contributionIds = $contributionIds;
  }

  /**
   * Given the contribution id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    if (!$this->_includesSoftCredits) {
      $this->_contactIds = CRM_Core_DAO::getContactIDsFromComponent(
        $this->_contributionIds,
        'civicrm_contribution'
      );
    }
  }

  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
   * @param string $backType
   * @param bool $submitOnce
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons([
      [
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE,
      ],
      [
        'type' => $backType,
        'name' => ts('Cancel'),
      ],
    ]);
  }

}
