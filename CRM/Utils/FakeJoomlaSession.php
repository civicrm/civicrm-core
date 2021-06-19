<?php
jimport('joomla.session.handler.interface');

class CRM_Utils_FakeJoomlaSession implements JSessionHandlerInterface {

  /**
   * This consumer's session ID.
   *
   * @var  string
   */
  protected $id = '';

  /**
   * Logical session name
   *
   * @var  string
   */
  protected $name;

  /**
   * @var  array
   */
  protected $data = array();

  /**
   * Constructor.
   *
   * @param string $name Session name
   */
  public function __construct($name = 'FAKE') {
    $this->name = $name;
  }

  /**
   * @inheritDoc
   */
  public function start() {
    if (empty($this->id)) {
      $this->setId($this->generateId());
    }

    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function regenerate($destroy = FALSE, $lifetime = NULL) {
    $this->id = $this->generateId();
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @inheritDoc
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @inheritDoc
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @inheritDoc
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @inheritDoc
   */
  public function save() {
  }

  /**
   * @inheritDoc
   */
  public function clear() {
    $this->data = array();
  }

  /**
   * @inheritDoc
   */
  public function isStarted() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  protected function generateId() {
    return hash('sha256', random_bytes(32));
  }

}
