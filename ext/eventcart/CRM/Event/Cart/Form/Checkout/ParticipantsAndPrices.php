<?php

use Civi\Api4\Participant;

/**
 * Class CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices
 */
class CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices extends CRM_Event_Cart_Form_Cart {
  public $price_fields_for_event;
  public $_values = NULL;

  /**
   * Pre process function.
   */
  public function preProcess() {
    parent::preProcess();
    CRM_Core_Session::singleton()->replaceUserContext(
      CRM_Utils_System::url('civicrm/event/cart_checkout', [
        'qf_ParticipantsAndPrices_display' => 1,
      ])
    );

    $this->cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (!isset($this->cid) || $this->cid > 0) {
      //TODO users with permission can default to another contact
      $this->cid = self::getContactID();
    }
  }

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $this->price_fields_for_event = [];
    foreach ($this->cart->get_main_event_participants() as $participant) {
      $form = new CRM_Event_Cart_Form_MerParticipant($participant);
      $form->appendQuickForm($this);
    }
    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      $this->price_fields_for_event[$event_in_cart->event_id] = $this->build_price_options($event_in_cart->event);
    }
    //If events in cart have discounts the textbox for discount code will be displayed at the top, as long as this
    //form name is added to cividiscount
    $this->assign('events_in_carts', $this->cart->get_main_events_in_carts());
    $this->assign('price_fields_for_event', $this->price_fields_for_event);
    $this->addButtons(
      [
        [
          'type' => 'upload',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ],
      ]
    );

    if ($this->cid) {
      $params = ['id' => $this->cid];
      $contact = CRM_Contact_BAO_Contact::retrieve($params, $defaults);
      $contact_values = [];
      CRM_Core_DAO::storeValues($contact, $contact_values);
      $this->assign('contact', $contact_values);
    }
  }

  /**
   * Get the primary emil for the contact.
   *
   * @param CRM_Contact_BAO_Contact $contact
   *
   * @return string
   */
  public static function primary_email_from_contact($contact) {
    foreach ($contact->email as $email) {
      if ($email['is_primary']) {
        return $email['email'];
      }
    }

    return NULL;
  }

  /**
   * Build price options.
   *
   * @param CRM_Event_BAO_Event $event
   *
   * @return array
   */
  public function build_price_options($event) {
    $price_fields_for_event = [];
    $base_field_name = "event_{$event->id}_amount";
    $price_set_id = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $event->id);
    //CRM-14492 display admin fields only if user is admin
    $adminFieldVisible = FALSE;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $adminFieldVisible = TRUE;
    }
    if ($price_set_id) {
      $price_sets = CRM_Price_BAO_PriceSet::getSetDetail($price_set_id, TRUE, TRUE);
      $price_set = $price_sets[$price_set_id];
      $index = -1;
      foreach ($price_set['fields'] as $field) {
        $index++;
        if (CRM_Utils_Array::value('visibility', $field) == 'public' ||
           (CRM_Utils_Array::value('visibility', $field) == 'admin' && $adminFieldVisible == TRUE)) {
          $field_name = "event_{$event->id}_price_{$field['id']}";
          if (!empty($field['options'])) {
            CRM_Price_BAO_PriceField::addQuickFormElement($this, $field_name, $field['id'], FALSE);
            $price_fields_for_event[] = $field_name;
          }
        }
      }
    }
    return $price_fields_for_event;
  }

  /**
   * Validate values.
   *
   * @return bool
   */
  public function validate() {
    parent::validate();
    $this->cart->load_associations();

    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      // Validate if participant is already registered
      if ($event_in_cart->event->allow_same_participant_emails) {
        continue;
      }

      foreach ($event_in_cart->participants as $mer_participant) {
        $participant_fields = $this->_submitValues['field'][$mer_participant->id];
        //TODO what to do when profile responses differ for the same contact?
        $contact_id = self::find_contact($participant_fields);

        if ($contact_id) {
          $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
          $participant = Participant::get(FALSE)
            ->addWhere('event_id', '=', $event_in_cart->event_id)
            ->addWhere('contact_id', '=', $contact_id)
            ->addWhere('status_id', 'IN', array_keys($statusTypes))
            ->execute()
            ->first();
          if (!empty($participant)) {
            $form = $mer_participant->get_form();
            $this->_errors[$form->html_field_name('email')] = ts("The participant %1 is already registered for %2 (%3).", [
              1 => $participant_fields['email-Primary'],
              2 => $event_in_cart->event->title,
              3 => $event_in_cart->event->start_date,
            ]);
          }
        }
      }
    }
    if (empty($this->_errors)) {
      return TRUE;
    }
    CRM_Core_Error::statusBounce(implode('<br/>', $this->_errors));
    return FALSE;
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $this->loadCart();

    $defaults = [];
    /** @var \CRM_Event_Cart_BAO_MerParticipant $participant */
    foreach ($this->cart->get_main_event_participants() as $participant) {
      /** @var \CRM_Event_Cart_Form_MerParticipant $merParticipantForm */
      $merParticipantForm = $participant->get_form();
      if (($this->cid == 0) && ($participant->contact_id == self::getContactID())) {
        // Create a new contact
        $participant->email = NULL;
        $participant->contact_id = self::find_or_create_contact();
      }

      $defaults += $merParticipantForm->setDefaultValues();
      //Set price defaults if any
      foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
        $event_id = $event_in_cart->event_id;
        $price_set_id = CRM_Event_BAO_Event::usesPriceSet($event_in_cart->event_id);
        if ($price_set_id) {
          $price_sets = CRM_Price_BAO_PriceSet::getSetDetail($price_set_id, TRUE, TRUE);
          $price_set  = $price_sets[$price_set_id];
          foreach ($price_set['fields'] as $field) {
            $options = $field['options'] ?? NULL;
            if (!is_array($options)) {
              continue;
            }
            $field_name = "event_{$event_id}_price_{$field['id']}";
            foreach ($options as $value) {
              if ($value['is_default']) {
                if ($field['html_type'] == 'Checkbox') {
                  $defaults[$field_name] = 1;
                }
                else {
                  $defaults[$field_name] = $value['id'];
                }
              }
            }
          }
        }
      }
    }
    return $defaults;
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    foreach ($submittedValues['field'] as $participant_id => $fields) {
      $participant = Participant::get(FALSE)
        ->addWhere('id', '=', $participant_id)
        ->execute()
        ->first();

      // Email sometimes gets passed in as eg. "email-Primary"
      // Normalise it to "email"
      foreach ($fields as $key => $value) {
        if (substr($key, 0, 5) === 'email') {
          $fields['email'] = $fields[$key];
          unset($fields[$key]);
          break;
        }
      }

      $contact_id = self::find_or_create_contact($fields, $participant['contact_id']);
      $participant = $this->cart->get_event_in_cart_by_event_id($participant['event_id'])->get_participant_by_id($participant_id);
      if ($participant->contact_id && $contact_id != $participant->contact_id) {
        foreach ($this->cart->get_subparticipants($participant) as $subparticipant) {
          $subparticipant->contact_id = $contact_id;
          $subparticipant->save();
        }

        $participant->contact_id = $contact_id;
        $participant->save();
      }

      $participantParams = [
        'id' => $participant_id,
        'cart_id' => $this->cart->id,
        'event_id' => $participant->event_id,
        'contact_id' => $contact_id,
        'email' => $fields['email'],
      ];
      $this->cart->add_participant_to_cart($participantParams);

      if (array_key_exists('field', $this->_submitValues) && array_key_exists($participant_id, $this->_submitValues['field'])) {
        $custom_fields = $participant->get_form()->get_participant_custom_data_fields();

        CRM_Contact_BAO_Contact::createProfileContact($this->_submitValues['field'][$participant_id], $custom_fields, $contact_id);

        CRM_Core_BAO_CustomValueTable::postProcess($this->_submitValues['field'][$participant_id],
          'civicrm_participant',
          $participant_id,
          'Participant'
        );
      }
    }
    $this->cart->save();
  }

}
