<?php
namespace Civi\Report;

/**
 * Helper class to simulate an OutputHandler that an extension might provide.
 */
class SampleOutputHandler extends OutputHandlerBase {

  /**
   * Are we a suitable output handler for the given parameters.
   *
   * @param \CRM_Report_Form $form
   *
   * @return bool
   */
  public function isOutputHandlerFor(\CRM_Report_Form $form):bool {
    return $form->getOutputMode() === 'sample';
  }

}
