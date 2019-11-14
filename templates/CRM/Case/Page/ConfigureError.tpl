{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviCase Configuration Help - displayed when component is enabled but not yet configured. *}

{capture assign=docLink}{docURL page="user/case-management/set-up" text="CiviCase Setup documentation"}{/capture}

<div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
      <strong>{ts}You need to setup and load Case and Activity configuration files before you can begin using the CiviCase component.{/ts}</strong>
      {ts 1=$docLink}Refer to the %1 to learn about this process.{/ts}
</div>
