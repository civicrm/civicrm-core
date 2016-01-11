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

    // adds the HTML triggered by the button above
    add_action( 'admin_footer', array( $this, 'add_form_button_html' ) );

    // add the javascript and styles to make it all happen
    add_action( 'admin_enqueue_scripts', array( $this, 'add_form_button_js' ) );

  }


  /**
   * Callback method for 'media_buttons' hook as set in register_hooks()
   *
   * @param string $editor_id Unique editor identifier, e.g. 'content'
   * @return void
   */
  public function add_form_button( $editor_id ) {

    // add button to WP selected post types, if allowed
    if ( $this->post_type_has_button() ) {

      if (!$this->civi->initialize()) {
        return '';
      }

      $config      = CRM_Core_Config::singleton();
      $imageBtnURL = $config->resourceBase . 'i/logo16px.png';
      $out         = '<a href="#TB_inline?width=480&inlineId=civicrm_frontend_pages" class="button thickbox" id="add_civi" style="padding-left: 4px;" title="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '"><img src="' . $imageBtnURL . '" height="15" width="15" alt="' . __( 'Add CiviCRM Public Pages', 'civicrm' ) . '" />'. __( 'CiviCRM', 'civicrm' ) .'</a>';
      echo $out;

    }

  }


  /**
   * Callback method for 'admin_enqueue_scripts' hook as set in register_hooks()
   *
   * @return void
   */
  public function add_form_button_js( $hook ) {

    // bail if not on the page(s) we want
    if ( !in_array(
      $hook,
      array( 'post.php', 'page.php', 'page-new.php', 'post-new.php' )
    ) ) {
      return;
    }

    // enqueue script in footer
    $in_footer = TRUE;

    // we now benefit from browser caching, concatenation, gzip compression, etc
    wp_enqueue_script(
      'civicrm_form_button_js', // handle
      CIVICRM_PLUGIN_URL . 'assets/js/civicrm.modal.js', // src
      array( 'jquery' ), // deps
      CIVICRM_PLUGIN_VERSION,
      $in_footer
    );

    // enqueue stylesheet
    wp_enqueue_style(
      'civicrm_form_button_css', // handle
      CIVICRM_PLUGIN_URL . 'assets/css/civicrm.modal.css', // src
      FALSE, // deps
      CIVICRM_PLUGIN_VERSION,
      'all' // media
    );

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
      'public'   => true,
      'show_ui' => true,
    );

    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    // get post types
    $post_types = get_post_types($args, $output, $operator);

    // init outputs
    $output = array();
    $options = '';

    // sanity check
    if ( count($post_types) > 0 ) {
      foreach($post_types AS $post_type) {

        // filter only those which have an editor
        if ( post_type_supports($post_type, 'editor') ) {
          $supported_post_types[] = $post_type;
        }
      }
    }

    return $supported_post_types;
  }


  /**
   * Get ID and title of CiviCRM contribution pages
   *
   * @access private
   * @return array $contributionPages Array of contribution pages
   */
  private function get_contribution_pages() {
    $now = date('Ymdhis');
    $sql = "
        SELECT id, title
        FROM   civicrm_contribution_page
        WHERE  is_active = 1
        AND    (
             ( start_date IS NULL AND end_date IS NULL )
        OR       ( start_date <= $now AND end_date IS NULL )
        OR       ( start_date IS NULL AND end_date >= $now )
        OR       ( start_date <= $now AND end_date >= $now )
             )
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $contributionPages = array();
    while ($dao->fetch()) {
      $contributionPages[$dao->id] = $dao->title;
    }
    return $contributionPages;
  }


  /**
   * Get ID and title of CiviCRM events
   *
   * @access private
   * @return array $eventPages Array of event pages
   */
  private function get_event() {
    $now = date('Ymdhis');
    $sql = "
        SELECT id, title
        FROM   civicrm_event
        WHERE  is_active = 1
        AND ( is_template = 0 OR is_template IS NULL )
        AND    (
             ( start_date IS NULL AND end_date IS NULL )
        OR       ( start_date <= $now AND end_date IS NULL )
        OR       ( start_date IS NULL AND end_date >= $now )
        OR       ( start_date <= $now AND end_date >= $now )
        OR       ( start_date >= $now )
             )
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $eventPages = array();
    while ($dao->fetch()) {
      $eventPages[$dao->id] = $dao->title;
    }
    return $eventPages;
  }


  /**
   * Get ID and title of CiviCRM profile pages
   *
   * @access private
   * @return array $profilePages Array of profile pages
   */
  private function get_profile_page() {
    $sql = "
        SELECT g.id as id, g.title as title
        FROM   civicrm_uf_group g, civicrm_uf_join j
        WHERE  g.is_active = 1
        AND    j.is_active = 1
        AND    ( group_type LIKE '%Individual%'
           OR    group_type LIKE '%Contact%' )
        AND    g.id = j.uf_group_id
        AND    j.module = 'Profile'
        ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $profilePages = array();
    while ($dao->fetch()) {
      $profilePages[$dao->id] = $dao->title;
    }
    return $profilePages;
  }


  /**
   * Get ID and title of CiviCRM petition pages
   *
   * @access private
   * @return array $petitionPages Array of petition pages
   */
  private function get_petition() {
    $params = array(
      'version' => 3,
      'is_active' => 1,
      'return' => array('id', 'title'),

    );
    $result = civicrm_api('Survey', 'get', $params);

    $petitionPages = array();
    foreach ($result['values'] as $value) {
      $petitionPages[$value['id']] = $value['title'];
    }
    return $petitionPages;
  }


  /**
   * Callback method for 'admin_footer' hook as set in register_hooks()
   *
   * @return void
   */
  public function add_form_button_html() {

    // add modal to WP selected post types, if allowed
    if ( $this->post_type_has_button() ) {

      if (!$this->civi->initialize()) {
        return '';
      }

      // define title
      $title = __( 'Please select a CiviCRM front-end page type', 'civicrm' );

      // get pages
      $contribution_pages = $this->get_contribution_pages();
      $event_pages = $this->get_event();
      $profile_pages = $this->get_profile_page();
      $petition_pages = $this->get_petition();

      // include markup
      include_once( CIVICRM_PLUGIN_DIR . 'assets/templates/civicrm.modal.php' );

    }

  }


} // class CiviCRM_For_WordPress_Shortcodes_Modal ends


