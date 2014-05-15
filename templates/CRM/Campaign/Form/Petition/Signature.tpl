{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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


<script>
{literal}

  if (typeof(cj) === 'undefined') cj = jQuery;
{/literal}
</script>

<div id="intro" class="crm-section">{$petition.instructions}</div>
<div class="crm-block crm-petition-form-block">

{if $duplicate == "confirmed"}
<p>
{ts}You have already signed this petition.{/ts}
</p>
{/if}
{if $duplicate == "unconfirmed"}
<p>{ts}You have already signed this petition but you still <b>need to verify your email address</b>.</br>
Please check your email inbox for the confirmation email. If you don't find it, verify if it isn't in your spam folder.{/ts}
{/if}
{if $duplicate}
<p>{ts}Thank you for your support.{/ts}</p>
{include file="CRM/Campaign/Page/Petition/SocialNetwork.tpl" petition_id=$survey_id petitionTitle=$petitionTitle}
{else}
  <div class="crm-section crm-petition-contact-profile">
    {include file="CRM/Campaign/Form/Petition/Block.tpl" fields=$petitionContactProfile}
  </div>

  <div class="crm-section crm-petition-activity-profile">
    {include file="CRM/Campaign/Form/Petition/Block.tpl" fields=$petitionActivityProfile}
  </div>

  {if $isCaptcha}
      {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
{/if}

</div>
