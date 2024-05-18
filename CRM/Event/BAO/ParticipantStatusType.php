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
class CRM_Event_BAO_ParticipantStatusType extends CRM_Event_DAO_ParticipantStatusType {

  /**
   * @deprecated
   * @param array $params
   *
   * @return self|null
   */
  public static function add(&$params) {
    return self::writeRecord($params);
  }

  /**
   * @deprecated
   * @param array $params
   *
   * @return self|null
   */
  public static function create(&$params) {
    return self::writeRecord($params);
  }

  /**
   * @param int $id
   *
   * @return bool
   */
  public static function deleteParticipantStatusType($id) {
    // return early if there are participants with this status
    $participant = new CRM_Event_DAO_Participant();
    $participant->status_id = $id;
    if ($participant->find()) {
      return FALSE;
    }

    CRM_Utils_Weight::delWeight('CRM_Event_DAO_ParticipantStatusType', $id);

    $dao = new CRM_Event_DAO_ParticipantStatusType();
    $dao->id = $id;
    if (!$dao->find()) {
      return FALSE;
    }
    $dao->delete();
    return TRUE;
  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $isActive
   * @return bool
   */
  public static function setIsActive($id, $isActive) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Event_BAO_ParticipantStatusType', $id, 'is_active', $isActive);
  }

  /**
   * Checks if status_id (id or string (eg. 5 or "Pending from pay later") is allowed for class
   *
   * @param int|string $status_id
   * @param string $class
   *
   * @return bool
   */
  public static function getIsValidStatusForClass($status_id, $class = 'Pending') {
    $classParticipantStatuses = civicrm_api3('ParticipantStatusType', 'get', [
      'class' => $class,
      'is_active' => 1,
    ])['values'];
    $allowedParticipantStatuses = [];
    foreach ($classParticipantStatuses as $id => $detail) {
      $allowedParticipantStatuses[$id] = $detail['name'];
    }
    if (in_array($status_id, $allowedParticipantStatuses) || array_key_exists($status_id, $allowedParticipantStatuses)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param array $params
   *
   * @return array
   */
  public static function process($params) {

    $returnMessages = [];

    $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
    $expiredStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'");
    $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");

    //build the required status ids.
    $statusIds = '(' . implode(',', array_merge(array_keys($pendingStatuses), array_keys($waitingStatuses))) . ')';

    $participantDetails = $fullEvents = [];
    $expiredParticipantCount = $waitingConfirmCount = $waitingApprovalCount = 0;

    //get all participant who's status in class pending and waiting
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
      $participantDetails[$dao->id] = [
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
      ];
    }

    if (!empty($participantDetails)) {
      //cron 1. move participant from pending to expire if needed
      foreach ($participantDetails as $participantId => $values) {
        //process the additional participant at the time of
        //primary participant, don't process separately.
        if (!empty($values['registered_by_id'])) {
          continue;
        }

        $expirationTime = $values['expiration_time'] ?? NULL;
        if ($expirationTime && array_key_exists($values['status_id'], $pendingStatuses)) {

          //get the expiration and registration pending time.
          $expirationSeconds = $expirationTime * 3600;
          $registrationPendingSeconds = CRM_Utils_Date::unixTime($values['register_date']);

          // expired registration since registration cross allow confirmation time.
          if (($expirationSeconds + $registrationPendingSeconds) < time()) {

            //lets get the transaction mechanism.
            $transaction = new CRM_Core_Transaction();

            $ids = [$participantId];
            $expiredId = array_search('Expired', $expiredStatuses);
            $results = CRM_Event_BAO_Participant::transitionParticipants($ids, $expiredId, $values['status_id'], TRUE, TRUE);
            $transaction->commit();

            if (!empty($results)) {
              //diaplay updated participants
              if (is_array($results['updatedParticipantIds']) && !empty($results['updatedParticipantIds'])) {
                foreach ($results['updatedParticipantIds'] as $processedId) {
                  $expiredParticipantCount += 1;
                  $returnMessages[] = "<br />Status updated to: Expired";

                  //mailed participants.
                  if (is_array($results['mailedParticipants']) &&
                    array_key_exists($processedId, $results['mailedParticipants'])
                  ) {
                    $returnMessages[] = "<br />Expiration Mail sent to: {$results['mailedParticipants'][$processedId]}";
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
        if (!empty($values['registered_by_id'])) {
          continue;
        }

        if (array_key_exists($values['status_id'], $waitingStatuses) &&
          !array_key_exists($values['event_id'], $fullEvents)
        ) {

          if ($waitingStatuses[$values['status_id']] == 'On waitlist' &&
            CRM_Event_BAO_Event::validRegistrationDate($values)
          ) {

            //check the target event having space.
            $eventOpenSpaces = CRM_Event_BAO_Participant::eventFull($values['event_id'], TRUE, FALSE);

            if ($eventOpenSpaces && is_numeric($eventOpenSpaces) || ($eventOpenSpaces === NULL)) {

              //get the additional participant if any.
              $additionalIds = CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId);

              $allIds = [$participantId];
              if (!empty($additionalIds)) {
                $allIds = array_merge($allIds, $additionalIds);
              }
              $pClause = ' participant.id IN ( ' . implode(' , ', $allIds) . ' )';
              $requiredSpaces = CRM_Event_BAO_Event::eventTotalSeats($values['event_id'], $pClause);

              //need to check as to see if event has enough speces
              if (($requiredSpaces <= $eventOpenSpaces) || ($eventOpenSpaces === NULL)) {
                $transaction = new CRM_Core_Transaction();

                $ids = [$participantId];
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
                        $returnMessages[] = "<br /><br />- status updated to: Awaiting approval";
                        $returnMessages[] = "<br />Will send you Confirmation Mail when registration gets approved.";
                      }
                      else {
                        $waitingConfirmCount += 1;
                        $returnMessages[] = "<br /><br />- status updated to: Pending from waitlist";
                        if (is_array($results['mailedParticipants']) &&
                          array_key_exists($processedId, $results['mailedParticipants'])
                        ) {
                          $returnMessages[] = "<br />Confirmation Mail sent to: {$results['mailedParticipants'][$processedId]}";
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

    $returnMessages[] = "<br /><br />Number of Expired registration(s) = {$expiredParticipantCount}";
    $returnMessages[] = "<br />Number of registration(s) require approval =  {$waitingApprovalCount}";
    $returnMessages[] = "<br />Number of registration changed to Pending from waitlist = {$waitingConfirmCount}<br /><br />";
    if (!empty($fullEvents)) {
      foreach ($fullEvents as $eventId => $title) {
        $returnMessages[] = "Full Event : {$title}<br />";
      }
    }

    return ['is_error' => 0, 'messages' => $returnMessages];
  }

}
