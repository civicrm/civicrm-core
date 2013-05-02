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


/*
 * This file check and updates the status of all participant records.
 * 
 * EventParticipantion.php prior to running this script.
 */

require_once '../civicrm.config.php';
require_once 'CRM/Core/Config.php';
class CRM_ParticipantProcessor {
  function __construct() {
    $config = CRM_Core_Config::singleton();

    //this does not return on failure
    require_once 'CRM/Utils/System.php';
    require_once 'CRM/Utils/Hook.php';

    CRM_Utils_System::authenticateScript(TRUE);

    //log the execution time of script
    CRM_Core_Error::debug_log_message('ParticipantProcessor.php');
  }

  public function updateParticipantStatus() {
    require_once 'CRM/Event/PseudoConstant.php';
    $participantRole = CRM_Event_PseudoConstant::participantRole();
    $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
    $expiredStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
    $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");

    //build the required status ids.
    $statusIds = '(' . implode(',', array_merge(array_keys($pendingStatuses), array_keys($waitingStatuses))) . ')';

    $participantDetails = $fullEvents = array();
    $expiredParticipantCount = $waitingConfirmCount = $waitingApprovalCount = 0;

    //get all participant who's status in class pending and waiting
    $query = "SELECT * FROM civicrm_participant WHERE status_id IN {$statusIds} ORDER BY register_date";

    $query = "
   SELECT  participant.id,
           participant.contact_id,
           participant.status_id,
           participant.register_date,
           participant.registered_by_id,
           participant.event_id,
           event.title as eventTitle,
           event.registration_start_date,
           event.registration_end_date,
           event.end_date,
           event.expiration_time,
           event.requires_approval
     FROM  civicrm_participant participant
LEFT JOIN  civicrm_event event ON ( event.id = participant.event_id )
    WHERE  participant.status_id IN {$statusIds}
     AND   (event.end_date > now() OR event.end_date IS NULL)
     AND   event.is_active = 1 
 ORDER BY  participant.register_date, participant.id 
";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $participantDetails[$dao->id] = array(
        'id' => $dao->id,
        'event_id' => $dao->event_id,
        'status_id' => $dao->status_id,
        'contact_id' => $dao->contact_id,
        'register_date' => $dao->register_date,
        'registered_by_id' => $dao->registered_by_id,
        'eventTitle' => $dao->eventTitle,
        'registration_start_date' => $dao->registration_start_date,
        'registration_end_date' => $dao->registration_end_date,
        'end_date' => $dao->end_date,
        'expiration_time' => $dao->expiration_time,
        'requires_approval' => $dao->requires_approval,
      );
    }

    if (!empty($participantDetails)) {
      //cron 1. move participant from pending to expire if needed
      foreach ($participantDetails as $participantId => $values) {
        //process the additional participant at the time of
        //primary participant, don't process separately.
        if (CRM_Utils_Array::value('registered_by_id', $values)) {
          continue;
        }

        $expirationTime = CRM_Utils_Array::value('expiration_time', $values);
        if ($expirationTime && array_key_exists($values['status_id'], $pendingStatuses)) {

          //get the expiration and registration pending time.
          $expirationSeconds = $expirationTime * 3600;
          $registrationPendingSeconds = CRM_Utils_Date::unixTime($values['register_date']);

          // expired registration since registration cross allow confirmation time.
          if (($expirationSeconds + $registrationPendingSeconds) < time()) {

            //lets get the transaction mechanism.
            require_once 'CRM/Core/Transaction.php';
            $transaction = new CRM_Core_Transaction();

            require_once 'CRM/Event/BAO/Participant.php';
            $ids       = array($participantId);
            $expiredId = array_search('Expired', $expiredStatuses);
            $results   = CRM_Event_BAO_Participant::transitionParticipants($ids, $expiredId, $values['status_id'], TRUE, TRUE);
            $transaction->commit();

            if (!empty($results)) {
              //diaplay updated participants
              if (is_array($results['updatedParticipantIds']) && !empty($results['updatedParticipantIds'])) {
                foreach ($results['updatedParticipantIds'] as $processedId) {
                  $expiredParticipantCount += 1;
                  echo "<br /><br />- status updated to: Expired";

                  //mailed participants.
                  if (is_array($results['mailedParticipants']) &&
                    array_key_exists($processedId, $results['mailedParticipants'])
                  ) {
                    echo "<br />Expiration Mail sent to: {$results['mailedParticipants'][$processedId]}";
                  }
                }
              }
            }
          }
        }
      }
      //cron 1 end.

      //cron 2. lets move participants from waiting list to pending status
      foreach ($participantDetails as $participantId => $values) {
        //process the additional participant at the time of
        //primary participant, don't process separately.
        if (CRM_Utils_Array::value('registered_by_id', $values)) {
          continue;
        }

        if (array_key_exists($values['status_id'], $waitingStatuses) &&
          !array_key_exists($values['event_id'], $fullEvents)
        ) {

          if ($waitingStatuses[$values['status_id']] == 'On waitlist' &&
            CRM_Event_BAO_Event::validRegistrationDate($values)
          ) {

            //check the target event having space.
            require_once 'CRM/Event/BAO/Participant.php';
            $eventOpenSpaces = CRM_Event_BAO_Participant::eventFull($values['event_id'], TRUE, FALSE);

            if ($eventOpenSpaces && is_numeric($eventOpenSpaces) || ($eventOpenSpaces === NULL)) {

              //get the additional participant if any.
              $additionalIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId);

              $allIds = array($participantId);
              if (!empty($additionalIds)) {
                $allIds = array_merge($allIds, $additionalIds);
              }
              $pClause = ' participant.id IN ( ' . implode(' , ', $allIds) . ' )';
              $requiredSpaces = CRM_Event_BAO_Event::eventTotalSeats($values['event_id'], $pClause);

              //need to check as to see if event has enough speces
              if (($requiredSpaces <= $eventOpenSpaces) || ($eventOpenSpaces === NULL)) {
                require_once 'CRM/Core/Transaction.php';
                $transaction = new CRM_Core_Transaction();

                require_once 'CRM/Event/BAO/Participant.php';
                $ids = array($participantId);
                $updateStatusId = array_search('Pending from waitlist', $pendingStatuses);

                //lets take a call to make pending or need approval
                if ($values['requires_approval']) {
                  $updateStatusId = array_search('Awaiting approval', $waitingStatuses);
                }
                $results = CRM_Event_BAO_Participant::transitionParticipants($ids, $updateStatusId,
                  $values['status_id'], TRUE, TRUE
                );
                //commit the transaction.
                $transaction->commit();

                if (!empty($results)) {
                  //diaplay updated participants
                  if (is_array($results['updatedParticipantIds']) &&
                    !empty($results['updatedParticipantIds'])
                  ) {
                    foreach ($results['updatedParticipantIds'] as $processedId) {
                      if ($values['requires_approval']) {
                        $waitingApprovalCount += 1;
                        echo "<br /><br />- status updated to: Awaiting approval";
                        echo "<br />Will send you Confirmation Mail when registration get approved.";
                      }
                      else {
                        $waitingConfirmCount += 1;
                        echo "<br /><br />- status updated to: Pending from waitlist";
                        if (is_array($results['mailedParticipants']) &&
                          array_key_exists($processedId, $results['mailedParticipants'])
                        ) {
                          echo "<br />Confirmation Mail sent to: {$results['mailedParticipants'][$processedId]}";
                        }
                      }
                    }
                  }
                }
              }
              else {
                //target event is full.
                $fullEvents[$values['event_id']] = $values['eventTitle'];
              }
            }
            else {
              //target event is full.
              $fullEvents[$values['event_id']] = $values['eventTitle'];
            }
          }
        }
      }
      //cron 2 ends.
    }

    echo "<br /><br />Number of Expired registration(s) = {$expiredParticipantCount}";
    echo "<br />Number of registration(s) require approval =  {$waitingApprovalCount}";
    echo "<br />Number of registration changed to Pending from waitlist = {$waitingConfirmCount}<br /><br />";
    if (!empty($fullEvents)) {
      foreach ($fullEvents as $eventId => $title) {
        echo "Full Event : {$title}<br />";
      }
    }
  }
}

$obj = new CRM_ParticipantProcessor();
echo "Updating..";
$obj->updateParticipantStatus();
echo "<br />Participant records updated. (Done)";


