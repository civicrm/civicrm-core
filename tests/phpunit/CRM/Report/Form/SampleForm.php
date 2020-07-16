<?php

/**
 * Helper class to simulate a report form but allow us access to some protected
 * fields for tests.
 * There's an argument that you shouldn't be testing against internal fields
 * and that the getters in here should either be part of the real class or
 * there should be some other public output to test against, but for the
 * purposes of refactoring something like a big if/else block this is helpful
 * to ensure the same before and after, and it's easier to remove this test
 * class later if needed than stuff in the real class.
 */
class CRM_Report_Form_SampleForm extends CRM_Report_Form_Contribute_Summary {

  public function getOutputMode():string {
    return $this->_outputMode;
  }

  public function getAddPaging():bool {
    return $this->addPaging;
  }

}
