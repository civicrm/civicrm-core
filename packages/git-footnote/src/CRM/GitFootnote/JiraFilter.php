<?php
namespace CRM\GitFootnote;

class JiraFilter extends AbstractWordFilter {

  protected $wordPattern;
  protected $url;
  protected $jiraApi;
  protected $jiraCache;

  /**
   * @param string $wordPattern
   * @param string $url
   * @param Jira_Api|NULL $jiraApi
   */
  public function __construct($wordPattern, $url, $jiraApi = NULL) {
    $this->wordPattern = $wordPattern;
    $this->url = $url;
    $this->jiraApi = $jiraApi;
    $this->jiraCache = array();
  }

  public function filter(CommitMessage $message) {
    // If message is a single line with 1-2 real words and 1 JIRA issue,
    // then use filterShortMessage
    $trimmedMessage = trim($message->getMessage(), "\r\n\t ");
    if (substr_count($trimmedMessage, "\n") == 0) {
      $words = $this->parseWords($trimmedMessage);
      if (count($words) >= 1 && count($words) <= 3) {
        $issueKeys = array_filter($words, array($this, 'isIssueKey'));
        if (count($issueKeys) == 1) {
          $message->setMessage($this->filterShortMessage($words));
          return;
        }
      }
    }

    // Otherwise, use standard filter+footnotes
    parent::filter($message);
  }

  /**
   * Given a short commit message with single issue reference, add
   * the JIRA title to summary line.
   *
   * @param array $words
   * @return string
   */
  public function filterShortMessage($words) {
    $suffix = '';
    foreach ($words as $word) {
      if ($this->isIssueKey($word)) {
        $issue = $this->getIssue($word);
        if ($issue) {
          $suffix = ' - ' . $issue->getSummary() . "\n\n" . $this->createIssueUrl($word);
        }
        else {
          $suffix = ' - ' . $this->createIssueUrl($word);
        }
        break;
      }
    }
    return implode('', $words) . $suffix;
  }

  /**
   * Filter each word in the commit message separately.
   *
   * @param CommitMessage $message
   * @param $word
   * @return mixed
   */
  public function filterWord(CommitMessage $message, $word) {
    if ($this->isIssueKey($word)) {
      $issue = $this->getIssue($word);
      if ($issue) {
        $title = $word . ': ' . $issue->getSummary();
      } else {
        $title = $word . ':';
      }
      $url = $this->createIssueUrl($word);
      // CRM-13872 - Workaround to avoid duplicate footnotes when amending a commit message
      if (strpos($message->getMessage(), $url) === FALSE) {
        $message->addLinkNote($url, $title);
      }
    }
    return $word;
  }

  /**
   * @return Jira_Issue|NULL|FALSE (NULL if no service available; FALSE if invalid key)
   */
  protected function getIssue($key) {
    if (! $this->jiraApi) {
      return NULL;
    }
    if (! isset($this->jiraCache[$key])) {
      $this->jiraCache[$key] = FALSE;
      if (!preg_match('/^[A-Za-z0-9\-]+$/', $key)) {
        throw new \Exception("Invalid JIRA key: $key");
      }

      $walker = new \Jira_Issues_Walker($this->jiraApi);
      $walker->push("key = $key", "*navigable");
      foreach ($walker as $k => $issue) {
        $this->jiraCache[$key] = $issue;
      }
    }
    return $this->jiraCache[$key];
  }

  /**
   * @param string $issueKey
   * @return string
   */
  protected function createIssueUrl($issueKey) {
    return $this->url . '/browse/' . $issueKey;
  }

  protected function isIssueKey($word) {
    return preg_match($this->wordPattern, $word);
  }
}
