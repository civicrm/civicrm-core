{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

<div class="crm-block crm-grant-confirm-form-block">
    <div id="help">
        <p>{ts}Please verify the information below carefully. Click <strong>Go Back</strong> if you need to make changes.{/ts}
               {ts 1=$button}To complete this grant application, click the <strong>%1</strong> button below.{/ts}
        </p>
    </div>
    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
{if $default_amount_hidden and !$amount_requested}
    <div class="crm-group amount_display-group">
        <div class="header-dark">
            {ts}Grant Requested Amount{/ts}
        </div>
        <div class="display-block">
             {ts}Requested Amount{/ts}: <strong>{$default_amount_hidden|crmMoney}</strong>
        </div>
    </div>
{/if}

    {if $customPre}
       <fieldset class="label-left">
           {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
       </fieldset>
    {/if}
    {if $onbehalfProfile}
      <div class="crm-group onBehalf_display-group label-left crm-profile-view">
         {include file="CRM/UF/Form/Block.tpl" fields=$onbehalfProfile}
         <div class="crm-section organization_email-section">
            <div class="label">{ts}Organization Email{/ts}</div>
            <div class="content">{$onBehalfEmail}</div>
            <div class="clear"></div>
         </div>
      </div>
    {/if}
    {if $email}
        <div class="crm-group grantee_email-group">
            <div class="header-dark">
                {ts}Your Email{/ts}
            </div>
            <div class="crm-section no-label grantee_email-section">
             	<div class="content">{$email}</div>
            	<div class="clear"></div>
            </div>
        </div>
    {/if}
   	{if $customPost}
            <fieldset class="label-left">
                {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
            </fieldset>
    {/if}

    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
