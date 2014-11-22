<?php
namespace CRM\GitFootnote;

class CommitMessage {
  /**
   * @var string
   */
  protected $message;

  /**
   * @var array(string)
   */
  protected $notes;

  public function __construct($message = '', $notes = array()) {
    $this->setMessage($message);
    $this->notes = $notes;
  }

  /**
   * Add a hyperlink note
   *
   * @param string $note
   * @return CommitMessage
   */
  public function addLinkNote($url, $text = NULL) {
    if (! isset($this->notes[$url])) {
      if ($text) {
        $this->notes[$url] = sprintf("%s\n  %s", $text, $url);
      } else {
        $this->notes[$url] = $url;
      }
    }
    return $this;
  }

  public function getNotes() {
    return $this->notes;
  }

  public function getMessage() {
    return $this->message;
  }

  public function setMessage($message) {
    $this->message = $message;
  }

  public function toString() {
    $s = rtrim($this->message, " \n");
    if (!empty($this->notes)) {
      $s .= "\n\n----------------------------------------\n";
      foreach ($this->notes as $note) {
        $s .= "* ";
        $s .= $note;
        $s .= "\n";
      }
    }
    return $s;
  }

  public function __toString() {
    return $this->toString();
  }
}
