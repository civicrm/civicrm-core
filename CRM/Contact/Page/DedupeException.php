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
 * Main page for viewing contact.
 */
class CRM_Contact_Page_DedupeException extends CRM_Core_Page {

  /**
   * @var CRM_Utils_Pager
   * @internal
   */
  public $_pager;

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->initializePager();
    $this->assign('exceptions', $this->getExceptions());
    return parent::run();
  }

  /**
   * Method to initialize pager
   *
   * @access protected
   */
  protected function initializePager() {
    $params = [];

    $contactOneQ = CRM_Utils_Request::retrieve('crmContact1Q', 'String');

    if ($contactOneQ) {
      $params['contact_id1.display_name'] = ['LIKE' => '%' . $contactOneQ . '%'];
      $params['contact_id2.display_name'] = ['LIKE' => '%' . $contactOneQ . '%'];

      $params['options']['or'] = [["contact_id1.display_name", "contact_id2.display_name"]];
    }

    $totalitems = civicrm_api3('Exception', "getcount", $params);
    $params           = [
      'total' => $totalitems,
      'rowCount' => Civi::settings()->get('default_pager_size'),
      'status' => ts('Dedupe Exceptions %%StatusMessage%%'),
      'buttonBottom' => 'PagerBottomButton',
      'buttonTop' => 'PagerTopButton',
      'pageID' => $this->get(CRM_Utils_Pager::PAGE_ID),
    ];
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign('pager', $this->_pager);
  }

  /**
   * Function to get the exceptions
   *
   * @return array $exceptionsd
   */
  public function getExceptions() {
    list($offset, $limit) = $this->_pager->getOffsetAndRowCount();
    $contactOneQ = CRM_Utils_Request::retrieve('crmContact1Q', 'String');

    if (!$contactOneQ) {
      $contactOneQ = '';
    }

    $this->assign('searchcontact1', $contactOneQ);

    $params = [
      "options"     => ['limit' => $limit, 'offset' => $offset],
      'return' => ["contact_id1.display_name", "contact_id2.display_name", "contact_id1", "contact_id2"],
    ];

    if ($contactOneQ != '') {
      $params['contact_id1.display_name'] = ['LIKE' => '%' . $contactOneQ . '%'];
      $params['contact_id2.display_name'] = ['LIKE' => '%' . $contactOneQ . '%'];

      $params['options']['or'] = [["contact_id1.display_name", "contact_id2.display_name"]];
    }

    $exceptions = civicrm_api3("Exception", "get", $params);
    $exceptions = $exceptions["values"];
    return $exceptions;
  }

}
