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
class CRM_Event_Page_ParticipantListing_NameStatusAndDate extends CRM_Core_Page {

  protected $_id;

  protected $_participantListingID;

  protected $_eventTitle;

  protected $_pager;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this, TRUE);

    // ensure that there is a particpant type for this
    $this->_participantListingID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'participant_listing_id'
    );
    if (!$this->_participantListingID) {
      CRM_Core_Error::statusBounce(ts("The Participant Listing feature is not currently enabled for this event."));
    }

    // retrieve Event Title and include it in page title
    $this->_eventTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event',
      $this->_id,
      'title'
    );
    CRM_Utils_System::setTitle(ts('%1 - Participants', [1 => $this->_eventTitle]));

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
INNER JOIN civicrm_participant ON civicrm_contact.id = civicrm_participant.contact_id
INNER JOIN civicrm_event       ON civicrm_participant.event_id = civicrm_event.id
";

    $whereClause = "
WHERE    civicrm_event.id = %1";

    $params = [1 => [$this->_id, 'Integer']];
    $this->pager($fromClause, $whereClause, $params);
    $orderBy = $this->orderBy();

    list($offset, $rowCount) = $this->_pager->getOffsetAndRowCount();

    $query = "
SELECT   civicrm_contact.id                as contact_id    ,
         civicrm_contact.display_name      as name          ,
         civicrm_contact.sort_name         as sort_name     ,
         civicrm_participant.id            as participant_id,
         civicrm_participant.status_id     as status_id     ,
         civicrm_participant.register_date as register_date
         $fromClause
         $whereClause
ORDER BY $orderBy
LIMIT    $offset, $rowCount";

    $rows = [];
    $object = CRM_Core_DAO::executeQuery($query, $params);
    $statusLookup = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    while ($object->fetch()) {
      $status = $statusLookup[$object->status_id] ?? NULL;
      $row = [
        'id' => $object->contact_id,
        'participantID' => $object->participant_id,
        'name' => $object->name,
        'status' => $status,
        'date' => $object->register_date,
      ];
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

    $params = [];

    $params['status'] = ts('Group') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $this->get(CRM_Utils_Pager::PAGE_ROWCOUNT);
    if (!$params['rowCount']) {
      $params['rowCount'] = Civi::settings()->get('default_pager_size');
    }

    $query = "
SELECT count( civicrm_contact.id )
       $fromClause
       $whereClause";

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
      $headers = [];
      $headers[1] = [
        'name' => ts('Name'),
        'sort' => 'civicrm_contact.sort_name',
        'direction' => CRM_Utils_Sort::ASCENDING,
      ];
      $headers[2] = [
        'name' => ts('Status'),
        'sort' => 'civicrm_participant.status_id',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ];
      $headers[3] = [
        'name' => ts('Register Date'),
        'sort' => 'civicrm_participant.register_date',
        'direction' => CRM_Utils_Sort::DONTCARE,
      ];
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
