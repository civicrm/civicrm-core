<?php

/**
 * Placeholder page which generates a redirect
 *
 * @code
 * <item>
 *   <path>civicrm/admin/options/case_type</path>
 *   <page_callback>CRM_Core_Page_Redirect</page_callback>
 *   <page_arguments>url=civicrm/foo/bar?whiz=bang&amp;passthru=%%passthru%%</page_arguments>
 * </item>
 * @endcoe
 */
class CRM_Core_Page_Redirect extends CRM_Core_Page {
  function run($path = NULL, $pageArgs = array()) {
    $url = self::createUrl($path, $_REQUEST, $pageArgs, TRUE);
    // return $url;
    CRM_Utils_System::redirect($url);
  }

  /**
   * @param array $requestPath the parts of the path in the current page request
   * @param array $requestArgs any GET arguments
   * @param array $pageArgs the page_arguments registered in the router
   * @param array $requestArgs any parameters passed as part of this page request
   * @param bool $absolute whether to return an absolute URL
   * @return string URL
   */
  public static function createUrl($requestPath, $requestArgs, $pageArgs, $absolute) {
    if (empty($pageArgs['url'])) {
      CRM_Core_Error::fatal('This page is configured as a redirect, but it does not have a target.');
    }

    $vars = array();
    // note: %% isn't legal in a well-formed URL, so it's not a bad variable-delimiter
    foreach ($requestPath as $pathPos => $pathPart) {
      $vars["%%{$pathPos}%%"] = urlencode($pathPart);
    }
    foreach ($requestArgs as $var => $value) {
      $vars["%%{$var}%%"] = urlencode($value);
    }
    $urlString = strtr($pageArgs['url'], $vars);
    $urlString = preg_replace('/%%[a-zA-Z0-9]+%%/', '', $urlString);

    $urlParts = parse_url($urlString);
    $url = CRM_Utils_System::url(
      $urlParts['path'],
      CRM_Utils_Array::value('query', $urlParts, NULL),
      $absolute,
      CRM_Utils_Array::value('fragment', $urlParts, NULL)
    );

    return $url;
  }
}
