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
    {icon icon="fa-info-circle"}{/icon}
    {if $success}
        {ts 1=$display_name 2=$email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    {else}
        {ts}Unfortunately we encountered a problem in processing your email verification. Please contact the site administrator.{/ts}
    {/if}
</div>

{if $success}
  <div id="thankyou_text" class="crm-section thankyou_text-section">
  {if $thankyou_text}
    {$thankyou_text}
  {else}
    <p><div class="bold">{ts}Thank you for signing the petition.{/ts}</div></p>
  {/if}
  </div>
  {if $is_share}
    {include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl" emailMode=false}
  {/if}
{/if}
