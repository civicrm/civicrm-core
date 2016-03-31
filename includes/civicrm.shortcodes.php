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
 * Define CiviCRM_For_WordPress_Shortcodes Class
 */
class CiviCRM_For_WordPress_Shortcodes {


  /**
   * Declare our properties
   */

  // init property to store reference to Civi
  public $civi;

  // init property to store shortcodes
  public $shortcodes = array();

  // init property to store shortcode markup
  public $shortcode_markup = array();

  // count multiple passes of do_shortcode in a post
  public $shortcode_in_post = array();


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
   * Register hooks to handle the presence of shortcodes in content
   *
   * @return void
   */
  public function register_hooks() {

    // register the CiviCRM shortcode
    add_shortcode( 'civicrm', array( $this, 'render_single' ) );

    // add CiviCRM core resources when a shortcode is detected in the post content
    add_action( 'wp', array( $this, 'prerender' ), 10, 1 );

  }


  /**
   * Determine if a CiviCRM shortcode is present in any of the posts about to be displayed
   * Callback method for 'wp' hook, always called from WP front-end
   *
   * @param object $wp The WP object, present but not used
   * @return void
   */
  public function prerender( $wp ) {

    /**
     * At this point, all conditional tags are available
     * @see http://codex.wordpress.org/Conditional_Tags
     */

    // bail if this is a 404
    if ( is_404() ) return;

    // a counter's useful
    $shortcodes_present = 0;

    // let's loop through the results
    // this also has the effect of bypassing the logic in
    // https://github.com/civicrm/civicrm-wordpress/pull/36
    if ( have_posts() ) {
      while ( have_posts() ) : the_post();

        global $post;

        // check for existence of shortcode in content
        if ( has_shortcode( $post->post_content, 'civicrm' ) ) {

          // get CiviCRM shortcodes in this post
          $shortcodes_array = $this->get_for_post( $post->post_content );

          // sanity check
          if ( !empty( $shortcodes_array ) ) {

            // add it to our property
            $this->shortcodes[$post->ID] = $shortcodes_array;

            // bump shortcode counter
            $shortcodes_present += count( $this->shortcodes[$post->ID] );

          }

        }

      endwhile;
    }

    // reset loop
    rewind_posts();

    // did we get any?
    if ( $shortcodes_present ) {

      // how should we handle multiple shortcodes?
      if ( $shortcodes_present > 1 ) {

        // add CSS resources for front end
        add_action( 'wp_enqueue_scripts', array( $this->civi, 'front_end_css_load' ), 100 );

        // let's add dummy markup
        foreach( $this->shortcodes AS $post_id => $shortcode_array ) {

          // set flag if there are multple shortcodes in this post
          $multiple = ( count( $shortcode_array ) > 1 ) ? 1 : 0;

          foreach( $shortcode_array AS $shortcode ) {

            // mimic invoke in multiple shortcode context
            $this->shortcode_markup[$post_id][] = $this->render_multiple( $post_id, $shortcode, $multiple );

          }

        }

      } else {

        // add core resources for front end
        add_action( 'wp', array( $this->civi, 'front_end_page_load' ), 100 );

        // since we have only one shortcode, run the_loop again
        // the DB query has already been done, so this has no significant impact
        if ( have_posts() ) {
          while ( have_posts() ) : the_post();

            global $post;

            // is this the post?
            if ( ! array_key_exists( $post->ID, $this->shortcodes ) ) {
              continue;
            }

            // the shortcode must be the first item in the shortcodes array
            $shortcode = $this->shortcodes[$post->ID][0];

            // check to see if a shortcode component has been repeated?
            $atts = $this->get_atts( $shortcode );

            // test for hijacking
            if ( isset( $atts['hijack'] ) AND $atts['hijack'] == '1' ) {
              add_filter( 'civicrm_context', array( $this, 'get_context' ) );
            }

            // store corresponding markup
            $this->shortcode_markup[$post->ID][] = do_shortcode( $shortcode );

            // test for hijacking
            if ( isset( $atts['hijack'] ) AND $atts['hijack'] == '1' ) {

              // ditch the filter
              remove_filter( 'civicrm_context', array( $this, 'get_context' ) );

              // set title
              global $civicrm_wp_title;
              $post->post_title = $civicrm_wp_title;

              // override page title
              add_filter( 'single_post_title', array( $this->civi, 'single_page_title' ), 50, 2 );

              // overwrite content
              add_filter( 'the_content', array( $this, 'get_content' ) );

            }

          endwhile;
        }

        // reset loop
        rewind_posts();

      }

    }

    // flag that we have parsed shortcodes
    $this->shortcodes_parsed = TRUE;

    // broadcast this as well
    do_action( 'civicrm_shortcodes_parsed' );

  }


  /**
   * Handles CiviCRM-defined shortcodes
   *
   * @param array Shortcode attributes array
   * @return string HTML for output
   */
  public function render_single( $atts ) {

    // check if we've already parsed this shortcode
    global $post;
    if ( is_object($post) ) {
      if ( !empty( $this->shortcode_markup ) ) {
        if ( isset( $this->shortcode_markup[$post->ID] ) ) {

          // set counter flag
          if ( ! isset( $this->shortcode_in_post[$post->ID] ) ) {
            $this->shortcode_in_post[$post->ID] = 0;
          } else {
            $this->shortcode_in_post[$post->ID]++;
          }

          // this shortcode must have been rendered
          return $this->shortcode_markup[$post->ID][$this->shortcode_in_post[$post->ID]];

        }
      }
    }

    // preprocess shortcode attributes
    $args = $this->preprocess_atts( $atts );

    // invoke() requires environment variables to be set
    foreach ( $args as $key => $value ) {
      if ( $value !== NULL ) {
        $_REQUEST[$key] = $_GET[$key] = $value;
      }
    }

    // kick out if not CiviCRM
    if (!$this->civi->initialize()) {
      return '';
    }

    // check permission
    $argdata = $this->civi->get_request_args();
    if ( ! $this->civi->users->check_permission( $argdata['args'] ) ) {
      return $this->civi->users->get_permission_denied();;
    }

    // CMW: why do we need this? Nothing that follows uses it...
    require_once ABSPATH . WPINC . '/pluggable.php';

    ob_start(); // start buffering
    $this->civi->invoke(); // now, instead of echoing, shortcode output ends up in buffer
    $content = ob_get_clean(); // save the output and flush the buffer
    return $content;

  }


  /**
   * Return a generic display for a shortcode instead of a CiviCRM invocation
   *
   * @param int $post_id The containing WordPress post ID
   * @param string $shortcode The shortcode being parsed
   * @param bool $multiple Boolean flag, TRUE if post has multiple shortcodes, FALSE otherwise
   * @return string $markup Generic markup for multiple instances
   */
  private function render_multiple( $post_id = FALSE, $shortcode = FALSE, $multiple = 0 ) {

    // get attributes
    $atts = $this->get_atts( $shortcode );

    // pre-process shortcode and retrieve args
    $args = $this->preprocess_atts( $atts );

    // get data for this shortcode
    $data = $this->get_data( $atts, $args );

    // sanity check
    if ( $data === FALSE ) return '';

    // did we get a title?
    $title = __( 'Content via CiviCRM', 'civicrm' );
    if ( ! empty( $data['title'] ) ) $title = $data['title'];

    // init title flag
    $show_title = TRUE;

    // default link
    $link = get_permalink( $post_id );

    // default to no class
    $class = '';

    // access CIvi config object
    $config = CRM_Core_Config::singleton();

    // do we have multiple shortcodes?
    if ( $multiple != 0 ) {

      $links = array();
      foreach( $args AS $var => $arg ) {
        if ( ! empty( $arg ) AND $var != 'q' ) {
          $links[] = $var . '=' . $arg;
        }
      }
      $query = implode( '&', $links );

      // $absolute, $frontend, $forceBackend
      $base_url = $this->civi->get_base_url(TRUE, FALSE, FALSE);

      // construct query parts
      $queryParts = array();
      $queryParts[] = 'page=CiviCRM';
      if (isset($args['q'])) {
        $queryParts[] = 'q=' . $args['q'];
      }
      if (isset($query)) {
        $queryParts[] = $query;
      }

      // construct link
      $link = trailingslashit( $base_url ) . '?' . implode('&', $queryParts);

      // add a class for styling purposes
      $class = ' civicrm-shortcode-multiple';

    }

    // test for hijacking
    if ( !$multiple ) {

      if ( isset( $atts['hijack'] ) AND $atts['hijack'] == '1' ) {

        // add title to array
        $this->post_titles[$post_id] = $data['title'];

        // override title
        add_filter( 'the_title', array( $this, 'get_title' ), 100, 2 );

        // overwrite content
        add_filter( 'the_content', array( $this, 'get_content' ) );

        // don't show title
        $show_title = FALSE;

        // add a class for styling purposes
        $class = ' civicrm-shortcode-single';

      }

    }

    // set some template variables

    // description
    $description = FALSE;
    if ( isset( $data['text'] ) AND ! empty( $data['text'] ) ) {
      $description = $data['text'];
    }

    // provide an enticing link
    $more_link = sprintf(
      '<a href="%s">%s</a>',
      $link,
      apply_filters( 'civicrm_shortcode_more_link', __( 'Find out more...', 'civicrm' ) )
    );

    // assume CiviCRM footer is not enabled
    $empowered_enabled = FALSE;
    $footer = '';

    // test config object for setting
    if ( $config->empoweredBy == 1 ) {

      // footer enabled - define it
      $civi = __( 'CiviCRM.org - Growing and Sustaining Relationships', 'civicrm' );
      $logo = '<div class="empowered-by-logo"><span>' . __( 'CiviCRM', 'civicrm' ) . '</span></div>';
      $civi_link = '<a href="http://civicrm.org/" title="' . $civi . '" target="_blank" class="empowered-by-link">' . $logo . '</a>';
      $empowered = sprintf( __( 'Empowered by %s', 'civicrm' ), $civi_link );
      $footer = apply_filters( 'civicrm_shortcode_footer', $empowered );

      $empowered_enabled = TRUE;

    }

    // start buffering
    ob_start();

    // include template
    include( CIVICRM_PLUGIN_DIR . 'assets/templates/civicrm.shortcode.php' );

    // save the output and flush the buffer
    $markup = ob_get_clean();

    // allow plugins to override
    return apply_filters( 'civicrm_shortcode_render_multiple', $markup, $post_id, $shortcode );

  }


  /**
   * In order to hijack the page, we need to override the context
   *
   * @return string Overridden context code
   */
  public function get_context() {
    return 'nonpage';
  }


  /**
   * In order to hijack the page, we need to override the content
   *
   * @return string Overridden content
   */
  public function get_content( $content ) {

    global $post;

    // is this the post?
    if ( ! array_key_exists( $post->ID, $this->shortcode_markup ) ) {
      return $content;
    }

    // bail if it has multiple shortcodes
    if ( count( $this->shortcode_markup[$post->ID] ) > 1 ) {
      return $content;
    }

    return $this->shortcode_markup[$post->ID][0];

  }


  /**
   * In order to hijack the page, we need to override the title
   *
   * @return string Overridden title
   */
  public function get_title( $title, $post_id ) {

    // is this the post?
    if ( ! array_key_exists( $post_id, $this->shortcode_markup ) ) {
      return $title;
    }

    // bail if it has multiple shortcodes
    if ( count( $this->shortcode_markup[$post_id] ) > 1 ) {
      return $title;
    }

    // shortcodes may or may not override title
    if ( array_key_exists( $post_id, $this->post_titles ) ) {
      $title = $this->post_titles[$post_id];
    }

    return $title;

  }


  /**
   * Detect and return CiviCRM shortcodes in post content
   *
   * @param $content The content to parse
   * @return array $shortcodes Array of shortcodes
   */
  private function get_for_post( $content ) {

    // init return array
    $shortcodes = array();

    // attempt to discover all instances of the shortcode
    $pattern = get_shortcode_regex();

    if (
      preg_match_all( '/' . $pattern . '/s', $content, $matches )
      && array_key_exists( 2, $matches )
      && in_array( 'civicrm', $matches[2] ) )
    {

      // get keys for our shortcode
      $keys = array_keys( $matches[2], 'civicrm' );

      foreach( $keys AS $key ) {
        $shortcodes[] = $matches[0][$key];
      }

    }

    return $shortcodes;

  }


  /**
   * Return attributes for a given CiviCRM shortcode
   *
   * @param $shortcode The shortcode to parse
   * @return array $shortcode_atts Array of shortcode attributes
   */
  private function get_atts( $shortcode ) {

    // strip all but attributes definitions
    $text = str_replace( '[civicrm ', '', $shortcode );
    $text = str_replace( ']', '', $text );

    // extract attributes
    $shortcode_atts = shortcode_parse_atts( $text );

    return $shortcode_atts;

  }


  /**
   * Preprocess CiviCRM-defined shortcodes
   *
   * @param array $atts Shortcode attributes array
   * @return void
   */
  public function preprocess_atts( $atts ) {

    extract( shortcode_atts( array(
      'component' => 'contribution',
      'action' => NULL,
      'mode' => NULL,
      'id' => NULL,
      'cid' => NULL,
      'gid' => NULL,
      'cs' => NULL,
      'force' => NULL,
      ),
      $atts
    ) );

    $args = array(
      'reset' => 1,
      'id'    => $id,
      'force' => $force,
    );

    switch ( $component ) {

      case 'contribution':

        if ( $mode == 'preview' || $mode == 'test' ) {
          $args['action'] = 'preview';
        }
        $args['q'] = 'civicrm/contribute/transact';
        break;

      case 'event':

        switch ( $action ) {
          case 'register':
            $args['q'] = 'civicrm/event/register';
            if ( $mode == 'preview' || $mode == 'test' ) {
              $args['action'] = 'preview';
            }
            break;

          case 'info':
            $args['q'] = 'civicrm/event/info';
            $_REQUEST['page'] = $_GET['page'] = 'CiviCRM';
            break;

          default:
            echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm' ) . '</p>';
            return;
        }
        break;

      case 'user-dashboard':

        $args['q'] = 'civicrm/user';
        unset( $args['id'] );
        break;

      case 'profile':

        if ($mode == 'edit') {
          $args['q'] = 'civicrm/profile/edit';
        }
        elseif ($mode == 'view') {
          $args['q'] = 'civicrm/profile/view';
        }
        elseif ($mode == 'search') {
          $args['q'] = 'civicrm/profile';
        }
        else {
          $args['q'] = 'civicrm/profile/create';
        }
        $args['gid'] = $gid;
        break;


      case 'petition':

        $args['q'] = 'civicrm/petition/sign';
        $args['sid'] = $args['id'];
        unset($args['id']);
        break;

      default:

        echo '<p>' . __( 'Do not know how to handle this shortcode', 'civicrm' ) . '</p>';
        return;

    }

    return $args;

  }


  /**
   * Post-process CiviCRM-defined shortcodes
   *
   * @param array $atts Shortcode attributes array
   * @param array $args Shortcode arguments array
   * @return void
   */
  public function get_data( $atts, $args ) {

    // init return array
    $data = array();

    if (!$this->civi->initialize()) {
      return FALSE;
    }

    // get the Civi entity via the API
    $params = array(
      'version' => 3,
      'page' => 'CiviCRM',
      'q' => 'civicrm/ajax/rest',
      'sequential' => '1',
    );

    switch ( $atts['component'] ) {

      case 'contribution':

        // add event ID
        $params['id'] = $args['id'];

        // call API
        $civi_entity = civicrm_api( 'contribution_page', 'getsingle', $params );

        // set title
        $data['title'] = $civi_entity['title'];

        // set text, if present
        $data['text'] = '';
        if ( isset( $civi_entity['intro_text'] ) ) {
          $data['text'] = $civi_entity['intro_text'];
        }

        break;

      case 'event':

        // add event ID
        $params['id'] = $args['id'];

        // call API
        $civi_entity = civicrm_api( 'event', 'getsingle', $params );

        // set title
        switch ( $atts['action'] ) {
          case 'register':
            $data['title'] = sprintf(
              __( 'Register for %s', 'civicrm' ),
              $civi_entity['title']
            );
            break;

          case 'info':
          default:
            $data['title'] = $civi_entity['title'];
            break;
        }

        // set text, if present
        $data['text'] = '';
        if ( isset( $civi_entity['summary'] ) ) {
          $data['text'] = $civi_entity['summary'];
        }
        if (
          // summary is not present or is empty
          ( !isset($civi_entity['summary']) OR empty($civi_entity['summary']) )
          AND
          // we do have a description
          isset( $civi_entity['description'] ) AND !empty( $civi_entity['description'] )
        ) {
          // override with description
          $data['text'] = $civi_entity['description'];
        }

        break;

      case 'user-dashboard':

        // set title
        $data['title'] = __( 'Dashboard', 'civicrm' );
        break;

      case 'profile':

        // add event ID
        $params['id'] = $args['gid'];

        // call API
        $civi_entity = civicrm_api( 'uf_group', 'getsingle', $params );

        // set title
        $data['title'] = $civi_entity['title'];

        // set text to empty
        $data['text'] = '';
        break;


      case 'petition':

        // add petition ID
        $params['id'] = $atts['id'];

        // call API
        $civi_entity = civicrm_api( 'survey', 'getsingle', $params );

        // set title
        $data['title'] = $civi_entity['title'];

        // set text, if present
        $data['text'] = '';
        if ( isset( $civi_entity['instructions'] ) ) {
          $data['text'] = $civi_entity['instructions'];
        }

        break;

      default:

        // do we need to protect against malformed shortcodes?
        break;

    }

    return $data;

  }


} // class CiviCRM_For_WordPress_Shortcodes ends


