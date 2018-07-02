{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
  <i class="crm-i fa-exclamation-triangle"></i>
  <strong>{ts}Profile is not configured with Email address.{/ts}</strong>
</div>
{else}
<div class="form-item">
{include file="CRM/common/CMSUser.tpl"}
{include file="CRM/UF/Form/Block.tpl" fields=$fields}
{if $isCaptcha}
{include file='CRM/common/ReCAPTCHA.tpl'}
{/if}
</div>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{/if}
