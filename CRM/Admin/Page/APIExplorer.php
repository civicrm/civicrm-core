<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Api Explorer
 */
class CRM_Admin_Page_APIExplorer extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   */
  public function run() {
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Admin/Page/APIExplorer.js')
      ->addScriptFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.js', 99)
      ->addStyleFile('civicrm', 'bower_components/google-code-prettify/bin/prettify.min.css', 99)
      ->addVars('explorer', ['max_joins' => \Civi\API\Api3SelectQuery::MAX_JOINS]);

    $this->assign('operators', CRM_Core_DAO::acceptedSQLOperators());

    return parent::run();
  }

  /**
   * Get user context.
   *
   * @return string
   *   user context.
   */
  public function userContext() {
    return 'civicrm/api';
  }

  /**
   * Format a docblock to be a bit more readable
   *
   * FIXME: APIv4 uses markdown in code docs. Switch to that.
   *
   * @param string $text
   * @return string
   */
  public static function formatDocBlock($text) {
    // Normalize #leading spaces.
    $lines = explode("\n", $text);
    $lines = preg_replace('/^ +\*/', ' *', $lines);
    $text = implode("\n", $lines);

    // Get rid of comment stars
    $text = str_replace(["\n * ", "\n *\n", "\n */\n", "/**\n"], ["\n", "\n\n", '', ''], $text);

    // Format for html
    $text = htmlspecialchars($text);

    // Extract code blocks - save for later to skip html conversion
    $code = [];
    preg_match_all('#(@code|```)(.*?)(@endcode|```)#is', $text, $code);
    $text = preg_replace('#(@code|```)(.*?)(@endcode|```)#is', '<pre></pre>', $text);

    // Convert @annotations to titles
    $text = preg_replace_callback(
      '#^[ ]*@(\w+)([ ]*)#m',
      function($matches) {
        return "<strong>" . ucfirst($matches[1]) . "</strong>" . (empty($matches[2]) ? '' : ': ');
      },
      $text);

    // Preserve indentation
    $text = str_replace("\n ", "\n&nbsp;&nbsp;&nbsp;&nbsp;", $text);

    // Convert newlines
    $text = nl2br($text);

    // Add unformatted code blocks back in
    if ($code && !empty($code[2])) {
      foreach ($code[2] as $block) {
        $text = preg_replace('#<pre></pre>#', "<pre>$block</pre>", $text, 1);
      }
    }
    return $text;
  }

}
