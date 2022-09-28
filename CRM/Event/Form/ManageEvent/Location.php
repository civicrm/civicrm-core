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

use Civi\Api4\Event;
use Civi\Api4\LocBlock;
use Civi\Api4\Email;
use Civi\Api4\Phone;
use Civi\Api4\Address;

/**
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing Event Location
 * civicrm_event_page.
 */
class CRM_Event_Form_ManageEvent_Location extends CRM_Event_Form_ManageEvent {

  /**
   * @var \Civi\Api4\Generic\Result
   */
  protected $locationBlock;

  /**
   * How many locationBlocks should we display?
   *
   * @var int
   * @const
   */
  const LOCATION_BLOCKS = 1;

  /**
   * The variable, for storing the location array
   *
   * @var array
   */
  protected $_locationIds = [];

  /**
   * The variable, for storing location block id with event
   *
   * @var int
   */
  protected $_oldLocBlockId = 0;

  /**
   * Get the db values for this form.
   * @var array
   */
  public $_values = [];

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();
    $this->setSelectedChild('location');

    $this->_values = $this->get('values');
    if ($this->_id && empty($this->_values)) {
      //get location values.
      $params = [
        'entity_id' => $this->_id,
        'entity_table' => 'civicrm_event',
      ];
      $this->_values = CRM_Core_BAO_Location::getValues($params);

      //get event values.
      $params = ['id' => $this->_id];
      CRM_Event_BAO_Event::retrieve($params, $this->_values);
      $this->set('values', $this->_values);
    }

    //location blocks.
    CRM_Contact_Form_Location::preProcess($this);
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode the default values are retrieved from the database.
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    if (!empty($defaults['loc_block_id'])) {
      $defaults['loc_event_id'] = $defaults['loc_block_id'];
      $countLocUsed = CRM_Event_BAO_Event::countEventsUsingLocBlockId($defaults['loc_block_id']);
      $this->assign('locUsed', $countLocUsed);
    }

    $config = CRM_Core_Config::singleton();
    if (!isset($defaults['address'][1]['country_id'])) {
      $defaults['address'][1]['country_id'] = $config->defaultContactCountry;
    }

    if (!isset($defaults['address'][1]['state_province_id'])) {
      $defaults['address'][1]['state_province_id'] = $config->defaultContactStateProvince;
    }

    $defaults['location_option'] = $this->_oldLocBlockId ? 2 : 1;

    return $defaults;
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Event_Form_ManageEvent_Location', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    // check for state/country mapping
    $errors = CRM_Contact_Form_Edit_Address::formRule($fields);

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to build location block.
   */
  public function buildQuickForm() {
    CRM_Contact_Form_Edit_Address::buildQuickForm($this, 1);
    CRM_Contact_Form_Edit_Email::buildQuickForm($this, 1);
    CRM_Contact_Form_Edit_Email::buildQuickForm($this, 2);
    CRM_Contact_Form_Edit_Phone::buildQuickForm($this, 1);
    CRM_Contact_Form_Edit_Phone::buildQuickForm($this, 2);

    $this->applyFilter('__ALL__', 'trim');

    //fix for CRM-1971
    $this->assign('action', $this->_action);

    if ($this->_id) {
      $this->locationBlock = Event::get()
        ->addWhere('id', '=', $this->_id)
        ->setSelect(['loc_block_id.*', 'loc_block_id'])
        ->execute()->first();
      $this->_oldLocBlockId = $this->locationBlock['loc_block_id'];
    }

    // get the list of location blocks being used by other events

    $locationEvents = CRM_Event_BAO_Event::getLocationEvents();
    // remove duplicates and make sure that the duplicate entry with key as
    // loc_block_id of this event (this->_id) is preserved
    if (!empty($locationEvents[$this->_oldLocBlockId])) {
      $possibleDuplicate = $locationEvents[$this->_oldLocBlockId];
      $locationEvents = array_flip(array_unique($locationEvents));
      if (!empty($locationEvents[$possibleDuplicate])) {
        $locationEvents[$possibleDuplicate] = $this->_oldLocBlockId;
      }
      $locationEvents = array_flip($locationEvents);
    }
    else {
      $locationEvents = array_unique($locationEvents);
    }

    if (!empty($locationEvents)) {
      $this->assign('locEvents', TRUE);
      $optionTypes = [
        '1' => ts('Create new location'),
        '2' => ts('Use existing location'),
      ];

      $this->addRadio('location_option', ts("Choose Location"), $optionTypes);

      if (!isset($locationEvents[$this->_oldLocBlockId]) || (!$this->_oldLocBlockId)) {
        $locationEvents = ['' => ts('- select -')] + $locationEvents;
      }
      $this->add('select', 'loc_event_id', ts('Use Location'), $locationEvents, FALSE, ['class' => 'crm-select2']);
    }
    $this->addElement('advcheckbox', 'is_show_location', ts('Show Location?'));
    parent::buildQuickForm();
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->exportValues();
    $deleteOldBlock = FALSE;

    // If 'Use existing location' is selected.
    if (CRM_Utils_Array::value('location_option', $params) == 2) {

      /*
       * If there is an existing LocBlock and the selected LocBlock is different,
       * flag the existing LocBlock for deletion.
       */
      if ($this->_oldLocBlockId && !empty($params['loc_event_id']) &&
        ($params['loc_event_id'] != $this->_oldLocBlockId)
      ) {
        $deleteOldBlock = TRUE;
      }

      /*
       * Always update the loc_block_id in this Event so that LocBlock update
       * affects the selected LocBlock and not the previous one - whether or not
       * there is a previous LocBlock.
       */
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $this->_id,
        'loc_block_id', $params['loc_event_id']
      );

    }

    /*
     * If there is an existing LocBlock and 'Create new location' is selected,
     * set the loc_block_id for this Event to null so that an update results in
     * creating a new LocBlock.
     */
    if ($this->_oldLocBlockId && (CRM_Utils_Array::value('location_option', $params) == 1)) {
      $deleteOldBlock = TRUE;
      CRM_Core_DAO::setFieldValue('CRM_Event_DAO_Event', $this->_id,
        'loc_block_id', 'null'
      );
    }

    /*
     * If there is a previous LocBlock and we have determined that it should be
     * deleted, go ahead and do so now. The method that is called will only delete
     * the LocBlock if it is not being used by another Event.
     */
    if ($this->_oldLocBlockId && $deleteOldBlock) {
      CRM_Event_BAO_Event::deleteEventLocBlock($this->_oldLocBlockId, $this->_id);
    }

    // Assume a new LocBlock is needed.
    $isUpdateToExistingLocationBlock = FALSE;

    /*
     * If there is a previous LocBlock and it was not deleted, check if the new
     * LocBlock ID matches the previous one. If so, then it needs to be updated.
     */
    if (!empty($this->locationBlock['loc_block_id']) && !$deleteOldBlock) {
      if (!empty($params['loc_event_id']) && (int) $params['loc_event_id'] === $this->locationBlock['loc_block_id']) {
        $isUpdateToExistingLocationBlock = TRUE;
      }
    }

    /*
     * If 'Use existing location' is selected and there isn't a previous LocBlock
     * but a LocBlock has been selected, then that LocBlock should be updated.
     * In order to do so, the IDs of the Address, Phone and Email "Blocks" have
     * to be retrieved and added in to the elements in the $params array.
     */
    if (CRM_Utils_Array::value('location_option', $params) == 2) {
      if (empty($this->locationBlock['loc_block_id']) && !empty($params['loc_event_id'])) {
        $isUpdateToExistingLocationBlock = TRUE;
        $existingLocBlock = LocBlock::get()
          ->addWhere('id', '=', (int) $params['loc_event_id'])
          ->setCheckPermissions(FALSE)
          ->execute()->first();
      }
    }

    /*
     * It should be impossible for there to be no default location type.
     * Consider removing this handling.
     */
    $defaultLocationTypeID = CRM_Core_BAO_LocationType::getDefault()->id ?? 1;

    foreach ([
      'address' => $params['address'],
      'phone' => $params['phone'],
      'email' => $params['email'],
    ] as $block => $locationEntities) {

      $params[$block][1]['is_primary'] = 1;
      foreach ($locationEntities as $index => $locationEntity) {

        $fieldKey = (int) $index === 1 ? '_id' : '_2_id';

        // Assume there's no Block ID.
        $blockId = FALSE;

        // Check the existing LocBlock for an ID.
        if (!empty($this->locationBlock['loc_block_id.' . $block . $fieldKey])) {
          $blockId = $this->locationBlock['loc_block_id.' . $block . $fieldKey];
        }
        else {
          // Check the queried LocBlock for an ID.
          if (!empty($existingLocBlock[$block . $fieldKey])) {
            $blockId = $existingLocBlock[$block . $fieldKey];
          }
        }

        /*
         * Unsetting the array element excludes the Block from being updated and
         * removes it from the LocBlock. However, the intention of clearing a Block
         * is presumably to delete it.
         */
        if (!$this->isLocationHasData($block, $locationEntity)) {
          unset($params[$block][$index]);
          if (!empty($blockId)) {
            // The Block can be deleted here.
          }
          continue;
        }

        $params[$block][$index]['location_type_id'] = $defaultLocationTypeID;

        // Assign the existing Block ID if an update is needed.
        if ($isUpdateToExistingLocationBlock && !empty($blockId)) {
          $params[$block][$index]['id'] = $blockId;
        }
      }

    }

    // Update the Blocks.
    $addresses = empty($params['address']) ? [] : Address::save(FALSE)->setRecords($params['address'])->execute();
    $emails = empty($params['email']) ? [] : Email::save(FALSE)->setRecords($params['email'])->execute();
    $phones = empty($params['phone']) ? [] : Phone::save(FALSE)->setRecords($params['phone'])->execute();

    // Build the LocBlock record.
    $record = [
      'email_id' => $emails[0]['id'] ?? NULL,
      'address_id' => $addresses[0]['id'] ?? NULL,
      'phone_id' => $phones[0]['id'] ?? NULL,
      'email_2_id' => $emails[1]['id'] ?? NULL,
      'address_2_id' => $addresses[1]['id'] ?? NULL,
      'phone_2_id' => $phones[1]['id'] ?? NULL,
    ];

    // Maybe trigger LocBlock update.
    if ($isUpdateToExistingLocationBlock) {
      $record['id'] = (int) $params['loc_event_id'];
    }

    // Update the LocBlock.
    $params['loc_block_id'] = LocBlock::save(FALSE)->setRecords([$record])->execute()->first()['id'];

    // Finally update Event params.
    $params['id'] = $this->_id;
    CRM_Event_BAO_Event::add($params);

    // Update tab "disabled" CSS class.
    $this->ajaxResponse['tabValid'] = TRUE;
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Event Location');
  }

  /**
   * Is there some data to save for the given entity
   *
   * @param string $block
   * @param array $locationEntity
   *
   * @return bool
   */
  protected function isLocationHasData(string $block, array $locationEntity): bool {
    if ($block === 'email') {
      return !empty($locationEntity['email']);
    }
    if ($block === 'phone') {
      return !empty($locationEntity['phone']);
    }
    foreach ($locationEntity as $value) {
      if (!empty($value)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
