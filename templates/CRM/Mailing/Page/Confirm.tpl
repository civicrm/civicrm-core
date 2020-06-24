{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
{if $success}
      {ts 1=$display_name 2=$email 3=$group}<strong>%1 (%2)</strong> has been successfully subscribed to the <strong>%3</strong> mailing list.{/ts}
{else}
      {ts}Oops. We encountered a problem in processing your subscription confirmation. Please contact the site administrator.{/ts}
{/if}
</div>
