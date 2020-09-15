<?php
namespace Civi\Report;

/**
 * Class OutputHandlerFactoryTest
 * @package Civi\Report
 * @group headless
 */
class OutputHandlerFactoryTest extends \CiviUnitTestCase {

  /**
   * Test builtin outputhandler creation
   */
  public function testCreateBuiltin() {
    $form = new \CRM_Report_Form_SampleForm();
    $form->setOutputModeForTesting('csv');
    $outputHandler = OutputHandlerFactory::singleton()->create($form);
    $this->assertEquals('CRM_Report_OutputHandler_Csv', get_class($outputHandler));

    $form->setOutputModeForTesting('pdf');
    $outputHandler = OutputHandlerFactory::singleton()->create($form);
    $this->assertEquals('CRM_Report_OutputHandler_Pdf', get_class($outputHandler));

    $form->setOutputModeForTesting('print');
    $outputHandler = OutputHandlerFactory::singleton()->create($form);
    $this->assertEquals('CRM_Report_OutputHandler_Print', get_class($outputHandler));
  }

  /**
   * Test when no suitable handler available for given report parameters.
   */
  public function testCreateNoMatch() {
    $form = new \CRM_Report_Form_SampleForm();
    $form->setOutputModeForTesting('something_nonexistent');
    $outputHandler = OutputHandlerFactory::singleton()->create($form);
    $this->assertNull($outputHandler);
  }

  /**
   * Test handler made available via hook.
   */
  public function testCreateWithHook() {
    \Civi::dispatcher()->addListener('hook_civicrm_alterReportVar', [$this, 'hookForAlterReportVar']);
    $form = new \CRM_Report_Form_SampleForm();
    $form->setOutputModeForTesting('sample');
    $outputHandler = OutputHandlerFactory::singleton()->create($form);
    $this->assertEquals('Civi\Report\SampleOutputHandler', get_class($outputHandler));
  }

  /**
   * Test actions modified by hook.
   */
  public function testAlterReportVarHookWithActions() {
    \Civi::dispatcher()->addListener('hook_civicrm_alterReportVar', [$this, 'hookForAlterReportVar']);
    $form = new \CRM_Report_Form_SampleForm();
    // NULL means no particular instance - running new report from template
    $actions = $form->getActionsForTesting(NULL);
    $this->assertEquals(['title' => 'Export Sample'], $actions['report_instance.sample']);
  }

  /**
   * This is the listener for hook_civicrm_alterReportVar
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   *   Should contain 'varType', 'var', and 'object' members corresponding
   *   to the hook parameters.
   */
  public function hookForAlterReportVar(\Civi\Core\Event\GenericHookEvent $e) {
    switch ($e->varType) {
      case 'outputhandlers':
        $e->var['\Civi\Report\SampleOutputHandler'] = '\Civi\Report\SampleOutputHandler';
        break;

      case 'actions':
        $e->var['report_instance.sample'] = ['title' => 'Export Sample'];
        break;
    }
  }

}
