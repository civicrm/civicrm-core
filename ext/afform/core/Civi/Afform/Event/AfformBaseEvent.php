<?php
namespace Civi\Afform\Event;

use Civi\Core\Event\GenericHookEvent;

abstract class AfformBaseEvent extends GenericHookEvent {

  /**
   * @var array
   *   The main 'Afform' record/configuration.
   */
  private $afform;

  /**
   * @var \Civi\Afform\FormDataModel
   */
  private $formDataModel;

  /**
   * @var \Civi\Api4\Generic\AbstractAction
   */
  private $apiRequest;

  /**
   * AfformBaseEvent constructor.
   * @param array $afform
   * @param \Civi\Afform\FormDataModel $formDataModel
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   */
  public function __construct(array $afform, \Civi\Afform\FormDataModel $formDataModel, \Civi\Api4\Generic\AbstractAction $apiRequest) {
    $this->afform = $afform;
    $this->formDataModel = $formDataModel;
    $this->apiRequest = $apiRequest;
  }

  public function getAfform(): array {
    return $this->afform;
  }

  /**
   * @return \Civi\Afform\FormDataModel
   */
  public function getFormDataModel(): \Civi\Afform\FormDataModel {
    return $this->formDataModel;
  }

  /**
   * @return \Civi\Api4\Generic\AbstractAction
   */
  public function getApiRequest() {
    return $this->apiRequest;
  }

}
