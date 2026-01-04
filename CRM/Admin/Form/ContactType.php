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

/**
 * This class generates form components for ContactSub Type.
 */
class CRM_Admin_Form_ContactType extends CRM_Admin_Form {

  /**
   * @var int|null
   */
  protected $parentId;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'ContactType';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'label', ts('Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_ContactType', 'label'),
      TRUE
    );
    $contactType = $this->add('select', 'parent_id', ts('Basic Contact Type'),
      CRM_Contact_BAO_ContactType::basicTypePairs(FALSE, 'id')
    );
    $enabled = $this->add('advcheckbox', 'is_active', ts('Enabled'));
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $contactType->freeze();
      // We'll display actual "name" for built-in types (for reference) when editing their label / image_URL
      $contactTypeName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $this->_id, 'name');
      $this->assign('contactTypeName', $contactTypeName);

      $this->parentId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $this->_id, 'parent_id');
      // Freeze Enabled field for built-in contact types (parent_id is NULL for these)
      // dev/core#4470 except Household which can be disabled
      if (is_null($this->parentId) && $contactTypeName != 'Household') {
        $enabled->freeze();
      }
    }
    // TODO: Remove when dropping image_URL column
    if ($this->_id) {
      $imageUrl = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $this->_id, 'image_URL');
      if ($imageUrl) {
        $this->addElement('text', 'image_URL', ts('Image URL'));
      }
    }
    $this->assign('hasImageUrl', !empty($imageUrl));
    $this->add('text', 'icon', ts('Icon'), ['class' => 'crm-icon-picker', 'title' => ts('Choose Icon'), 'allowClear' => TRUE]);
    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_ContactType', 'description')
    );

    $this->assign('cid', $this->_id);
    $this->addFormRule(['CRM_Admin_Form_ContactType', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {

    $errors = [];

    if ($self->_id) {
      $contactName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $self->_id, 'name');
    }
    else {
      $contactName = ucfirst(CRM_Utils_String::munge($fields['label']));
    }

    if (!CRM_Core_DAO::objectExists($contactName, 'CRM_Contact_DAO_ContactType', $self->_id)) {
      $errors['label'] = ts('This contact type name already exists in database. Contact type names must be unique.');
    }

    $reservedKeyWords = CRM_Core_SelectValues::customGroupExtends();
    //restrict "name" from being a reserved keyword when a new contact subtype is created
    if (!$self->_id && array_key_exists($contactName, $reservedKeyWords)) {
      $errors['label'] = ts('Contact type names should not use reserved keywords.');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Contact_BAO_ContactType::deleteRecord(['id' => $this->_id]);
        CRM_Core_Session::setStatus(ts('Selected contact type has been deleted.'), ts('Record Deleted'), 'success');
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Session::setStatus(ts("Selected contact type can not be deleted. Make sure contact type doesn't have any associated custom data or group."), ts('Sorry'), 'error');
      }
      return;
    }

    $params = $this->exportValues();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    // If icon is set, it overrides image_URL
    if (!empty($params['icon'])) {
      $params['image_URL'] = '';
    }

    $contactType = CRM_Contact_BAO_ContactType::writeRecord($params);
    CRM_Core_Session::setStatus(ts("The Contact Type '%1' has been saved.",
      [1 => $contactType->label]
    ), ts('Saved'), 'success');
  }

}
