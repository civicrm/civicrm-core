<?php

use Civi\Api4\EventCartParticipant;

/**
 * Class CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices
 */
class CRM_Event_Cart_Form_Checkout_ParticipantsAndPrices extends CRM_Event_Cart_Form_Cart {
  public $price_fields_for_event;
  public $_values = NULL;

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
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ]
    );

    if ($this->getContactID()) {
      $params = ['id' => $this->getContactID()];
      $contact = $this->retrieveContact($params, $defaults);
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
    $price_set_id = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $event->id);
    //CRM-14492 display admin fields only if user is admin
    $adminFieldVisible = FALSE;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $adminFieldVisible = TRUE;
    }
    if ($price_set_id) {
      $price_sets = CRM_Price_BAO_PriceSet::getSetDetail($price_set_id, TRUE, TRUE);
      $price_set = $price_sets[$price_set_id];
      foreach ($price_set['fields'] as $field) {
        if (($field['visibility'] ?? '') === 'public' ||
           (($field['visibility'] ?? '') === 'admin' && $adminFieldVisible == TRUE)) {
          $field_name = "event_{$event->id}_price_{$field['id']}";
          if (!empty($field['options'])) {
            $options = $field['options'];
            foreach ($options as &$option) {
              if (!isset($option['tax_amount'])) {
                $option['tax_amount'] = 0;
              }
            }
            CRM_Price_BAO_PriceField::addQuickFormElement($this, $field_name, $field['id'], FALSE, TRUE, NULL, $options);
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
    if ($this->_errors) {
      return FALSE;
    }
    $this->cart->load_associations();
    $fields = $this->_submitValues;

    foreach ($this->cart->get_main_events_in_carts() as $event_in_cart) {
      $price_set_id = CRM_Event_BAO_Event::usesPriceSet($event_in_cart->event_id);
      if ($price_set_id) {
        $priceField = new CRM_Price_DAO_PriceField();
        $priceField->price_set_id = $price_set_id;
        $priceField->find();

        $check = [];

        while ($priceField->fetch()) {
          if (!empty($fields["event_{$event_in_cart->event_id}_price_{$priceField->id}"])) {
            $check[] = $priceField->id;
          }
        }

        //XXX
        if (empty($check)) {
          $this->_errors['_qf_default'] = ts("Select at least one option from Price Levels.");
        }

        $lineItem = [];
        if (isset($this->_values['fee']['fields']) && is_array($this->_values['fee']['fields'])) {
          // Probably unreachable.
          $this->processCartAmount($this->_values['fee']['fields'], $fields, $lineItem);
          //XXX total...
          if ($fields['amount'] < 0) {
            $this->_errors['_qf_default'] = ts("Price Levels can not be less than zero. Please select the options accordingly");
          }
        }
      }

      // Validate if participant is already registered
      if ($event_in_cart->event->allow_same_participant_emails) {
        continue;
      }

      foreach ($event_in_cart->participants as $mer_participant) {
        $participant_fields = $fields['event'][$event_in_cart->event_id]['participant'][$mer_participant->id];
        //TODO what to do when profile responses differ for the same contact?
        $contact_id = self::find_contact($participant_fields);

        if ($contact_id) {
          $participant = new CRM_Event_BAO_Participant();
          $participant->event_id = $event_in_cart->event_id;
          $participant->contact_id = $contact_id;
          $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
          $participant->find();
          while ($participant->fetch()) {
            if (array_key_exists($participant->status_id, $statusTypes)) {
              $form = $mer_participant->get_form();
              $this->_errors[$form->html_field_name('email')] = ts("The participant %1 is already registered for %2 (%3).", [
                1 => $participant_fields['email'],
                2 => $event_in_cart->event->title,
                3 => $event_in_cart->event->start_date,
              ]);
            }
          }
        }
      }
    }
    return empty($this->_errors);
  }

  /**
   * Get line item purchase information.
   *
   * This function takes the input parameters and interprets out of it what has been purchased.
   *
   * @param $fields
   *   This is the output of the function CRM_Price_BAO_PriceSet::getSetDetail($priceSetID, FALSE, FALSE);
   *   And, it would make sense to introduce caching into that function and call it from here rather than
   *   require the $fields array which is passed from pillar to post around the form in order to pass it in here.
   * @param array $params
   *   Params reflecting form input e.g with fields 'price_5' => 7, 'price_8' => array(7, 8)
   * @param $lineItem
   *   Line item array to be altered.
   * @param int $priceSetID
   *
   * @todo $priceSetID is a pseudoparam for permit override - we should stop passing it where we
   * don't specifically need it & find a better way where we do.
   */
  private function processCartAmount($fields, &$params, &$lineItem = [], $priceSetID = NULL) {
    // using price set
    foreach ($fields as $id => $field) {
      if (empty($params["price_{$id}"]) ||
        (empty($params["price_{$id}"]) && $params["price_{$id}"] == NULL)
      ) {
        // skip if nothing was submitted for this field
        continue;
      }

      [$params, $lineItem] = self::getLine($params, $lineItem, $priceSetID, $field, $id);
    }
    $order = new CRM_Financial_BAO_Order();
    $order->setLineItems((array) $lineItem);
    $params['amount_level'] = $order->getAmountLevel();
    $params['amount'] = $order->getTotalAmount();
    $params['tax_amount'] = $order->getTotalTaxAmount();
  }

  /**
   * Set default values.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $this->loadCart();

    $defaults = [];
    foreach ($this->cart->get_main_event_participants() as $participant) {
      $form = $participant->get_form();
      if (empty($participant->email)
        && ($participant->get_participant_index() == 1)
        && ($this->getContactID() != 0)
      ) {
        $defaults = [];
        $params = ['id' => $this->getContactID()];
        $contact = $this->retrieveContact($params, $defaults);
        $participant->contact_id = $this->getContactID();
        $participant->save();
        $participant->email = self::primary_email_from_contact($contact);
      }
      elseif ($this->getContactID() == 0
        && $participant->contact_id == $this->getContactID()
      ) {
        $participant->email = NULL;
        $participant->contact_id = self::find_or_create_contact();
      }
      $defaults += $form->setDefaultValues();
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
                if ($field['html_type'] === 'Checkbox') {
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
    if (!array_key_exists('event', $this->_submitValues)) {
      return;
    }
    // XXX de facto primary key
    $email_to_contact_id = [];
    foreach ($this->_submitValues['event'] as $event_id => $participants) {
      foreach ($participants['participant'] as $participant_id => $fields) {
        if (array_key_exists($fields['email'], $email_to_contact_id)) {
          $contact_id = $email_to_contact_id[$fields['email']];
        }
        else {
          $contact_id = self::find_or_create_contact($fields);
          $email_to_contact_id[$fields['email']] = $contact_id;
        }

        $participant = $this->cart->get_event_in_cart_by_event_id($event_id)->get_participant_by_id($participant_id);
        if ($participant->contact_id && $contact_id != $participant->contact_id) {
          $defaults = [];
          $params = ['id' => $participant->contact_id];
          $temporary_contact = $this->retrieveContact($params, $defaults);

          foreach ($this->cart->get_subparticipants($participant) as $subparticipant) {
            $subparticipant->contact_id = $contact_id;
            $subparticipant->save();
          }

          $participant->contact_id = $contact_id;
          $participant->save();

          if ($temporary_contact->is_deleted) {
            // ARGH a permissions check prevents us from using skipUndelete,
            // so we potentially leave records pointing to this contact for now
            // CRM_Contact_BAO_Contact::deleteContact($temporary_contact->id);
            $temporary_contact->delete();
          }
        }

        //TODO security check that participant ids are already in this cart
        $participant_params = [
          'id' => $participant_id,
          'event_id' => $event_id,
          'contact_id' => $contact_id,
          //'registered_by_id' => $this->cart->user_id,
          'email' => $fields['email'],
        ];
        $participant = new CRM_Event_Cart_BAO_MerParticipant($participant_params, $this->cart->id);
        $participant->save();
        EventCartParticipant::save(FALSE)
          ->addRecord(['cart_id' => $this->cart->id, 'participant_id' => $participant->id])
          ->setMatch(['cart_id', 'participant_id'])
          ->execute();
        $this->cart->add_participant_to_cart($participant);

        if (array_key_exists('field', $this->_submitValues) && array_key_exists($participant_id, $this->_submitValues['field'])) {
          $custom_fields = array_merge($participant->get_form()->get_participant_custom_data_fields());

          CRM_Contact_BAO_Contact::createProfileContact($this->_submitValues['field'][$participant_id], $custom_fields, $contact_id);

          CRM_Core_BAO_CustomValueTable::postProcess($this->_submitValues['field'][$participant_id],
            'civicrm_participant',
            $participant_id,
           'Participant'
          );
        }
      }
    }
    $this->cart->save();
  }

}
