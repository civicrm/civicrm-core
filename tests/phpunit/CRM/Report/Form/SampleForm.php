<?php

/**
 * Helper class to simulate a report form but allow us access to some protected
 * fields for tests.
 * There's an argument that you shouldn't be testing against internal fields
 * and that the functions in here should either be part of the real class or
 * there should be some other public output to test against, but for the
 * purposes of refactoring something like a big if/else block this is helpful
 * to ensure the same before and after, and it's easier to remove this test
 * class later if needed than stuff in the real class.
 */
class CRM_Report_Form_SampleForm extends CRM_Report_Form_Contribute_Summary {

  /**
   * Getter for addPaging.
   *
   * @return bool
   */
  public function getAddPaging():bool {
    return $this->addPaging;
  }

  /**
   * Thin wrapper around protected function for testing.
   * Just calls getActions.
   *
   * @param int $instanceId
   * @return array
   */
  public function getActionsForTesting($instanceId) {
    return $this->getActions($instanceId);
  }

  /**
   * This just allows setting outputMode directly for testing.
   * @param string $outputMode
   */
  public function setOutputModeForTesting(string $outputMode) {
    $this->_outputMode = $outputMode;
  }

}
