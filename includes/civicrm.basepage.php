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
         * The following logic is in response to some of the complexities of how
         * titles are handled in WordPress, particularly when there are SEO
         * plugins present that modify the title for Open Graph purposes. There
         * have also been issues with the default WordPress themes, which modify
         * the title using the 'wp_title' filter.
         *
         * First, we try and set the title of the page object, which will work
         * if the loop is not run subsequently and if there are no additional
         * filters on the title.
         *
         * Second, we store the CiviCRM title so that we can construct the base
         * page title if other plugins modify it.
         */

        // override post title
        global $civicrm_wp_title;
        $post->post_title = $civicrm_wp_title;

        // because the above seems unreliable, store title for later use
        $this->basepage_title = $civicrm_wp_title;

        // disallow commenting
        $post->comment_status = 'closed';

      endwhile;
    }

    // reset loop
    rewind_posts();

    // override page title with high priority
    add_filter( 'wp_title', array( $this, 'wp_page_title' ), 100, 3 );

    // add compatibility with WordPress SEO plugin's Open Graph title
    add_filter( 'wpseo_opengraph_title', array( $this, 'wpseo_page_title' ), 100, 1 );

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
   * Get CiviCRM basepage title for <title> element
   *
   * Callback method for 'wp_title' hook, called at the end of function wp_title
   *
   * @param string $title Title that might have already been set
   * @param string $separator Separator determined in theme (but defaults to WordPress default)
   * @param string $separator_location Whether the separator should be left or right
   */
  public function wp_page_title( $title, $separator = '&raquo;', $separator_location = '' ) {

    // if feed, return just the title
    if ( is_feed() ) return $this->basepage_title;

    // set default separator location, if it isn't defined
    if ( '' === trim( $separator_location ) ) {
      $separator_location = ( is_rtl() ) ? 'left' : 'right';
    }

    // if we have WP SEO present, use its separator
    if ( class_exists( 'WPSEO_Options' ) ) {
      $separator_code = WPSEO_Options::get_default( 'wpseo_titles', 'separator' );
      $separator_array = WPSEO_Option_Titles::get_instance()->get_separator_options();
      if ( array_key_exists( $separator_code, $separator_array ) ) {
      	$separator = $separator_array[$separator_code];
      }
    }

    // construct title depending on separator location
    if ( $separator_location == 'right' ) {
	  $title = $this->basepage_title . " $separator " . get_bloginfo( 'name', 'display' );
    } else {
	  $title = get_bloginfo( 'name', 'display' ) . " $separator " . $this->basepage_title;
    }

    // return modified title
    return $title;

  }


  /**
   * Get CiviCRM base page title for Open Graph elements
   *
   * Callback method for 'wpseo_opengraph_title' hook, to provide compatibility
   * with the WordPress SEO plugin.
   *
   * @param string $post_title The title of the WordPress page or post
   * @return string $basepage_title The title of the CiviCRM entity
   */
  public function wpseo_page_title( $post_title ) {

    // hand back our base page title
    return $this->basepage_title;

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


