<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 *
 */


// this file must not accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Define CiviCRM_For_WordPress_Shortcodes_Modal Class
 */
class CiviCRM_For_WordPress_Shortcodes_Modal {


  /**
   * Declare our properties
   */

  // init property to store reference to Civi
  public $civi;


  /**
   * Instance constructor
   *
   * @return object $this The object instance
   */
  function __construct() {

    // store reference to Civi object
    $this->civi = civi_wp();

    return $this;

  }


  /**
   * Register hooks to handle the presence of shortcodes in content
   *
   * @return void
   */
  public function register_hooks() {

    // bail if Civi not installed yet
    if ( ! CIVICRM_INSTALLED ) return;

    // adds the CiviCRM button to post and page edit screens
    // use priority 100 to position button to the farright
    add_action( 'media_buttons', array( $this, 'add_form_button' ), 100 );


    // add the javascript and styles to make it all happen
    add_action('load-post.php', array($this, 'add_core_resources'));
    add_action('load-post-new.php', array($this, 'add_core_resources'));
    add_action('load-page.php', array($this, 'add_core_resources'));
    add_action('load-page-new.php', array($this, 'add_core_resources'));

  }


  /**
   * Callback method for 'media_buttons' hook as set in register_hooks()
   *
   * @param string $editor_id Unique editor identifier, e.g. 'content'
   * @return void
   */
  public function add_form_button() {

    // add button to WP selected post types, if allowed
    if ( $this->post_type_has_button() ) {

      if (!$this->civi->initialize()) {
        return;
      }

      $config = CRM_Core_Config::singleton();
      $imageBtnURL = $config->resourceBase . 'i/logo16px.png';
      echo '<a href="/wp-admin/admin.php?page=CiviCRM&q=civicrm/shortcode&reset=1" class="button crm-popup medium-popup crm-shortcode-button" data-popup-type="page" style="padding-left: 4px;" title="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '" />'. __( 'CiviCRM', 'civicrm' ) .'</a>';

    }

  }


  /**
   * Callback method as set in register_hooks()
   *
   * @return void
   */
  public function add_core_resources() {
    if ($this->civi->initialize()) {
      CRM_Core_Resources::singleton()->addCoreResources();
    }
  }


  /**
   * Does a WordPress post type have the CiviCRM button on it?
   *
   * @return bool $has_button True if the post type has the button, false otherwise
   */
  public function post_type_has_button() {

    // get screen object
    $screen = get_current_screen();

    // bail if no post type (e.g. Ninja Forms)
    if ( ! isset( $screen->post_type ) ) return;

    // get post types that support the editor
    $capable_post_types = $this->get_post_types_with_editor();

    // default allowed to true on all capable post types
    $allowed = ( in_array( $screen->post_type, $capable_post_types ) ) ? true : false;

    // allow plugins to override
    $allowed = apply_filters( 'civicrm_restrict_button_appearance', $allowed, $screen );

    return $allowed;

  }


  /**
   * Get WordPress post types that support the editor
   *
   * @return array $supported_post_types Array of post types that have an editor
   */
  public function get_post_types_with_editor() {

    static $supported_post_types = array();
    if ( !empty( $supported_post_types) ) {
      return $supported_post_types;
    }

    // get only post types with an admin UI
    $args = array(
      'public' => true,
      'show_ui' => true,
    );

    // get post types
    $post_types = get_post_types($args);

    foreach ($post_types AS $post_type) {
      // filter only those which have an editor
      if (post_type_supports($post_type, 'editor')) {
        $supported_post_types[] = $post_type;
      }
    }

    return $supported_post_types;
  }

} // class CiviCRM_For_WordPress_Shortcodes_Modal ends


