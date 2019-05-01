<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Api Explorer
 */
class CRM_Admin_Page_APIExplorer extends CRM_Core_Page {

  /**
   * Return unique paths for checking for examples.
   * @return array
   */
  private static function uniquePaths() {
    // Ensure that paths with trailing slashes are properly dealt with
    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach ($paths as $id => $rawPath) {
      $pathParts = explode(DIRECTORY_SEPARATOR, $rawPath);
      foreach ($pathParts as $partId => $part) {
        if (empty($part)) {
          unset($pathParts[$partId]);
        }
      }
      $newRawPath = implode(DIRECTORY_SEPARATOR, $pathParts);
      if ($newRawPath != $rawPath) {
        $paths[$id] = DIRECTORY_SEPARATOR . $newRawPath;
      }
    }
    $paths = array_unique($paths);
    return $paths;
  }

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

    // List example directories
    // use get_include_path to ensure that extensions are captured.
    $examples = [];
    $paths = self::uniquePaths();
    foreach ($paths as $path) {
      $dir = \CRM_Utils_File::addTrailingSlash($path) . 'api' . DIRECTORY_SEPARATOR . 'v3' . DIRECTORY_SEPARATOR . 'examples';
      if (is_dir($dir)) {
        foreach (scandir($dir) as $item) {
          if ($item && strpos($item, '.') === FALSE && array_search($item, $examples) === FALSE) {
            $examples[] = $item;
          }
        }
      }
    }
    sort($examples);
    $this->assign('examples', $examples);

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
   * AJAX callback to fetch examples.
   */
  public static function getExampleFile() {
    if (!empty($_GET['entity']) && strpos($_GET['entity'], '.') === FALSE) {
      $examples = [];
      $paths = self::uniquePaths();
      foreach ($paths as $path) {
        $dir = \CRM_Utils_File::addTrailingSlash($path) . 'api' . DIRECTORY_SEPARATOR . 'v3' . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . $_GET['entity'];
        if (is_dir($dir)) {
          foreach (scandir($dir) as $item) {
            $item = str_replace('.php', '', $item);
            if ($item && strpos($item, '.') === FALSE) {
              $examples[] = ['key' => $item, 'value' => $item];
            }
          }
        }
      }
      CRM_Utils_JSON::output($examples);
    }
    if (!empty($_GET['file']) && strpos($_GET['file'], '.') === FALSE) {
      $paths = self::uniquePaths();
      $fileFound = FALSE;
      foreach ($paths as $path) {
        $fileName = \CRM_Utils_File::addTrailingSlash($path) . 'api' . DIRECTORY_SEPARATOR . 'v3' . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . $_GET['file'] . '.php';
        if (!$fileFound && file_exists($fileName)) {
          $fileFound = TRUE;
          echo file_get_contents($fileName);
        }
      }
      if (!$fileFound) {
        echo "Not found.";
      }
      CRM_Utils_System::civiExit();
    }
    CRM_Utils_System::permissionDenied();
  }

  /**
   * Ajax callback to display code docs.
   */
  public static function getDoc() {
    // Verify the API handler we're talking to is valid.
    $entities = civicrm_api3('Entity', 'get');
    $entity = CRM_Utils_Array::value('entity', $_GET);
    if (!empty($entity) && in_array($entity, $entities['values']) && strpos($entity, '.') === FALSE) {
      $action = CRM_Utils_Array::value('action', $_GET);
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
      $fnName = 'civicrm_api3_' . _civicrm_api_get_entity_name_from_camel($entity) . '_' . $action;
      // Support the alternate "1 file per action" structure
      $actionFile = "api/v3/$entity/" . ucfirst($action) . '.php';
      $actionFileContents = file_get_contents("api/v3/$entity/" . ucfirst($action) . '.php', FILE_USE_INCLUDE_PATH);
      if ($actionFileContents) {
        $file = $actionFile;
        $contents = $actionFileContents;
      }
      // If action isn't in this file, try generic
      if (strpos($contents, "function $fnName") === FALSE) {
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
   * Not a proper doc parser... patches welcome :)
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
    preg_match_all('#@code(.*?)@endcode#is', $text, $code);
    $text = preg_replace('#@code.*?@endcode#is', '<pre></pre>', $text);

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
    if ($code && !empty($code[1])) {
      foreach ($code[1] as $block) {
        $text = preg_replace('#<pre></pre>#', "<pre>$block</pre>", $text, 1);
      }
    }
    return $text;
  }

}
