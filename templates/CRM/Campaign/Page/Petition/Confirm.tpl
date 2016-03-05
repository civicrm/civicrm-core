{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;
    {if $success}
        {ts 1=$display_name 2=$email}<strong>%1 - your email address '%2' has been successfully verified.</strong>{/ts}
    {else}
        {ts}Oops. We encountered a problem in processing your email verification. Please contact the site administrator.{/ts}
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
    {include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl"}
  {/if}
{/if}
