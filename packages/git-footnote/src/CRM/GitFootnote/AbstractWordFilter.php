<?php
namespace CRM\GitFootnote;

/**
 */
abstract class AbstractWordFilter implements Filter {

  public function filter(CommitMessage $message) {
    $filter = $this;
    $words = $this->parseWords($message->getMessage());
    $wordsLen = count($words);
    for ($i = 0; $i < $wordsLen; $i += 2) {
      $words[$i] = $this->filterWord($message, $words[$i]);
    }
    $message->setMessage(implode($words));
  }

  public function parseWords($messageText) {
    return preg_split('/([ ,;:\/\"\'\<\>!\?\.\(\)\[\]\r\n\t]+)/', $messageText, -1, PREG_SPLIT_DELIM_CAPTURE);
  }

  public abstract function filterWord(CommitMessage $message, $word);
}
