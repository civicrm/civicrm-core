<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * Field handler for events to provide simple renderer that allows linking
 * to the event page.
 *
 * @ingroup civicrm_field_handlers
 */
class civicrm_handler_field_event extends views_handler_field_markup {

  /**
   * Provide link to node option
   */
  public function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_event'] = array(
      '#title' => t('Link this field to its event page'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_event'],
    );
  }

  public function render_link($data, $values) {
    if (!empty($this->options['link_to_event']) && user_access('view event info') && $values->id) {
      return civicrm_views_href($data,
        'civicrm/event/info',
        "reset=1&id={$values->id}"
      );
    }
    else {
      return $data;
    }
  }

  public function render($values) {
    $format = is_numeric($this->format) ? $this->format : $values->{$this->aliases['format']};
    return $this->render_link(check_markup($values->{$this->field_alias}, $format, FALSE), $values);
  }

}
