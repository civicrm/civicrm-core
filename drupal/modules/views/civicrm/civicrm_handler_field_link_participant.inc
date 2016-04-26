<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

/*
 * Copyright (C) 2009 James Rakich
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 *
 * civicrm_handler_field_link_participant.inc
 * Completely customizable text that can be linked to numerous places related to the participant.
 * View, Edit and Delete Pages, as well as a custom Drupal Link with the participant ID appended,
 * which is great for linking to other views using the activity base table.
 *
 */

/**
 * Field handler to provide simple renderer that allows linking to various pages involving the participant.
 */
class civicrm_handler_field_link_participant extends views_handler_field {

  /**
   * Constructor to provide additional field to add.
   */
  function construct() {
    parent::construct();
    $this->additional_fields['contact_id'] = array('table' => 'civicrm_participant', 'field' => 'contact_id');
    $this->additional_fields['id'] = 'id';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_civicrm_participant'] = array('default' => 'view');
    $options['custom_link'] = array('default' => '');
    $options['link_text'] = array('default' => '');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_civicrm_participant'] = array(
      '#type' => 'select',
      '#title' => t('Choose where to link this field'),
      '#options' => array(
        'view' => t('Link to View Participant'),
        'edit' => t('Link to Edit Participant'),
        'delete' => t('Link to Delete Participant'),
        'custom' => t('Link to a Custom Node'),
      ),
      '#default_value' => $this->options['link_to_civicrm_participant'],
      '#required' => TRUE,
    );

    $form['custom_link'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom Link to a Drupal Node'),
      '#description' => t('Link to a Drupal Node or View, from the base path. The Participant ID will be appended to the end of the link.'),
      '#default_value' => isset($this->options['custom_link']) ? $this->options['custom_link'] : '',
      '#dependency' => array('edit-options-link-to-civicrm-participant' => array('custom')),
    );

    $form['link_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Link Text'),
      '#description' => t('The text that will display in the field with the link.'),
      '#default_value' => isset($this->options['link_text']) ? $this->options['link_text'] : '',
      '#required' => TRUE,
    );
  }

  function render_link($data, $values) {

    $link_text = $this->options['link_text'];
    $link_choice = $this->options['link_to_civicrm_participant'];

    if ($link_choice == 'custom') {
      $custom_link = $this->options['custom_link'];
    }


    switch ($link_choice) {

      // LINKING TO PARTICIPANT VIEW PAGE

      case 'view':
        if (user_access('edit event participants') && $link_text !== NULL && $link_text !== '') {
          return civicrm_views_href($link_text,
            'civicrm/contact/view/participant',
            "reset=1&id={$values->id}&cid={$values->civicrm_participant_contact_id}&action=view&context=search&selectedChild=event"
          );
        }

        // LINKING TO PARTICIPANT EDIT PAGE
      case 'edit':
        if (user_access('edit event participants') && $link_text !== NULL && $link_text !== '') {
          return civicrm_views_href($link_text,
            'civicrm/contact/view/participant',
            "reset=1&action=update&id={$values->id}&cid={$values->civicrm_participant_contact_id}&context=search"
          );
        }

        // LINKING TO PARTICIPANT DELETE PAGE
      case 'delete':
        if (user_access('edit event participants') && $link_text !== NULL && $link_text !== '') {
          return civicrm_views_href($link_text,
            'civicrm/contact/view/participant',
            "reset=1&action=delete&id={$values->id}&cid={$values->civicrm_participant_contact_id}&context=search"
          );
        }

        // CUSTOM NODE LINKAGE, GOOD FOR LINKING TO OTHER VIEWS WITH AN ARGUMENT
      case 'custom':
        if ($custom_link !== NULL && $custom_link !== '' && $link_text !== NULL && $link_text !== '') {
          return l($link_text, $custom_link . "{$values->id}");
        }

        // IF THE OTHER CASES AREN'T IN PLAY, THEN JUST PRINT THE TEXT
      default:
        return $link_text;
    }
  }

  function render($values) {
    return $this->render_link(NULL, $values);
  }
}

