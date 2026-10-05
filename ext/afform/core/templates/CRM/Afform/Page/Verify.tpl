{if $verified}
  {if $success_message}
    {$success_message|purify}
  {else}
    {ts}Thank you. Your email was verified successfully and your submission was processed.{/ts}
  {/if}
{else}
  {ts}Sorry, unable to verify your submission.{/ts}
  {$error_message}
{/if}
