{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays account creation and supporter profile form (step 1 in creating a personal campaign page as well as Update Contact info). *}
{if $action EQ 1}
<div class="help">
        {ts}Creating your own fundraising page is simple. Fill in some basic information below, which will allow you to manage your page and invite friends to make a contribution. Then click 'Continue' to personalize and announce your page.{/ts}
</div>
{/if}

{if $profileDisplay}
<div class="messages status no-popup">
  <i class="crm-i fa-exclamation-triangle" role="img" aria-hidden="true"></i>
  <strong>{ts}Profile is not configured with Email address.{/ts}</strong>
</div>
{else}
<div class="form-item">
{include file="CRM/common/CMSUser.tpl"}
{include file="CRM/UF/Form/Block.tpl" fields=$fields prefix=false hideFieldset=false}
</div>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/if}
