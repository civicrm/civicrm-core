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
namespace Civi\Report;

/**
 * OutputHandlers can either be the standard core ones: print/pdf/csv, or
 * extensions can add their own.
 *
 * @package Civi\Report
 */
class OutputHandlerFactory {

  protected static $singleton;

  /**
   * @var array
   *   Array of registered possible OutputHandlers.
   */
  protected static $registered = [];

  /**
   * Singleton function.
   *
   * @return OutputHandlerFactory
   */
  public static function singleton() {
    if (self::$singleton === NULL) {
      self::$singleton = new OutputHandlerFactory();
      self::registerBuiltins();
    }
    return self::$singleton;
  }

  /**
   * Return an OutputHandler based on the parameters.
   *
   * @param \CRM_Report_Form $form
   *   A CiviReport that extends CRM_Report_Form.
   *
   * @return \Civi\Report\OutputHandlerInterface|NULL
   *   An object that implements the OutputHandlerInterface, or NULL if
   *   nothing suitable for the given parameters.
   */
  public function create(\CRM_Report_Form $form) {
    /**
     * The first draft of this had extensions register their classes,
     * but it needed to be early on because there's also the dropdown on the
     * report form that lists the output formats available which happens
     * earlier than the output run and that worked better as a simple hook.
     * So it just felt out of place then to have two different types of things,
     * and people are used to hooks, and there's already alterReportVar which
     * seemed a natural place.
     */
    \CRM_Utils_Hook::alterReportVar('outputhandlers', self::$registered, $form);
    foreach (self::$registered as $candidate) {
      try {
        $outputHandler = new $candidate();
        if ($outputHandler->isOutputHandlerFor($form)) {
          $outputHandler->setForm($form);
          return $outputHandler;
        }
      }
      catch (\Exception $e) {
        // no ts() since this is a sysadmin-y message
        \Civi::log()->warning("Unable to use $candidate as an output handler. " . $e->getMessage());
      }
    }
    return NULL;
  }

  /**
   * Register an outputHandler to handle an output format.
   *
   * @param string $outputHandler
   *   The classname of a class that implements OutputHandlerInterface.
   */
  public function register(string $outputHandler) {
    // Use classname as index to (a) avoid duplicates and (b) make it easier
    // to unset/overwrite one via hook.
    self::$registered[$outputHandler] = $outputHandler;
  }

  /**
   * There are some handlers that were hard-coded in to the form before which
   * have now been moved to outputhandlers.
   */
  private static function registerBuiltins() {
    self::$singleton->register('\CRM_Report_OutputHandler_Print');
    self::$singleton->register('\CRM_Report_OutputHandler_Csv');
    self::$singleton->register('\CRM_Report_OutputHandler_Pdf');
  }

}
