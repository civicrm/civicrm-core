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
 *
 * civicrm_handler_field_link_pcp.inc
 *
 * Creates Personal Campaign Page-relate links.
 * At this time the View and Contribute Pages are supported.
 *
 */

/**
 * Field handler to provide simple renderer that allows linking to various pages involving the PCP.
 */
class civicrm_handler_field_link_pcp extends views_handler_field {

  /**
   * Constructor to provide additional field to add.
   */
  function construct() {
    parent::construct();
    $this->additional_fields['id'] = 'id';
    $this->additional_fields['page_id'] = 'page_id';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['link_to_civicrm_pcp'] = array('default' => 'view');
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['link_to_civicrm_pcp'] = array(
      '#type' => 'select',
      '#title' => t('Choose which kind of link to create'),
      '#options' => array(
        'view' => t('Link to View Personal Campaign Page'),
        'contribute' => t('Link to Make Contribution via Personal Campaign Page'),
      ),
      '#default_value' => $this->options['link_to_civicrm_pcp'],
    );
  }

  function render_link($data, $values) {

    $link_choice = $this->options['link_to_civicrm_pcp'];

    switch ($link_choice) {

      /*
     * LINKING TO PCP VIEW PAGE - this provides a URL for users to use however they like in their view
     * (e.g., exclude this field from view and output the Title of the PCP as a link, using Views'
     * replacement patterns to supply the link path
     */

      case 'view':
        // why bother displaying PCP pages if the user can't follow through with a donation?
        if (user_access('make online contributions')) {
          return civicrm_views_url(
            'civicrm/pcp/info',
            "reset=1&id={$values->id}",
            // results in an absolute URL
            TRUE
          );
        }

        /*
     * LINKING TO PCP CONTRIBUTION PAGE - this provides a URL for users to use however they like in their view
     * (e.g., exclude this field from view and output the Text For Donate Link of the PCP as a link, using Views'
     * replacement patterns to supply the link path
     */
      case 'contribute':
        if (user_access('make online contributions')) {
          return civicrm_views_url(
            'civicrm/contribute/transact',
            "id={$values->page_id}&pcpId={$values->id}&reset=1",
            // results in an absolute URL
            TRUE
          );
        }
    }
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}

