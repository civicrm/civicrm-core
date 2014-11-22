<?php
namespace CRM\GitFootnote;

interface Filter {

  /**
   * Filter a commit message
   *
   * @return void
   */
  function filter(CommitMessage $message);
}
