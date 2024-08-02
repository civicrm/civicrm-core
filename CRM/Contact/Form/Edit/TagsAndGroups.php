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
class CRM_Contact_Form_Edit_TagsAndGroups {

  /**
   * Constant to determine which forms we are generating.
   *
   * Used by both profile and edit contact
   */
  const GROUP = 1, TAG = 2, ALL = 3;

  /**
   * Build form elements.
   *
   * @param CRM_Core_Form $form
   *   The form object that we are operating on.
   * @param int $contactId
   *   Contact id.
   * @param int $type
   *   What components are we interested in.
   * @param bool $visibility
   *   Visibility of the field.
   * @param null $isRequired
   * @param string $groupName
   *   If used for building group block.
   * @param string $tagName
   *   If used for building tag block.
   * @param string $fieldName
   *   This is used in batch profile(i.e to build multiple blocks).
   * @param string $groupElementType
   *   The html type of the element we are adding e.g. checkbox, select
   * @param bool $public
   *   Is this being used in a public form e.g. Profile.
   */
  public static function buildQuickForm(
    &$form,
    $contactId = 0,
    $type = self::ALL,
    $visibility = FALSE,
    $isRequired = NULL,
    $groupName = 'Group(s)',
    $tagName = 'Tag(s)',
    $fieldName = NULL,
    $groupElementType = 'checkbox',
    $public = FALSE
  ) {
    $form->addExpectedSmartyVariable('type');
    $form->addOptionalQuickFormElement('group');
    // NYSS 5670
    if (!$contactId && !empty($form->_contactId)) {
      CRM_Core_Error::deprecatedWarning('this is thought to be unreachable, should be passed in');
      $contactId = $form->_contactId;
    }

    $type = (int) $type;
    if ($type & self::GROUP) {

      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }
      // The optional url parameter grid is refers to Group ID.
      // If it set the group options on the page are limited to that group
      $groupID = is_numeric(CRM_Utils_Request::retrieve('grid', 'Integer', $form)) ? (int) CRM_Utils_Request::retrieve('grid', 'Integer', $form) : NULL;

      if ($groupID && $visibility) {
        $ids = [$groupID => $groupID];
      }
      else {
        if ($visibility) {
          $group = CRM_Core_PseudoConstant::allGroup();
        }
        else {
          $group = CRM_Core_PseudoConstant::group();
        }
        $ids = $group;
      }

      if ($groupID || !empty($group)) {
        $groups = CRM_Contact_BAO_Group::getGroupsHierarchy($ids, NULL, '- ', FALSE, $public);

        $attributes['skiplabel'] = TRUE;
        $elements = [];
        $groupsOptions = [];
        foreach ($groups as $key => $group) {
          $id = $group['id'];
          // make sure that this group has public visibility
          if ($visibility &&
            $group['visibility'] === 'User and User Admin Only'
          ) {
            continue;
          }

          if ($groupElementType === 'select') {
            $groupsOptions[$key] = $group;
          }
          else {
            $tagGroup[$fName][$id]['description'] = $group['description'];
            $elements[] = &$form->addElement('advcheckbox', $id, NULL, $group['text'], $attributes);
          }
        }

        if ($groupElementType === 'select' && !empty($groupsOptions)) {
          $form->add('select2', $fName, $groupName, $groupsOptions, FALSE,
            ['placeholder' => ts('- select -'), 'multiple' => TRUE, 'class' => 'twenty']
          );
          $form->assign('groupCount', count($groupsOptions));
        }

        if ($groupElementType === 'checkbox' && !empty($elements)) {
          $form->addGroup($elements, $fName, $groupName, '&nbsp;<br />');
          $form->assign('groupCount', count($elements));
          if ($isRequired) {
            $form->addRule($fName, ts('%1 is a required field.', [1 => $groupName]), 'required');
          }
        }
      }
    }
    $form->assign('groupElementType', $groupElementType ?? NULL);

    if ($type & self::TAG) {
      $tagGroup = [];
      $tags = CRM_Core_BAO_Tag::getColorTags('civicrm_contact');

      if (!empty($tags)) {
        $form->add('select2', 'tag', $tagName, $tags, $isRequired, ['class' => 'huge', 'placeholder' => ts('- select -'), 'multiple' => TRUE]);
      }

      // build tag widget
      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', $contactId, FALSE, TRUE);
    }
    $form->assign('tagGroup', $tagGroup ?? NULL);
  }

  /**
   * Set defaults for relevant form elements.
   *
   * @param int $id
   *   The contact id.
   * @param array $defaults
   *   The defaults array to store the values in.
   * @param int $type
   *   What components are we interested in.
   * @param string $fieldName
   *   This is used in batch profile(i.e to build multiple blocks).
   *
   * @param string $groupElementType
   */
  public static function setDefaults($id, &$defaults, $type = self::ALL, $fieldName = NULL, $groupElementType = 'checkbox') {
    $type = (int ) $type;
    if ($type & self::GROUP) {
      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }

      $contactGroup = CRM_Contact_BAO_GroupContact::getContactGroup($id, 'Added', NULL, FALSE, TRUE, FALSE, TRUE, NULL, TRUE);
      if ($contactGroup) {
        if ($groupElementType == 'select') {
          $defaults[$fName] = implode(',', array_column($contactGroup, 'group_id'));
        }
        else {
          foreach ($contactGroup as $group) {
            $defaults[$fName . '[' . $group['group_id'] . ']'] = 1;
          }
        }
      }
    }

    if ($type & self::TAG) {
      $defaults['tag'] = implode(',', CRM_Core_BAO_EntityTag::getTag($id, 'civicrm_contact'));
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @param CRM_Core_Form $form
   * @param array $defaults
   */
  public static function setDefaultValues(&$form, &$defaults) {
    $contactEditOptions = $form->get('contactEditOptions');

    if ($form->_action & CRM_Core_Action::ADD) {
      if (array_key_exists('TagsAndGroups', $contactEditOptions)) {
        // set group and tag defaults if any
        if ($form->_gid) {
          $defaults['group'][$form->_gid] = 1;
        }
        if ($form->_tid) {
          $defaults['tag'][$form->_tid] = 1;
        }
      }
    }
    else {
      if (array_key_exists('TagsAndGroups', $contactEditOptions)) {
        // set the group and tag ids
        $groupElementType = 'checkbox';
        if (CRM_Utils_System::getClassName($form) == 'CRM_Contact_Form_Contact') {
          $groupElementType = 'select';
        }
        self::setDefaults($form->_contactId, $defaults, self::ALL, NULL, $groupElementType);
      }
    }
  }

}
