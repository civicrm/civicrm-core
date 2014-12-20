<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Contact_Form_Edit_TagsAndGroups {

  /**
   * Constant to determine which forms we are generating
   *
   * Used by both profile and edit contact
   */
  CONST GROUP = 1, TAG = 2, ALL = 3;

  /**
   * This function is to build form elements
   * params object $form object of the form
   *
   * @param CRM_Core_Form $form the form object that we are operating on
   * @param int $contactId contact id
   * @param int $type what components are we interested in
   * @param boolean $visibility visibility of the field
   * @param null $isRequired
   * @param string $groupName if used for building group block
   * @param string $tagName if used for building tag block
   * @param string $fieldName this is used in batch profile(i.e to build multiple blocks)
   *
   * @param string $groupElementType
   *
   * @static
   * @access public
   */
  static function buildQuickForm(&$form,
    $contactId = 0,
    $type = self::ALL,
    $visibility = FALSE,
    $isRequired = NULL,
    $groupName = 'Group(s)',
    $tagName = 'Tag(s)',
    $fieldName = NULL,
    $groupElementType = 'checkbox'
  ) {
    if (!isset($form->_tagGroup)) {
      $form->_tagGroup = array();
    }

    // NYSS 5670
    if (!$contactId && !empty($form->_contactId)) {
      $contactId = $form->_contactId;
    }

    $type = (int) $type;
    if ($type & self::GROUP) {

      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }

      $groupID = isset($form->_grid) ? $form->_grid : NULL;
      if ($groupID && $visibility) {
        $ids = array($groupID => $groupID);
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
        $groups = CRM_Contact_BAO_Group::getGroupsHierarchy($ids);

        $attributes['skiplabel'] = TRUE;
        $elements = array();
        $groupsOptions = array();
        foreach ($groups as $id => $group) {
          // make sure that this group has public visibility
          if ($visibility &&
            $group['visibility'] == 'User and User Admin Only'
          ) {
            continue;
          }

          if ($groupElementType == 'select') {
            $groupsOptions[$id] = $group['title'];
          }
          else {
            $form->_tagGroup[$fName][$id]['description'] = $group['description'];
            $elements[] = &$form->addElement('advcheckbox', $id, NULL, $group['title'], $attributes);
          }
        }

        if ($groupElementType == 'select' && !empty($groupsOptions)) {
          $form->add('select', $fName, ts('%1', array(1 => $groupName)), $groupsOptions, FALSE,
            array('id' => $fName, 'multiple' => 'multiple', 'class' => 'crm-select2')
          );
          $form->assign('groupCount', count($groupsOptions));
        }

        if ($groupElementType == 'checkbox' && !empty($elements)) {
          $form->addGroup($elements, $fName, $groupName, '&nbsp;<br />');
          $form->assign('groupCount', count($elements));
          if ($isRequired) {
            $form->addRule($fName, ts('%1 is a required field.', array(1 => $groupName)), 'required');
          }
        }
        $form->assign('groupElementType', $groupElementType);
      }
    }

    if ($type & self::TAG) {
      // CODE FROM CRM/Tag/Form/Tag.php //
      CRM_Core_Resources::singleton()
        ->addScriptFile('civicrm', 'packages/jquery/plugins/jstree/jquery.jstree.js', 0, 'html-header', FALSE)
        ->addStyleFile('civicrm', 'packages/jquery/plugins/jstree/themes/default/style.css', 0, 'html-header');

      $fName = 'tag';
      if ($fieldName) {
        $fName = $fieldName;
      }
      $form->_tagGroup[$fName] = 1;

      // get the list of all the categories
      $tags = new CRM_Core_BAO_Tag();
      $tree = $tags->getTree('civicrm_contact', TRUE);

      $elements = array();
      self::climbtree($form, $tree, $elements);

      $form->addGroup($elements, $fName, $tagName, '<br />');
      $form->assign('tagCount', count($elements));
      $form->assign('tree', $tree);
      $form->assign('tag', $tree);
      $form->assign('entityID', $contactId);
      $form->assign('entityTable', 'civicrm_contact');

      if ($isRequired) {
        $form->addRule($fName, ts('%1 is a required field.', array(1 => $tagName)), 'required');
      }

      // build tag widget
      $parentNames = CRM_Core_BAO_Tag::getTagSet('civicrm_contact');
      CRM_Core_Form_Tag::buildQuickForm($form, $parentNames, 'civicrm_contact', $contactId, FALSE, TRUE);
    }
    $form->assign('tagGroup', $form->_tagGroup);
  }

  static function climbtree($form, $tree, &$elements) {
    foreach ($tree as $tagID => $varValue) {
      $tagAttribute = array(
      'onclick' => "return changeRowColor(\"rowidtag_$tagID\")",
      'id' => "tag_{$tagID}",
      );

      $elements[$tagID] = $form->createElement('checkbox', $tagID, '', '', $tagAttribute);

      if (array_key_exists('children', $varValue)) {
        self::climbtree($form, $varValue['children'], $elements);
      }
    }
   return $elements;
  }

  /**
   * Set defaults for relevant form elements
   *
   * @param int $id the contact id
   * @param array $defaults the defaults array to store the values in
   * @param int $type what components are we interested in
   * @param string $fieldName this is used in batch profile(i.e to build multiple blocks)
   *
   * @param string $groupElementType
   *
   * @return void
   * @access public
   * @static
   */
  static function setDefaults($id, &$defaults, $type = self::ALL, $fieldName = NULL, $groupElementType = 'checkbox') {
    $type = (int ) $type;
    if ($type & self::GROUP) {
      $fName = 'group';
      if ($fieldName) {
        $fName = $fieldName;
      }

      $contactGroup = CRM_Contact_BAO_GroupContact::getContactGroup($id, 'Added', NULL, FALSE, TRUE);
      if ($contactGroup) {
        foreach ($contactGroup as $group) {
          if ($groupElementType == 'select') {
            $defaults[$fName][] = $group['group_id'];
          }
          else {
            $defaults[$fName . '[' . $group['group_id'] . ']'] = 1;
          }
        }
      }
    }

    if ($type & self::TAG) {
      $fName = 'tag';
      if ($fieldName) {
        $fName = $fieldName;
      }

      $contactTag = CRM_Core_BAO_EntityTag::getTag($id);
      if ($contactTag) {
        foreach ($contactTag as $tag) {
          $defaults[$fName . '[' . $tag . ']'] = 1;
        }
      }
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @param CRM_Core_Form $form
   * @param $defaults
   *
   * @return void
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
