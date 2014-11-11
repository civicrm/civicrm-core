<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

?>

<div id="civicrm_frontend_pages" style="display: none;">
  <div class="wrap">
    <div>

      <div class="civicrm-modal-header">
        <h3><?php echo $title; ?></h3>
      </div><!-- /.civicrm-modal-header -->

      <div class="civicrm-modal-content">

        <div class="civicrm-modal-hint">
        	<span><?php _e( "Can't find your form? Make sure it is active.", 'civicrm' ); ?></span>
        </div>

        <span class="civicrm-modal-selector">
          <select id="add_civicomponent_id">
            <option value="">&mdash; <?php _e( 'Select Page Type', 'civicrm' ); ?> &mdash;</option>
            <?php if ( !empty( $contribution_pages ) ) { ?>
            <option value="contribution"><?php _e( 'Contribution Page', 'civicrm' ); ?></option>
            <?php } ?>
            <?php if ( !empty( $event_pages ) ) { ?>
            <option value="event"><?php _e( 'Event Page', 'civicrm' ); ?></option>
            <?php } ?>
            <?php if ( !empty( $profile_pages ) ) { ?>
            <option value="profile"><?php _e( 'Profile', 'civicrm' ); ?></option>
            <?php } ?>
            <option value="user-dashboard"><?php _e( 'User Dashboard', 'civicrm' ); ?></option>
            <?php if ( !empty( $petition_pages ) ) { ?>
            <option value="petition"><?php _e( 'Petition', 'civicrm' ); ?></option>
            <?php } ?>
          </select>
        </span>

        <span id="contribution-section" style="display: none;">
          <?php if ( !empty( $contribution_pages ) ) { ?>
          <select id="add_contributepage_id">
          <?php foreach ($contribution_pages AS $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
          <?php } ?>
          </select>
          <?php } ?>
        </span>

        <span id="event-section" style="display: none;">
          <?php if ( !empty( $event_pages ) ) { ?>
          <select id="add_eventpage_id">
          <?php foreach ($event_pages AS $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
          <?php } ?>
          </select>
          <?php } ?>
          </select>
        </span>

        <span id="profile-section" style="display: none;">
          <?php if ( !empty( $profile_pages ) ) { ?>
          <select id="add_profilepage_id">
          <?php foreach ($profile_pages AS $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
          <?php } ?>
          </select>
          <?php } ?>
          </select>
        </span>

        <span id="petition-section" style="display: none;">
          <?php if ( !empty( $petition_pages ) ) { ?>
          <select id="add_petition_id">
          <?php foreach ($petition_pages AS $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
          <?php } ?>
          </select>
          <?php } ?>
        </span>

        <div id="action-section-event" style="display: none;">
          <div class="civicrm-modal-action-section-event">
            <input type="radio" name="event_action" value="info" checked="checked" id="civicrm-modal-event-info" />
            <label for="civicrm-modal-event-info"><?php _e( 'Event Info Page', 'civicrm' ); ?></label>
            <input type="radio" name="event_action" value="register" id="civicrm-modal-event-register" />
            <label for="civicrm-modal-event-register"><?php _e( 'Event Registration Page', 'civicrm' ); ?></label>
          </div>
        </div>

        <div id="component-section" style="display: none;">
          <div class="civicrm-modal-component-section">
            <input type="radio" name="component_mode" value="live" checked="checked" id="civicrm-modal-live" />
            <label for="civicrm-modal-live"><?php _e( 'Live Page', 'civicrm' ); ?></label>
            <input type="radio" name="component_mode" value="test" id="civicrm-modal-test" />
            <label for="civicrm-modal-test"><?php _e( 'Test Drive', 'civicrm' ); ?></label>
          </div>
        </div>

        <div id="profile-mode-section" style="display: none;">
          <div class="civicrm-modal-profile-mode-section">
            <input type="radio" name="profile_mode" value="create" checked="checked" id="civicrm-modal-create" />
            <label for="civicrm-modal-create"><?php _e( 'Create', 'civicrm' ); ?></label>
            <input type="radio" name="profile_mode" value="edit" id="civicrm-modal-edit" />
            <label for="civicrm-modal-edit"><?php _e( 'Edit', 'civicrm' ); ?></label>
            <input type="radio" name="profile_mode" value="view" id="civicrm-modal-view" />
            <label for="civicrm-modal-view"><?php _e( 'View', 'civicrm' ); ?></label>
            <input type="radio" name="profile_mode" value="search" id="civicrm-modal-search" />
            <label for="civicrm-modal-search"><?php _e( 'Search/Public Directory', 'civicrm' ); ?></label>
          </div>
        </div>

        <div class="hijack" style="display: none;">
          <p><?php _e( 'If you only insert one shortcode, you can choose to override all page content with the content of the shortcode.', 'civicrm' ); ?></p>
          <input type="radio" name="hijack-page" value="1" id="civicrm-modal-override" />
          <label for="civicrm-modal-override"><?php _e( 'Override page content', 'civicrm' ); ?></label>
          <input type="radio" name="hijack-page" value="0" checked="checked" id="civicrm-modal-leave" />
          <label for="civicrm-modal-leave"><?php _e( "Don't override", 'civicrm' ); ?></label>
        </div>

      </div><!-- /.civicrm-modal-content -->

      <div class="civicrm-modal-footer">
        <input type="button" class="button-primary" value="Insert Form" id="crm-wp-insert-shortcode"/>&nbsp;&nbsp;&nbsp;
        <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e( 'Cancel', 'civicrm' ); ?></a>
      </div><!-- /.civicrm-modal-footer -->

    </div>
  </div><!-- /.wrap -->
</div>
