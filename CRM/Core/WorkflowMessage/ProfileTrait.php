<?php

use Civi\Api4\UFGroup;
use Civi\Api4\UFJoin;

/**
 * Trait for participant workflow classes.
 *
 * @method string getNote()
 */
trait CRM_Core_WorkflowMessage_ProfileTrait {

  /**
   * The profiles array is not currently assigned / used but might
   * be saner than the ones that are.
   *
   * @var array
   */
  protected $profiles;

  /**
   * The note, if any, from the session.
   *
   * As multiple notes can be attached to a participant records we only want
   * to render a note as part of the profile if the user entered it in the form submission.
   *
   * @var string
   */
  protected string $note = '';

  /**
   * @var array
   *
   * @scope tplParams as customPre
   */
  public $profilesPreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost
   */
  public $profilesPostForm;

  /**
   * @var array
   *
   * @scope tplParams as customPre_grouptitle
   */
  public $profileTitlesPreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost_grouptitle
   */
  public $profileTitlesPostForm;

  /**
   * @var array
   *
   * @scope tplParams as customProfile
   */
  public $profilesAdditionalParticipants;

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfiles(): array {
    if (!isset($this->profiles)) {
      if ($this->getEventID()) {
        $joins = (array) UFJoin::get(FALSE)
          ->addWhere('entity_table', '=', 'civicrm_event')
          ->addWhere('entity_id', '=', $this->getEventID())
          ->addWhere('is_active', '=', TRUE)
          ->addSelect('module', 'weight', 'uf_group_id')
          ->addOrderBy('weight')
          ->execute();
        $profiles = UFGroup::get(FALSE)
          ->addWhere('id', 'IN', CRM_Utils_Array::collect('uf_group_id', $joins))
          ->execute()->indexBy('id');
        foreach ($joins as $join) {
          // The thing we want to order by is on the join not the profile
          // hence we iterate the joins.
          $profile = $profiles[$join['uf_group_id']];
          $profile['placement'] = $join['weight'] === 1 ? 'pre' : 'post';
          $profile['is_additional_participant'] = $join['module'] === 'CiviEvent_Additional';
          if ($join['module'] === 'CiviEvent') {
            $profile['participant_id'] = $this->getParticipantID();
            try {
              $fields = CRM_Event_BAO_Event::getProfileDisplay([$profile['id']],
                $this->getParticipant()['contact_id'] ?? NULL,
                $this->getParticipantID(),
                $this->getNote(),
              );
            }
            catch (CRM_Core_Exception $e) {
              // This could be false if the person does not have permission. This came up in
              // the SelfSvcTransfer workflow via test testTransferAnonymous & it seems OK
              // to not include profile data in this scenario ... probably.
              $fields = [];
            }
            $profile['fields'] = $fields ? $fields[0] : [];
          }
          elseif ($profile['is_additional_participant']) {
            foreach ($this->getParticipants() as $participant) {
              // Only show the other participants for the primary participant.
              if ($this->getIsPrimary() && !$participant['is_primary']) {
                if (!isset($profile['fields'])) {
                  $profile['fields'] = [];
                }
                try {
                  $fields = CRM_Event_BAO_Event::getProfileDisplay([$profile['id']],
                    $participant['contact']['id'] ?? NULL,
                    $participant['id'],
                    $this->getNote()
                  );
                }
                catch (CRM_Core_Exception $e) {
                  // This could be false if the person does not have permission. This came up in
                  // the SelfSvcTransfer workflow via test testTransferAnonymous & it seems OK
                  // to not include profile data in this scenario ... probably.
                  $fields = [];
                }
                $profile['fields'][] = $fields ? $fields[0] : [];

              }
            }
          }

          $this->profiles[] = $profile;
        }
      }
    }
    return $this->profiles ?: [];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfilesPreForm(): array {
    $profiles = [];
    foreach ($this->getProfilesByPlacement('pre') as $profile) {
      $profiles[] = $profile['fields'];
    }
    return $profiles;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfileTitlesPreForm(): array {
    $titles = [];
    foreach ($this->getProfilesByPlacement('pre') as $profile) {
      $titles[] = $profile['frontend_title'];
    }
    return $titles;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfileTitlesPostForm(): array {
    $titles = [];
    foreach ($this->getProfilesByPlacement('post') as $profile) {
      $titles[] = $profile['frontend_title'];
    }
    return $titles;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfilesPostForm(): array {
    $profiles = [];
    foreach ($this->getProfilesByPlacement('post') as $profile) {
      $profiles[] = $profile['fields'];
    }
    return $profiles;
  }

  public function getProfilesAdditionalParticipants(): array {
    $profiles = [];
    foreach ($this->getProfiles() as $profile) {
      if ($profile['is_additional_participant'] && !empty($profile['fields'])) {
        foreach ($profile['fields'] as $participantIndex => $fields) {
          $profiles['profile'][$participantIndex][$profile['id']] = $profile['fields'][$participantIndex];
        }
        $profiles['title'][$profile['id']] = $profile['title'];
      }
    }
    return $profiles;
  }

  /**
   * @param string $placement
   *   pre or post.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getProfilesByPlacement(string $placement): array {
    $profiles = [];
    foreach ($this->getProfiles() as $profile) {
      if (!empty($profile['participant_id']) && $profile['participant_id'] === $this->getParticipantID()) {
        if ($profile['placement'] === $placement) {
          $profiles[] = $profile;
        }
      }
    }
    return $profiles;
  }

}
