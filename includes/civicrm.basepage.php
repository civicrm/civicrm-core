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
 * Define CiviCRM_For_WordPress_Basepage Class
 */
class CiviCRM_For_WordPress_Basepage {


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
   * Register hooks to handle CiviCRM in a WordPress wpBasePage context
   *
   * @return void
   */
  public function register_hooks() {
    
    // kick out if not CiviCRM
    if (!$this->civi->initialize()) {
      return;
    }
    
    // regardless of URL, load page template
    add_filter( 'template_include', array( $this, 'basepage_template' ), 999 );
        
    // check permission
    $argdata = $this->civi->get_request_args();
    if ( ! $this->civi->users->check_permission( $argdata['args'] ) ) {
      add_filter( 'the_content', array( $this->civi->users, 'get_permission_denied' ) );
      return;
    }

    // cache CiviCRM base page markup
    add_action( 'wp', array( $this, 'basepage_handler' ), 10, 1 );
    
  }


  /**
   * Build CiviCRM base page content
   * Callback method for 'wp' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function basepage_handler( $wp ) {

    /**
     * At this point, all conditional tags are available
     * @see http://codex.wordpress.org/Conditional_Tags
     */
    
    // bail if this is a 404
    if ( is_404() ) return;
    
    // kick out if not CiviCRM
    if (!$this->civi->initialize()) {
      return '';
    }
    
    // add core resources for front end
    add_action( 'wp', array( $this->civi, 'front_end_page_load' ), 100 );
    
    // CMW: why do we need this? Nothing that follows uses it...
    require_once ABSPATH . WPINC . '/pluggable.php';

    // let's do the_loop
    // this has the effect of bypassing the logic in
    // https://github.com/civicrm/civicrm-wordpress/pull/36
    if ( have_posts() ) {
      while ( have_posts() ) : the_post();
        
        global $post;
        
        ob_start(); // start buffering
        $this->civi->invoke(); // now, instead of echoing, base page output ends up in buffer
        $this->basepage_markup = ob_get_clean(); // save the output and flush the buffer
        
        /**
         * CMW: the following only works if the loop is not run again before the
         * page is rendered. It is probably better to store the title and use a
         * filter when the page is rendered.
         */
         
        // override post title
        global $civicrm_wp_title;
        $post->post_title = $civicrm_wp_title;
        
        // disallow commenting
        $post->comment_status = 'closed';
        
      endwhile;
    }
    
    // reset loop
    rewind_posts();
    
    // override page title
    add_filter( 'single_post_title', array( $this->civi, 'single_page_title' ), 50, 2 );
    
    // include this content when base page is rendered
    add_filter( 'the_content', array( $this, 'basepage_render' ) );

    // hide the edit link
    add_action( 'edit_post_link', array( $this->civi, 'clear_edit_post_link' ) );
    
    // tweak admin bar
    add_action( 'wp_before_admin_bar_render', array( $this->civi, 'clear_edit_post_menu_item' ) );
    
    // flag that we have parsed the base page
    $this->basepage_parsed = TRUE;
    
    // broadcast this as well
    do_action( 'civicrm_basepage_parsed' );
    
  }


  /**
   * Get CiviCRM base page content
   * Callback method for 'the_content' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function basepage_render() {
    
    // hand back our base page markup
    return $this->basepage_markup;
  
  }


  /**
   * Get CiviCRM base page template
   * Callback method for 'template_include' hook, always called from WP front-end
   *
   * @param string $template The path to the existing template
   * @return string $template The modified path to the desired template
   */
  public function basepage_template( $template ) {
    
    // use the basic page template, but allow overrides
    $page_template = locate_template( array(
      apply_filters( 'civicrm_basepage_template', 'page.php' )
    ) );
    
    if ( '' != $page_template ) {
      return $page_template;
    }
    
    // fallback
    return $template;
  
  }


} // class CiviCRM_For_WordPress_Basepage ends


