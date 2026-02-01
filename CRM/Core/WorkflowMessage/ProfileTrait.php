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
   * @throws \CRM_Core_Exception
   */
  public function getProfiles(): array {
    if (!isset($this->profiles)) {
      if ($this->isEventPage()) {
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
          $profile['module'] = $join['module'];
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
      elseif (isset($this->getContribution()['contribution_page_id'])) {
        $onBehalfIDs = CRM_Contribute_BAO_Contribution::getOnbehalfIds(
          $this->getContributionID(),
          $this->getContactID()
        );
        $joins = (array) UFJoin::get(FALSE)
          ->addWhere('entity_table', '=', 'civicrm_contribution_page')
          ->addWhere('entity_id', '=', $this->getContribution()['contribution_page_id'])
          ->addWhere('is_active', '=', TRUE)
          ->addSelect('module', 'weight', 'uf_group_id', 'uf_group_id.frontend_title')
          ->addOrderBy('weight')
          ->execute();
        $profiles = UFGroup::get(FALSE)
          ->addWhere('id', 'IN', CRM_Utils_Array::collect('uf_group_id', $joins))
          ->execute()->indexBy('id');
        foreach ($joins as $join) {
          // The thing we want to order by is on the join not the profile
          // hence we iterate the joins.
          $contactID = $this->getContactID();
          if ($join['module'] === 'soft_credit') {
            $contactID = $this->getSoftCredit()['contact_id'] ?? NULL;
          }
          if ($join['module'] === 'on_behalf') {
            // In this scenario we want the organization details
            $contactID = $onBehalfIDs['organization_id'] ?? NULL;
          }
          else {
            $profileTypes = CRM_Core_BAO_UFGroup::profileGroups($join['uf_group_id']);
            // if this is onbehalf of contribution then set related contact
            //for display profile need to get individual contact id,
            //hence get it from related_contact if on behalf of org true CRM-3767
            //CRM-5001 Contribution/Membership:: On Behalf of Organization,
            //If profile GROUP contain the Individual type then consider the
            //profile is of Individual ( including the custom data of membership/contribution )
            //IF Individual type not present in profile then it is consider as Organization data.
            $relatedContact = $onBehalfIDs['individual_id'] ?? NULL;
            if ($relatedContact) {
              if (in_array('Individual', $profileTypes) || in_array('Contact', $profileTypes)) {
                //Take Individual contact ID
                $contactID = (int) $relatedContact;
              }
            }
          }
          $profile = $profiles[$join['uf_group_id']];
          $profile['placement'] = $join['weight'] === 1 ? 'pre' : 'post';
          $profile['module'] = $join['module'];
          $profile['title'] = $join['uf_group_id.frontend_title'];
          $profile['fields'] = $contactID ? $this->getProfileFields($join['uf_group_id'], $contactID, (string) $join['module']) : [];
          $this->profiles[] = $profile;
        }
      }
    }
    return $this->profiles ?: [];
  }

  public function setProfiles(array $profiles): self {
    $this->profiles = $profiles;
    return $this;
  }

  /**
   * Get the profile title and fields.
   *
   * @param int $ufGroupID
   * @param int $contactID
   * @param string $module
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getProfileFields(int $ufGroupID, int $contactID, string $module): array {
    $values = [];
    $params = [];

    $profileType = CRM_Core_BAO_UFField::getProfileType($ufGroupID);
    if ($this->isMembershipReceipt() && $profileType == 'Membership') {
      $params = [
        [
          'member_id',
          '=',
          $this->getMembershipID(),
          0,
          0,
        ],
      ];
    }
    elseif ($profileType == 'Contribution' && $this->getContributionID()) {
      $params = [
        [
          'contribution_id',
          '=',
          $this->getContributionID(),
          0,
          0,
        ],
      ];
      if ($this->getIsTest()) {
        $params[] = [
          'contribution_test',
          '=',
          1,
          0,
          0,
        ];
      }
    }

    if (CRM_Core_BAO_UFGroup::filterUFGroups($ufGroupID, $contactID)) {
      $fields = CRM_Core_BAO_UFGroup::getFields($ufGroupID, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);
      if ($module === 'soft_credit') {
        $fields['display_name'] = [
          'name' => 'display_name',
          'title' => ts('Name'),
          'html_type' => 'Text',
          'data_type' => 'String',
          'field_type' => 'Text',
        ];
        unset($fields['first_name'], $fields['last_name'], $fields['prefix_id'], $fields['suffix_id'], $fields['organization_id'], $fields['household_name']);
      }
      foreach ($fields as $k => $v) {
        // suppress all file fields from display and formatting fields
        if (
          $v['data_type'] === 'File' || $v['name'] === 'image_URL' || $v['field_type'] === 'Formatting') {
          unset($fields[$k]);
        }
      }
      CRM_Core_BAO_UFGroup::getValues($contactID, $fields, $values, FALSE, $params, FALSE, NULL, 'email');
    }
    return $values;
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
  public function getProfilePreForm(): array {
    foreach ($this->getProfilesPreForm() as $profile) {
      return $profile;
    }
    return [];
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfilePostForm(): array {
    foreach ($this->getProfilesPostForm() as $profile) {
      return $profile;
    }
    return [];
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
  public function getProfileTitlePreForm(): string {
    foreach ($this->getProfilesByPlacement('pre') as $profile) {
      return $profile['frontend_title'];
    }
    return '';
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function getProfileTitlePostForm(): string {
    foreach ($this->getProfilesByPlacement('post') as $profile) {
      return $profile['frontend_title'];
    }
    return '';
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
      if (!empty($profile['is_additional_participant']) && !empty($profile['fields'])) {
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
      if (
        !$this->isEventPage()
        || (!empty($profile['participant_id']) && $profile['participant_id'] === $this->getParticipantID())) {
        if ($profile['placement'] === $placement) {
          $profiles[] = $profile;
        }
      }
    }
    return $profiles;
  }

  /**
   * @param string $module
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getProfilesByModule(string $module): array {
    $profiles = [];
    foreach ($this->getProfiles() as $profile) {
      if ($profile['module'] === $module) {
        $profiles[] = $profile;
      }
    }
    return $profiles;
  }

  /**
   * @param string $module
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getProfileByModule(string $module): array {
    foreach ($this->getProfilesByModule($module) as $profile) {
      return $profile;
    }
    return [];
  }

  /**
   * @return bool
   */
  private function isEventPage(): bool {
    return property_exists($this, 'eventID') && $this->getEventID();
  }

  /**
   * @return bool
   */
  private function isMembershipReceipt(): bool {
    return property_exists($this, 'membership') && $this->getMembershipID();
  }

}
