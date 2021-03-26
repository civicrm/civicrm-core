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
 * This class provides shared contribution task functionality.
 */
trait CRM_Contribute_Form_Task_TaskTrait {

  /**
   * Query result object.
   *
   * @var \CRM_Core_DAO
   */
  protected $queryBAO;

  /**
   * Get the results from the BAO_Query object based search.
   *
   * @return CRM_Core_DAO
   *
   * @throws \CRM_Core_Exception
   */
  public function getSearchQueryResults(): CRM_Core_DAO {
    $form = $this;
    $queryParams = $this->getQueryParams();
    $returnProperties = ['contribution_id' => 1];
    $sortOrder = $sortCol = NULL;
    if ($form->get(CRM_Utils_Sort::SORT_ORDER)) {
      $sortOrder = $form->get(CRM_Utils_Sort::SORT_ORDER);
      //Include sort column in select clause.
      $sortCol = trim(str_replace(['`', 'asc', 'desc'], '', $sortOrder));
      $returnProperties[$sortCol] = 1;
    }

    $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CONTRIBUTE
    );
    // @todo the function CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled should handle this
    // can we remove? if not why not?
    if ($this->isQueryIncludesSoftCredits()) {
      $query->_rowCountClause = ' count(civicrm_contribution.id)';
      $query->_groupByComponentClause = ' GROUP BY contribution_search_scredit_combined.id, contribution_search_scredit_combined.contact_id, contribution_search_scredit_combined.scredit_id ';
    }
    else {
      $query->_distinctComponentClause = ' civicrm_contribution.id';
      $query->_groupByComponentClause = ' GROUP BY civicrm_contribution.id ';
    }
    return $query->searchQuery(0, 0, $sortOrder);
  }

  /**
   * Get the query parameters, adding test = FALSE if needed.
   *
   * @return array|null
   */
  protected function getQueryParams(): ?array {
    $queryParams = $this->get('queryParams');
    if (!is_array($queryParams)) {
      return NULL;
    }
    foreach ($queryParams as $fields) {
      if ($fields[0] === 'contribution_test') {
        return $queryParams;
      }
    }
    $queryParams[] = [
      'contribution_test',
      '=',
      0,
      0,
      0,
    ];
    return $queryParams;
  }

  /**
   * Has soft credit information been requested in the query filters.
   *
   * @return bool
   */
  public function isQueryIncludesSoftCredits(): bool {
    return (bool) CRM_Contribute_BAO_Query::isSoftCreditOptionEnabled($this->getQueryParams());
  }

}
