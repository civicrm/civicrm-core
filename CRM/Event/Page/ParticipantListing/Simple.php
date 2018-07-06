<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Event_Page_ParticipantListing_Simple extends CRM_Core_Page {

  protected $_id;

  protected $_participantListingType;

  protected $_eventTitle;

  protected $_pager;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);

    // retrieve Event Title and include it in page title
    $this->_eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'title'
    );
    CRM_Utils_System::setTitle(ts('%1 - Participants', array(1 => $this->_eventTitle)));

    // we do not want to display recently viewed contacts since this is potentially a public page
    $this->assign('displayRecent', FALSE);
  }

  /**
   * @return string
   */
  public function run() {
    $this->preProcess();

    $fromClause = "
FROM       civicrm_contact
INNER JOIN civicrm_participant ON ( civicrm_contact.id = civicrm_participant.contact_id
           AND civicrm_contact.is_deleted = 0 )
INNER JOIN civicrm_event       ON civicrm_participant.event_id = civicrm_event.id
LEFT JOIN  civicrm_email       ON ( civicrm_contact.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 )
";

    $whereClause = "
WHERE    civicrm_event.id = %1
AND      civicrm_participant.is_test = 0
AND      civicrm_participant.status_id IN ( 1, 2 )";
    $params = array(1 => array($this->_id, 'Integer'));
    $this->pager($fromClause, $whereClause, $params);
    $orderBy = $this->orderBy();

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    $query = "
SELECT   civicrm_contact.id           as contact_id    ,
         civicrm_contact.display_name as name          ,
         civicrm_contact.sort_name    as sort_name     ,
         civicrm_participant.id       as participant_id,
         civicrm_email.email          as email
         $fromClause
         $whereClause
ORDER BY $orderBy
LIMIT    $offset, $rowCount";

    $rows = array();
    $object = CRM_Core_DAO::executeQuery($query, $params);
    while ($object->fetch()) {
      $row = array(
        'id' => $object->contact_id,
        'participantID' => $object->participant_id,
        'name' => $object->name,
        'email' => $object->email,
      );
      $rows[] = $row;
    }
    $this->assign_by_ref('rows', $rows);

    return parent::run();
  }

  /**
   * @param $fromClause
   * @param $whereClause
   * @param array $whereParams
   */
  public function pager($fromClause, $whereClause, $whereParams) {

    $params = array();

    $params['status'] = ts('Group') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $query = "
SELECT count( civicrm_contact.id )
       $fromClause
       $whereClause
";

    $params['total'] = CRM_Core_DAO::singleValueQuery($query, $whereParams);
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  /**
   * @return string
   */
  public function orderBy() {
    static $headers = NULL;
    if (!$headers) {
      $headers = array();
      $headers[1] = array(
        'name' => ts('Name'),
        'sort' => 'civicrm_contact.sort_name',
        'direction' => CRM_Utils_Sort::ASCENDING,
      );
      if ($this->_participantListingType == 'Name and Email') {
        $headers[2] = array(
          'name' => ts('Email'),
          'sort' => 'civicrm_email.email',
          'direction' => CRM_Utils_Sort::DONTCARE,
        );
      }
    }
    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }
    $sort = new CRM_Utils_Sort($headers, $sortID);
    $this->assign_by_ref('headers', $headers);
    $this->assign_by_ref('sort', $sort);
    $this->set(CRM_Utils_Sort::SORT_ID,
      $sort->getCurrentSortID()
    );
    $this->set(CRM_Utils_Sort::SORT_DIRECTION,
      $sort->getCurrentSortDirection()
    );

    return $sort->orderBy();
  }

}
