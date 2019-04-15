<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for Relationship Type.
 */
class CRM_Admin_Form_RelationshipType extends CRM_Admin_Form {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *     Auto-added by setEntityFieldsMetadata unless specified here (use description => '' to hide)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (option) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   *  - required
   *  - is_freeze (field should be frozen).
   *
   * @var array
   */
  protected $entityFields = [];

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'label_a_b' => [
        'name' => 'label_a_b',
        'description' => ts("Label for the relationship from Contact A to Contact B. EXAMPLE: Contact A is 'Parent of' Contact B."),
        'required' => TRUE,
      ],
      'label_b_a' => [
        'name' => 'label_b_a',
        'description' => ts("Label for the relationship from Contact B to Contact A. EXAMPLE: Contact B is 'Child of' Contact A. You may leave this blank for relationships where the name is the same in both directions (e.g. Spouse)."),
      ],
      'description' => [
        'name' => 'description',
        'description' => '',
      ],
      'contact_types_a' => ['name' => 'contact_types_a', 'not-auto-addable' => TRUE],
      'contact_types_b' => ['name' => 'contact_types_b', 'not-auto-addable' => TRUE],
      'is_active' => ['name' => 'is_active'],
    ];

    self::setEntityFieldsMetadata();
  }

  /**
   * Deletion message to be assigned to the form.
   *
   * @var string
   */
  protected $deleteMessage;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'RelationshipType';
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = ts('WARNING: Deleting this option will result in the loss of all Relationship records of this type.') . ts('This may mean the loss of a substantial amount of data, and the action cannot be undone.') . ts('Do you want to continue?');
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $isReserved = ($this->_id && CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $this->_id, 'is_reserved'));
    $this->entityFields['is_active']['is_freeze'] = $isReserved;

    self::buildQuickEntityForm();
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->addRule('label_a_b', ts('Label already exists in Database.'),
      'objectExists', ['CRM_Contact_DAO_RelationshipType', $this->_id, 'label_a_b']
    );
    $this->addRule('label_b_a', ts('Label already exists in Database.'),
      'objectExists', ['CRM_Contact_DAO_RelationshipType', $this->_id, 'label_b_a']
    );

    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements(FALSE, TRUE, '__');
    foreach (['contact_types_a' => ts('Contact Type A'), 'contact_types_b' => ts('Contact Type B')] as $name => $label) {
      $element = $this->add('select', $name, $label . ' ',
        [
          '' => ts('All Contacts'),
        ] + $contactTypes
      );
      if ($isReserved) {
        $element->freeze();
      }
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }

  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    if ($this->_action != CRM_Core_Action::DELETE &&
      isset($this->_id)
    ) {
      $defaults = $params = [];
      $params = ['id' => $this->_id];
      $baoName = $this->_BAOName;
      $baoName::retrieve($params, $defaults);
      $defaults['contact_types_a'] = CRM_Utils_Array::value('contact_type_a', $defaults);
      if (!empty($defaults['contact_sub_type_a'])) {
        $defaults['contact_types_a'] .= '__' . $defaults['contact_sub_type_a'];
      }

      $defaults['contact_types_b'] = CRM_Utils_Array::value('contact_type_b', $defaults);
      if (!empty($defaults['contact_sub_type_b'])) {
        $defaults['contact_types_b'] .= '__' . $defaults['contact_sub_type_b'];
      }
      return $defaults;
    }
    else {
      return parent::setDefaultValues();
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contact_BAO_RelationshipType::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Relationship type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      // store the submitted values in an array
      $params = $this->exportValues();
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $params['id'] = $this->_id;
      }

      $cTypeA = CRM_Utils_System::explode('__',
        $params['contact_types_a'],
        2
      );
      $cTypeB = CRM_Utils_System::explode('__',
        $params['contact_types_b'],
        2
      );

      $params['contact_type_a'] = $cTypeA[0];
      $params['contact_type_b'] = $cTypeB[0];

      $params['contact_sub_type_a'] = $cTypeA[1] ? $cTypeA[1] : 'null';
      $params['contact_sub_type_b'] = $cTypeB[1] ? $cTypeB[1] : 'null';

      if (!strlen(trim(CRM_Utils_Array::value('label_b_a', $params)))) {
        $params['label_b_a'] = CRM_Utils_Array::value('label_a_b', $params);
      }

      if (empty($params['id'])) {
        // Set name on created but don't update on update as the machine name is not exposed.
        $params['name_b_a'] = CRM_Utils_String::munge($params['label_b_a']);
        $params['name_a_b'] = CRM_Utils_String::munge($params['label_a_b']);
      }

      $result = civicrm_api3('RelationshipType', 'create', $params);

      $this->ajaxResponse['relationshipType'] = $result['values'];

      CRM_Core_Session::setStatus(ts('The Relationship Type has been saved.'), ts('Saved'), 'success');
    }
  }

}
