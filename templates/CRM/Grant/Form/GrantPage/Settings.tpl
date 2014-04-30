{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
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
{if $action eq 8}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
          {ts}WARNING: Are you sure you want to Delete the selected Grant Application Page? A Delete operation cannot be undone. Do you want to continue?{/ts}
      </div>
<div class="form-item">
    {include file="CRM/common/formButtons.tpl"}
</div>
{else}
<div class="crm-block crm-form-block crm-grant-grantpage-settings-form-block">
<div id="help">
    {if $action eq 0}
        <p>{ts}This is the first step in creating a new online Grant Application Page. You can create one or more different Grant Application Pages for different purposes, audiences, campaigns, etc. Each page can have it's own introductory message, pre-configured default amounts, custom data collection fields, etc.{/ts}</p>
        <p>{ts}In this step, you will configure the page title, grant type (emergency, family support, etc.), default amount, and introductory message. You will be able to go back and modify all aspects of this page at any time after completing the setup wizard.{/ts}</p>
    {else}
        {ts}Use this form to edit the page title, grant type (e.g. emergency, family support, etc.), default amount, introduction, and status (active/inactive) for this online grant application page.{/ts}
    {/if}
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div> 
	<table class="form-layout-compressed">
	<tr class="crm-grant-grantpage-settings-form-block-title"><td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='title' id=$grantApplicationPageID}{/if}</td><td>{$form.title.html}<br/>
            <span class="description">{ts}This title will be displayed at the top of the page.<br />Please use only alphanumeric, spaces, hyphens and dashes for Title.{/ts}</td>
	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-grant_type_id"><td class="label">{$form.grant_type_id.label}</td><td>{$form.grant_type_id.html}<br />	
            <span class="description">{ts}Select the grant type to be assigned to grant applications made using this page.{/ts}</span></td>
	</tr>
	
        <tr class="crm-contribution-contributionpage-settings-form-block-is_organization"><td>&nbsp;</td><td>{$form.is_organization.html} {$form.is_organization.label} {help id="id-is_organization"}</td></tr>
  	<tr id="for_org_option" class="crm-contribution-form-block-is_organization">
          <td>&nbsp;</td>
          <td>
            <table class="form-layout-compressed">
            <tr class="crm-contribution-for_organization_help">
                <td class="description" colspan="2">
                    {capture assign="profileURL"}{crmURL p='civicrm/admin/uf/group' q='reset=1'}{/capture}
                    {if $invalidProfiles}
                      {ts 1=$profileURL}You must <a href="%1">configure a valid organization profile</a> in order to allow individuals to contribute on behalf of an organization. Valid profiles include Contact and / or Organization fields, and may include Contribution and Membership fields.{/ts}
                    {else}
                      {ts 1=$profileURL}To change the organization data collected use the "On Behalf Of Organization" profile (<a href="%1">Administer > Customize Data and Screens > Profiles</a>).{/ts}
                    {/if}
                </td>
            </tr>
           {if !$invalidProfiles}
              <tr class="crm-contribution-onbehalf_profile_id">
                <td class="label">{$form.onbehalf_profile_id.label}</td>
                <td>{$form.onbehalf_profile_id.html}</td>
              </tr>
           {/if}
            <tr id="for_org_text" class="crm-contribution-contributionpage-settings-form-block-for_organization">
                <td class="label">{$form.for_organization.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='for_organization' id=$contributionPageID}{/if}</td>
                <td>{$form.for_organization.html}<br />
                    <span class="description">{ts}Text displayed next to the checkbox on the contribution form.{/ts}</span>
                </td>
            </tr>
            <tr class="crm-contribution-contributionpage-settings-form-block-is_for_organization">
                <td>&nbsp;</td>
                <td>{$form.is_for_organization.html}<br />
                    <span class="description">{ts}Check 'Required' to force ALL users to contribute/signup on behalf of an organization.{/ts}</span>
                </td>
            </tr>
            </table>
          </td>
    	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-intro_text">
	    <td class ="label">{$form.intro_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='intro_text' id=$grantApplicationPageID}{/if} {help id="id-intro_msg"}</td><td>{$form.intro_text.html}</td>
	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-footer_text">
	    <td class ="label">{$form.footer_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='footer_text' id=$grantApplicationPageID}{/if} {help id="id-footer_msg"}</td><td>{$form.footer_text.html}</td>
	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-default_amount">
	    <td class ="label">{$form.default_amount.label}</td><td>{$form.default_amount.html} {help id="id-default_amount"}</td>
	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-start_date">
	    <td class ="label">{$form.start_date.label} {help id="id-start_date"}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=start_date}
	    </td>    
    </tr>
	<tr class="crm-grant-grantpage-settings-form-block-end_date">
	    <td class ="label">{$form.end_date.label}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=end_date}
	    </td>    
    </tr>
</table>
<table class="form-layout-compressed">
      		<tr class="crm-grant-grantpage-settings-form-block-is_active"><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}<br />
	{if $grantPageID}
    		<span class="description">
        	{if $config->userSystem->is_drupal EQ '1'}
            	{ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
            	<strong>{crmURL a=true p='civicrm/grant/transact' q="reset=1&id=`$grantApplicationPageID`"}</strong>
        	{elseif $config->userFramework EQ 'Joomla'}
            	{ts 1=$title}When your page is active, create front-end links to the grant application page using the Menu Manager. Select <strong>Grants &raquo; Dashboard</strong> and select <strong>%1</strong> for the grant application page.{/ts}
        	{/if}
		</span>
    	{/if}
	</td>
	</tr>    	
     	</table>
	 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_text"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}

{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_organization"
    trigger_value       = 1
    target_element_id   ="for_org_option"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
