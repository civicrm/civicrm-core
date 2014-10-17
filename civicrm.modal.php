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

// define title
$title = __( 'Please select a CiviCRM front-end page type.', 'civicrm' );
      
?>

<div id="civicrm_frontend_pages" style="display:none;">
  <div class="wrap">
    <div>
    
      <div style="padding:15px 15px 0 15px;">
        <h3 style="color:#5A5A5A!important; font-family:Georgia,Times New Roman,Times,serif!important; font-size:1.8em!important; font-weight:normal!important;"><?php echo $title; ?></h3>
        <span><?php echo $title; ?></span>
      </div>
      
      <div style="padding:15px 15px 0 15px;">
      
        <select id="add_civicomponent_id">
          <option value=""><?php _e( 'Select a frontend element.', 'civicrm' ); ?></option>
          <option value="contribution"><?php _e( 'Contribution Page', 'civicrm' ); ?></option>
          <option value="event"><?php _e( 'Event Page', 'civicrm' ); ?></option>
          <option value="profile"><?php _e( 'Profile', 'civicrm' ); ?></option>
          <option value="user-dashboard"><?php _e( 'User Dashboard', 'civicrm' ); ?></option>
          <option value="petition"><?php _e( 'Petition', 'civicrm' ); ?></option>
        </select>
        
        <span id="contribution-section" style="display:none;">
          <select id="add_contributepage_id">
          <?php
          $contributionPages = $this->get_contribution_pages();
          foreach ($contributionPages as $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
          <?php
          }
          ?>
          </select>
        </span>
        
        <span id="event-section" style="display:none;">
          <select id="add_eventpage_id">
            <?php
            $eventPages = $this->get_event();
            foreach ($eventPages as $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
            <?php
            }
            ?>
          </select>
        </span>
        <br>
        
        <span id="action-section-event" style="display:none;">
          <div style="padding:15px 15px 0 15px;">
            <input type="radio" name="event_action" value="info" checked="checked" /> <?php _e( 'Event Info Page', 'civicrm' ); ?>
            <input type="radio" name="event_action" value="register" /> <?php _e( 'Event Registration Page', 'civicrm' ); ?>
          </div>
        </span>
        <br/>
        
        <span id="component-section" style="display:none;">
          <div style="padding:15px 15px 0 15px;">
            <input type="radio" name="component_mode" value="live" checked="checked"/> <?php _e( 'Live Page', 'civicrm' ); ?>
            <input type="radio" name="component_mode" value="test" /> <?php _e( 'Test Drive', 'civicrm' ); ?>
          </div>
        </span>
        <br/>
        
        <span id="profile-section" style="display:none;">
          <select id="add_profilepage_id">
          <?php
          $profilePages = $this->get_profile_page();
          foreach ($profilePages as $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
            <?php
          }
          ?>
          </select>
        </span>
        <br/>
        
        <span id="profile-mode-section" style="display:none;">
          <div style="padding:15px 15px 0 15px;">
            <input type="radio" name="profile_mode" value="create" checked="checked"/> <?php _e( 'Create', 'civicrm' ); ?>
            <input type="radio" name="profile_mode" value="edit" /> <?php _e( 'Edit', 'civicrm' ); ?>
            <input type="radio" name="profile_mode" value="edit" /> <?php _e( 'View', 'civicrm' ); ?>
            <input type="radio" name="profile_mode" value="search" /> <?php _e( 'Search/Public Directory', 'civicrm' ); ?>
          </div>
        </span>
        
        <span id="petition-section" style="display:none;">
          <select id="add_petition_id">
          <?php
          $petitionPages = $this->get_petition();
          foreach ($petitionPages as $key => $value) { ?>
            <option value="<?php echo absint($key) ?>"><?php echo esc_html($value) ?></option>
            <?php
          }
          ?>
          </select>
        </span>

        <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">
        	<?php _e( "Can't find your form? Make sure it is active.", 'civicrm' ); ?>
        </div>
        
        <div style="padding:8px 0 0 0; line-height: 1.6;">
          <?php _e( 'If you only insert one shortcode, you can choose to override all page content with the content of the shortcode.', 'civicrm' ); ?><br/>
          <input type="radio" name="hijack-page" value="1" checked="checked"/> <?php _e( 'Override page content', 'civicrm' ); ?>
          <input type="radio" name="hijack-page" value="0" /> <?php _e( "Don't override", 'civicrm' ); ?>
        </div>
      
      </div>
    
      <div style="padding:15px;">
        <input type="button" class="button-primary" value="Insert Form" id="crm-wp-insert-shortcode"/>&nbsp;&nbsp;&nbsp;
        <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e( 'Cancel', 'civicrm' ); ?></a>
      </div>
    
    </div>
  </div><!-- /.wrap -->
</div>
