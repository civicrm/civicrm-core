{if $verified}
  {ts}Thank you. Your email was verified successfully and your submission was processed.{/ts}
{else}
  {ts}Sorry, unable to verify your submission.{/ts}
  {$error_message}
{/if}
