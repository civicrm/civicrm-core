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

namespace Civi\Test;

use Civi\WorkflowMessage\WorkflowMessage;

trait WorkflowMessageTestTrait {

  abstract public function getWorkflowClass(): string;

  public function getWorkflowName(): string {
    $class = $this->getWorkflowClass();
    return $class::WORKFLOW;
  }

  /**
   * @return \Civi\Api4\Generic\AbstractGetAction
   * @throws \CRM_Core_Exception
   */
  protected function findExamples(): \Civi\Api4\Generic\AbstractGetAction {
    return \Civi\Api4\ExampleData::get(0)
      ->setSelect(['name', 'title', 'tags', 'data', 'asserts'])
      ->addWhere('name', 'LIKE', 'workflow/' . $this->getWorkflowName() . '/%')
      ->addWhere('tags', 'CONTAINS', 'phpunit');
  }

  /**
   * @param array $exampleProps
   * @param string $exampleName
   * @throws \Civi\WorkflowMessage\Exception\WorkflowMessageException
   */
  protected function assertConstructorEquivalence(array $exampleProps, $exampleName = ''): void {
    $class = $this->getWorkflowClass();
    $instances = [];
    $instances["factory_$exampleName"] = WorkflowMessage::create($this->getWorkflowName(), $exampleProps);
    $instances["class_$exampleName"] = new $class($exampleProps);

    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $refInstance */
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance */

    $refName = $refInstance = NULL;
    $comparisons = 0;
    foreach ($instances as $cmpName => $cmpInstance) {
      if ($refName === NULL) {
        $refName = $cmpName;
        $refInstance = $cmpInstance;
        continue;
      }

      $this->assertSameWorkflowMessage($refInstance, $cmpInstance, "Compare $refName vs $cmpName: ");
      $comparisons++;
    }
    $this->assertEquals(1, $comparisons);
  }

  /**
   * @param \Civi\WorkflowMessage\WorkflowMessageInterface $refInstance
   * @param \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance
   * @param string|null $prefix
   */
  protected function assertSameWorkflowMessage(\Civi\WorkflowMessage\WorkflowMessageInterface $refInstance, \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance, ?string $prefix = NULL): void {
    if ($prefix === NULL) {
      $prefix = sprintf('[%s] ', $this->getWorkflowName());
    }
    $this->assertEquals($refInstance->getWorkflowName(), $cmpInstance->getWorkflowName(), "{$prefix}Should have same workflow name)");
    $this->assertEquals($refInstance->export('tplParams'), $cmpInstance->export('tplParams'), "{$prefix}Should have same export(tplParams)");
    $this->assertEquals($refInstance->export('tokenContext'), $cmpInstance->export('tokenContext'), "{$prefix}should have same export(tokenContext)");
    $this->assertEquals($refInstance->export('envelope'), $cmpInstance->export('envelope'), "{$prefix}Should have same export(envelope)");
    $refExportAll = WorkflowMessage::exportAll($refInstance);
    $cmpExportAll = WorkflowMessage::exportAll($cmpInstance);
    $this->assertEquals($refExportAll, $cmpExportAll, "{$prefix}Should have same exportAll()");
  }

}
