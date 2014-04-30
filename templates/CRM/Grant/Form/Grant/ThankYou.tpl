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

<div class="crm-block crm-grant-thankyou-form-block">
    {if $thankyou_text}
        <div id="thankyou_text" class="crm-section thankyou_text-section">
            {$thankyou_text}
        </div>
    {/if}
    
    <div id="help">
          <div>{ts}Your grant application has been sent for processing. Please print this page for your records.{/ts}</div>
            {if $is_email_receipt}
                <div>
        {if $onBehalfEmail AND ($onBehalfEmail neq $email)}
          {ts 1=$email 2=$onBehalfEmail}An email receipt has also been sent to %1 and to %2.{/ts}
        {else}
          {ts 1=$email}An email receipt has also been sent to %1.{/ts}
        {/if}
    </div>
            {/if}
    </div>

    <div class="spacer"></div>
      {if $default_amount_hidden}
       <div class="crm-group amount_display-group">
        <div class="header-dark">
            {ts}Grant Requested Amount{/ts}
        </div>
        <div class="display-block">
             {ts}Requested Amount{/ts}: <strong>{$default_amount_hidden|crmMoney}</strong>
       </div>
      {/if} 
      {if $application_received_date}
       <div class="display-block">
            {ts}Date{/ts}: <strong>{$application_received_date|crmDate}</strong><br />
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
    {if $customPost}
            <fieldset class="label-left">
                {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
            </fieldset>
    {/if}

    <div id="thankyou_footer" class="grant_thankyou_footer-section">
        <p>
        {$thankyou_footer}
        </p>
    </div>
</div>
