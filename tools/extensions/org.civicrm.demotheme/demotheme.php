<?php

/**
 * Implements hook_civicrm_themes().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_themes
 */
function demotheme_civicrm_themes(&$themes) {

  // Example #1: A standard theme. CSS files may take the names:
  //  - css/civicrm.css
  //  - css/bootstrap.css
  //  - org.civicrm.volunteer-css/slider.css

  $themes['demotheme'] = array(
    'ext' => 'org.civicrm.demotheme',
    'title' => 'Demo Theme',
    'help' => 'This is a simple demonstration theme',
  );

  // -------------------------------------------------------------------------

  // Example #2: A subtheme configuration where "demotheme-blue" inherits files
  // from "demotheme-base". CSS files may take the names:
  // - base/css/civicrm.css
  // - base/css/bootstrap.css
  // - base/org.civicrm.volunteer-css/slider.css
  // - blue/css/civicrm.css
  // - blue/css/bootstrap.css
  // - blue/org.civicrm.volunteer-css/slider.css
  //
  //  $themes['demotheme-base'] = array(
  //    'ext' => 'org.civicrm.demotheme',
  //    'title' => 'Demo Theme (base)',
  //    'help' => 'This is a demonstration theme',
  //    'prefix' => 'base/',
  //  );
  //
  //  $themes['demotheme-blue'] = array(
  //    'ext' => 'org.civicrm.demotheme',
  //    'title' => 'Demo Theme (blue version)',
  //    'help' => 'This is a demonstration theme (blue version)',
  //    'prefix' => 'blue/',
  //    'search_order' => array('demotheme-blue', 'demotheme-base', '*fallback*'),
  //  );

  // -------------------------------------------------------------------------

  // Example #3: A complex configuration where one uses custom lookup function.
  //
  //  $themes['demotheme-dynamic'] = array(
  //    'ext' => 'org.civicrm.demotheme',
  //    'title' => 'Demo Theme (Dynamic)',
  //    'help' => 'This is a dynamic demonstration theme',
  //    'url_callback' => '_demotheme_url_callback',
  //  );
}

/*
function _demotheme_url_callback(\Civi\Core\Themes $themes, $themeKey, $cssExt, $cssKey) {
  return array("http://example.com/the-do-whatever-you-need.css");
}
*/