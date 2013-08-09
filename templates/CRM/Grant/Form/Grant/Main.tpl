{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
{include file="CRM/common/TrackingFields.tpl"}

{capture assign='reqMark'}<span class="marker" title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-grant-main-form-block">
  <div id="intro_text" class="crm-section intro_text-section">
      {$intro_text}
  </div>
      {assign var=n value=email-$bltID}
      <div class="crm-section {$form.$n.name}-section">
        <div class="label">{$form.$n.label}</div>
        <div class="content">
          {$form.$n.html}
        </div>
        <div class="clear"></div>
      </div>

  <div class="crm-section default_amount-section">
  {if $defaultAmount}
        <div class="label">Requested Amount</div>
       	      <div class="content">
   	      	   {$defaultAmount|crmMoney}
   	       </div>	
         <div class="clear"></div>
      </div> 
   {/if}

    {include file="CRM/common/CMSUser.tpl"}

    <div class="crm-group custom_pre_profile-group">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
    </div>

    <div class="crm-group custom_post_profile-group">
    {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
    </div>

    {if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
    {/if}
    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
    {if $footer_text}
      <div id="footer_text" class="crm-section grant_footer_text-section">
       <p>{$footer_text}</p>
      </div>
    {/if}
</div>
