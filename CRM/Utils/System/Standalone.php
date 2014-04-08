<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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

require_once 'CRM/Utils/System/Base.php';

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * Standalone (a.k.a. CMS agnostic) specific stuff goes here
 */
class CRM_Utils_System_Standalone extends CRM_Utils_System_Base {

  function __construct() {
//    parent::__contruct();
    // are we running full CiviCRM or embedded in a iFrame
    $config = CRM_Core_Config::singleton();
    $config->inCiviCRM = (strrchr($_SERVER['SCRIPT_NAME'], '/') == '/index.php');
    $this->is_drupal = FALSE;
  }

  /**
   * sets the title of the page
   *
   * @param string $title
   * @paqram string $pageTitle
   *
   * @return void
   * @access public
   */
  function setTitle( $title, $pageTitle = null ) {
    if ( ! $pageTitle ) {
      $pageTitle = $title;
    }

    $template = CRM_Core_Smarty::singleton( );
    $template->assign( 'pageTitle', $pageTitle );
    $template->assign( 'docTitle',  $title );
    // Add jQuery and other resources
    CRM_Core_Resources::singleton()
      ->addCoreResources();
    return;
  }

  /**
   * Append an additional breadcrumb tag to the existing breadcrumb
   *
   * @param string $title
   * @param string $url   
   *
   * @return void
   * @access public
   * @static
   */
  static function appendBreadCrumb( $breadCrumbs ) {
    $template = CRM_Core_Smarty::singleton( );
    $bc = $template->get_template_vars( 'breadcrumb' );

    if ( is_array( $breadCrumbs ) ) {
      foreach ( $breadCrumbs as $crumbs ) {
        if ( stripos($crumbs['url'], 'id%%') ) {
          $args = array( 'cid', 'mid' );
          foreach ( $args as $a ) {
            $val  = CRM_Utils_Request::retrieve( $a, 'Positive', CRM_Core_DAO::$_nullObject,
                                                 false, null, $_GET );
            if ( $val ) {
              $crumbs['url'] = str_ireplace( "%%{$a}%%", $val, $crumbs['url'] );
            }
          }
        }
        $bc[] = $crumbs;
      }
    }
    $template->assign_by_ref( 'breadcrumb', $bc );
    return;
  }

  /**
   * Reset an additional breadcrumb tag to the existing breadcrumb
   *
   * @return void
   * @access public
   * @static
   */
  static function resetBreadCrumb( ) {
    return;
  }

  /**
   * Append a string to the head of the html file
   *
   * @param string $head the new string to be appended
   *
   * @return void
   * @access public
   * @static
   */
  static function addHTMLHead( $head ) {
    $template = CRM_Core_Smarty::singleton( );
    $template->append( 'pageHTMLHead', $head );
    return;
  }

  /**
   * Add a script file
   *
   * @param $url: string, absolute path to file
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addScriptUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline script
   *
   * @param $code: string, javascript code
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addScript($code, $region) {
    return FALSE;
  }

  /**
   * Add a css file
   *
   * @param $url: string, absolute path to file
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addStyleUrl($url, $region) {
    return FALSE;
  }

  /**
   * Add an inline style
   *
   * @param $code: string, css code
   * @param $region string, location within the document: 'html-header', 'page-header', 'page-footer'
   *
   * Note: This function is not to be called directly
   * @see CRM_Core_Region::render()
   *
   * @return bool TRUE if we support this operation in this CMS, FALSE otherwise
   * @access public
   */
  public function addStyle($code, $region) {
    return FALSE;
  }

  /**
   * rewrite various system urls to https 
   *  
   * @param null 
   *
   * @return void 
   * @access public  
   * @static  
   */  
  static function mapConfigToSSL( ) {
    global $base_url;
    $base_url = str_replace( 'http://', 'https://', $base_url );
  }

  /**
   * figure out the post url for the form
   *
   * @param mix $action the default action if one is pre-specified
   *
   * @return string the url to post the form
   * @access public
   * @static
   */
  static function postURL( $action ) {
    if ( ! empty( $action ) ) {
      return $action;
    }
    if ( isset( $_GET['q'] ) ) {
      return self::url( $_GET['q'] );
    } else {
      return '';
    }
  }

  /**
   * Generate an internal CiviCRM URL (copied from DRUPAL/includes/common.inc#url)
   *
   * @param $path     string   The path being linked to, such as "civicrm/add"
   * @param $query    string   A query string to append to the link.
   * @param $absolute boolean  Whether to force the output to be an absolute link (beginning with http:).
   *                           Useful for links that will be displayed outside the site, such as in an
   *                           RSS feed.
   * @param $fragment string   A fragment identifier (named anchor) to append to the link.
   * @param $htmlize  boolean  whether to convert to html equivalent
   *
   * @return string            an HTML string containing a link to the given path.
   * @access public
   *
   */
  function url($path = null, $query = null, $absolute = true, $fragment = null, $htmlize = true ) {
    $config = CRM_Core_Config::singleton( );
    $script = ($config->inCiviCRM ? 'index.php' : 'embed.php');

    if (isset($fragment)) {
      $fragment = '#'. $fragment;
    }

    if ( ! isset( $config->useFrameworkRelativeBase ) ) {
      $base = parse_url( $config->userFrameworkBaseURL );
      $config->useFrameworkRelativeBase = $base['path'];
    }
    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = $htmlize ? '&amp;' : '&';

    if (! $config->cleanURL ) {
      if ( isset( $path ) ) {
        if ( isset( $query ) ) {
          return $base . $script .'?q=' . $path . $separator . $query . $fragment;
        } else {
          return $base . $script .'?q=' . $path . $fragment;
        }
      } else {
        if ( isset( $query ) ) {
          return $base . $script .'?'. $query . $fragment;
        } else {
          return $base . $fragment;
        }
      }
    } else {
      if ( isset( $path ) ) {
        if ( isset( $query ) ) {
          return $base . $path .'?'. $query . $fragment;
        } else {
          return $base . $path . $fragment;
        }
      } else {
        if ( isset( $query ) ) {
          return $base . $script .'?'. $query . $fragment;
        } else {
          return $base . $fragment;
        }
      }
    }
  }

  /**
   * Eventually we should use OAuth here, since this is mainly
   * for API authentication.
   *
   * For now let's just verify that they passed in a valid
   * OpenID. The API layer verifies a valid API key later anyway,
   * so we don't duplicate that effort here.
   *
   * @param string $name     the user name
   * @param string $password the password for the above user name
   *
   * @return mixed false if no auth
   *               array( contactID, ufID, unique string ) if success
   * @access public
   * @static
   */
  static function authenticate($name, $password, $loadCMSBootstrap = FALSE) {
    // NG: Does NOT work for command line invocation
    /*      // check that we got a valid URL
            $options = array( 'domain_check'    => false,
                              'allowed_schemes' => array( 'http', 'https' ) );
            require_once 'Validate.php';
            $validUrl = Validate::uri( $name, $options );
            if ( !$validUrl ) {
                return false;
            }
    */

    // we got a valid URL, see if it's allowed to login
    require_once 'CRM/Core/BAO/OpenID.php';
    $allowLogin = CRM_Core_BAO_OpenID::isAllowedToLogin( $name );
    if ( !$allowLogin ) {
      return false;
    }

    // see if the password matches the API key
    require_once 'CRM/Contact/BAO/Contact.php';
    $dao = CRM_Contact_BAO_Contact::matchContactOnOpenId( $name );
    require_once 'CRM/Core/DAO.php';
    $api_key = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $dao->contact_id, 'api_key');
    if ( $api_key != $password ) {
      return false;
    }

    // everything looks good, setup the session and return
    require_once 'CRM/Standalone/User.php';
    $user = new CRM_Standalone_User( $name );
    require_once 'CRM/Core/BAO/UFMatch.php';
    CRM_Core_BAO_UFMatch::synchronize( $user, false, 'Standalone', 'Individual' );
    require_once 'CRM/Core/Session.php';
    $session = CRM_Core_Session::singleton();
    $returnArray = array( $session->get('userID'), $session->get('ufID'), mt_rand() );
    return $returnArray;
  }

  /**
   * Get the userID (contact_id) for an already-authorized OpenID login
   *
   * @param mix $user the user object holding OpenID auth info
   *
   * @return void
   * @access public
   * @static
   */
  static function getUserID( $user ) {
    require_once 'CRM/Core/BAO/UFMatch.php';

    // this puts the appropriate values in the session, so
    // no need to return anything
    CRM_Core_BAO_UFMatch::synchronize( $user, true, 'Standalone', 'Individual' );
  }

  /**
   * Get if the user is allowed to login 
   *
   * @param $user the user object holding auth info
   *
   * @return boolean
   * @access public
   * @static
   */
  static function getAllowedToLogin( $user ) {
    require_once 'CRM/Core/BAO/OpenID.php';

    // this returns true if the user is allowed to log in, false o/w
    $allow_login = CRM_Core_BAO_OpenID::isAllowedToLogin( $user->identity_url );
    return $allow_login;
  }

  /**
   * Set a message in the UF to display to a user 
   *   
   * @param string $message the message to set 
   *   
   * @access public   
   * @static   
   */   
  static function setMessage( $message ) {
    return;
  }

  /**
   * Function to create a user in host CMS
   *
   * @param array  $params associated array
   * @param string $mail email id for cms user
   *
   * @return uid if user exists, false otherwise
   *
   * @access public
   */
  function createUser(&$params, $mail) {
    return FALSE;
  }

  function loadUser($user) {
    return TRUE;
  }

  /**
   * Change user name in host CMS
   *
   * @param integer $ufID User ID in CMS
   * @param string $ufName User name
   */
  function updateCMSName($ufID, $ufName) {
  }

  static function permissionDenied( ) {
      CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
  }

  static function logout( ) {
    session_destroy();

    if (CIVICRM_UF_AUTH == 'SAML20') {
      require_once 'onelogin/saml.php';
      require_once 'saml.settings.php';
      $settings = saml_get_settings();
      if ($settings->idp_slo_target_url)
        header("Location:".$settings->idp_slo_target_url);
    }
    header("Location: " . (defined('CIVICRM_LOGO_URL')?CIVICRM_LOGO_URL:"http://www.cividesk.com/"));
  }

  /**
   * Get the locale set in the hosting CMS
   * @return null  as the language is set elsewhere
   */
  static function getUFLocale()
  {
    return null;
  }

  /**
   * load standalone bootstrap
   *
   * @param $params array with uid or name and password 
   * @param $loadUser boolean load cms user?
   * @param $throwError throw error on failure?
   */
  static function loadBootStrap( $params = array( ), $loadUser = true, $throwError = true )
  {
    // load BootStrap here if needed
    return true;
  }

  /**
   * check is user logged in.
   *
   * @return boolean true/false.
   */
  public function isUserLoggedIn( ) {
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');
    return (!empty($userID));
  }

  /**
   * Get currently logged in user uf id.
   *
   * @return int logged in user uf id.
   */
  public function getLoggedInUfID() {
    $session = CRM_Core_Session::singleton();
    return $session->get('ufID');
  }

  /**
   * Get user login URL for hosting CMS (method declared in each CMS system class)
   *
   * @param string $destination - if present, add destination to querystring (works for Drupal only)
   *
   * @return string - loginURL for the current CMS
   * @static
   */
  public function getLoginURL($destination = '') {
    $config = CRM_Core_Config::singleton();
    $loginURL = $config->userFrameworkBaseURL . 'standalone/login.php';
    if ($destination) {
      $loginURL .= '?redirect=' . urlencode($destination);
    }
    return $loginURL;
  }

  public function getLoginDestination(&$form) {
    return;
  }

  function getVersion() {
    return CRM_Utils_System::version();
  }
}