{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* error.tpl: Display page for fatal errors. Provides complete HTML doc.*}
{if $config->userFramework != 'WordPress'}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">

<head>
  <title>{ts}Error{/ts}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
{/if}
<style media="screen">
  body {
    background-color: #ffffcc;
  }
  .crm-fatal {
    display: inline-flex;
    gap: 1rem;
    padding: 1rem;
    font-size: 1.25rem;
    font-family: sans-serif;
    background-color: #ffffcc;
  }
  .crm-fatal .crm-fatal-exclamation {
    width: 1.75rem;
    height: 1.75rem;
    margin-top: 0.1rem;
    fill: darkred;
  }
  .crm-fatal .crm-fatal-messages {
    flex-direction: column;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
  }
  .crm-fatal a.btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;

    background-color: darkslategray;
    color: white;
    fill: white;
    text-decoration: none;
    padding: 0.5rem;
    border-radius: 0.5rem;

    font-size: 1rem;
  }
  .crm-fatal a.btn svg {
    width: 1rem;
    height: 1rem;
  }
  .crm-fatal a.btn:hover {
    background-color: black;
  }
</style>
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
  <div class="crm-fatal">
    <svg class="crm-fatal-exclamation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>

    <div class="crm-fatal-messages">
      <div class="crm-section crm-error-message">
        {$message|escape}
      </div>
      {if !empty($error.message) && $message != $error.message}
        <hr style="solid 1px" />
        <div class="crm-section crm-error-message">{$error.message|escape}</div>
      {/if}
      {if (!empty($code) || !empty($mysql_code) || !empty($errorDetails)) AND $config->debug}
        <details class="crm-accordion-bold crm-fatal-error-details-block">
         <summary>
          {ts}Error Details{/ts}
         </summary>
         <div class="crm-accordion-body">
            {if !empty($code)}
                <div class="crm-section">{ts}Error Code:{/ts} {$code|purify}</div>
            {/if}
            {if !empty($mysql_code)}
                <div class="crm-section">{ts}Database Error Code:{/ts} {$mysql_code|purify}</div>
            {/if}
            {if !empty($errorDetails)}
                <div class="crm-section">{ts}Additional Details:{/ts} {$errorDetails|purify}</div>
            {/if}
         </div>
        </details>
      {/if}
      <div class="crm-section crm-return-to-home">
        <a class="btn" href="{$config->userFrameworkBaseURL}" title="{ts escape='htmlattribute'}Main Menu{/ts}">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M341.8 72.6C329.5 61.2 310.5 61.2 298.3 72.6L74.3 280.6C64.7 289.6 61.5 303.5 66.3 315.7C71.1 327.9 82.8 336 96 336L112 336L112 512C112 547.3 140.7 576 176 576L464 576C499.3 576 528 547.3 528 512L528 336L544 336C557.2 336 569 327.9 573.8 315.7C578.6 303.5 575.4 289.5 565.8 280.6L341.8 72.6zM304 384L336 384C362.5 384 384 405.5 384 432L384 528L256 528L256 432C256 405.5 277.5 384 304 384z"/></svg>
          {ts}Return home{/ts}
        </a>
      </div>
    </div>
  </div>
</div> {* end crm-container div *}
{if $config->userFramework != 'WordPress'}
</body>
</html>
{/if}
