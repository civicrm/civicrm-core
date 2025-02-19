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
   * Ajax callback to display code docs.
   */
  public static function getDoc() {
    // Verify the API handler we're talking to is valid.
    $entities = civicrm_api3('Entity', 'get');
    $entity = $_GET['entity'] ?? NULL;
    if (!empty($entity) && in_array($entity, $entities['values']) && !str_contains($entity, '.')) {
      $action = $_GET['action'] ?? NULL;
      $doc = self::getDocblock($entity, $action);
      $result = [
        'doc' => $doc ? self::formatDocBlock($doc[0]) : 'Not found.',
        'code' => $doc ? $doc[1] : NULL,
        'file' => $doc ? $doc[2] : NULL,
      ];
      if (!$action) {
        $actions = civicrm_api3($entity, 'getactions');
        $result['actions'] = CRM_Utils_Array::makeNonAssociative(array_combine($actions['values'], $actions['values']));
      }
      CRM_Utils_JSON::output($result);
    }
    CRM_Utils_System::permissionDenied();
  }

  /**
   * Get documentation block.
   *
   * @param string $entity
   * @param string|null $action
   * @return array|bool
   *   [docblock, code]
   */
  private static function getDocBlock($entity, $action) {
    if (!$entity) {
      return FALSE;
    }
    $file = "api/v3/$entity.php";
    $contents = file_get_contents($file, FILE_USE_INCLUDE_PATH);
    if (!$contents) {
      // Api does not exist
      return FALSE;
    }
    $docblock = $code = [];
    // Fetch docblock for the api file
    if (!$action) {
      if (preg_match('#/\*\*\n.*?\n \*/\n#s', $contents, $docblock)) {
        return [$docblock[0], NULL, $file];
      }
    }
    // Fetch block for a specific action
    else {
      $action = strtolower($action);
      $fnName = 'civicrm_api3_' . CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($entity) . '_' . $action;
      // Support the alternate "1 file per action" structure
      $actionFile = "api/v3/$entity/" . ucfirst($action) . '.php';
      $actionFileContents = file_get_contents("api/v3/$entity/" . ucfirst($action) . '.php', FILE_USE_INCLUDE_PATH);
      if ($actionFileContents) {
        $file = $actionFile;
        $contents = $actionFileContents;
      }
      // If action isn't in this file, try generic
      if (!str_contains($contents, "function $fnName")) {
        $fnName = "civicrm_api3_generic_$action";
        $file = "api/v3/Generic/" . ucfirst($action) . '.php';
        $contents = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        if (!$contents) {
          $file = "api/v3/Generic.php";
          $contents = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        }
      }
      if (preg_match('#(/\*\*(\n \*.*)*\n \*/\n)function[ ]+' . $fnName . '#i', $contents, $docblock)) {
        // Fetch the code in a separate regex to preserve sanity
        preg_match("#^function[ ]+$fnName.*?^}#ism", $contents, $code);
        return [$docblock[1], $code[0], $file];
      }
    }
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
