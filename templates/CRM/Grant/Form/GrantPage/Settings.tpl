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
	<tr class="crm-grant-grantpage-settings-form-block-title"><td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_grant_app_page' field='title' id=$contributionPageID}{/if}</td><td>{$form.title.html}<br/>
            <span class="description">{ts}This title will be displayed at the top of the page.<br />Please use only alphanumeric, spaces, hyphens and dashes for Title.{/ts}</td>
	</tr>
	<tr class="crm-grant-grantpage-settings-form-block-grant_type_id"><td class="label">{$form.grant_type_id.label}</td><td>{$form.grant_type_id.html}<br />	
            <span class="description">{ts}Select the grant type to be assigned to grant applications made using this page.{/ts}</span></td>
	</tr>
	
        <td>&nbsp;</td>
        </td>
    </tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-intro_text">
	    <td class ="label">{$form.intro_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='intro_text' id=$contributionPageID}{/if} {help id="id-intro_msg"}</td><td>{$form.intro_text.html}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-footer_text">
	    <td class ="label">{$form.footer_text.label}<br /> {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='footer_text' id=$contributionPageID}{/if} {help id="id-footer_msg"}</td><td>{$form.footer_text.html}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-default_amount">
	    <td class ="label">{$form.default_amount.label}</td><td>{$form.default_amount.html} {help id="id-default_amount"}</td>
	</tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-start_date">
	    <td class ="label">{$form.start_date.label} {help id="id-start_date"}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=start_date}
	    </td>    
    </tr>
	<tr class="crm-contribution-contributionpage-settings-form-block-end_date">
	    <td class ="label">{$form.end_date.label}</td>
	    <td>
	        {include file="CRM/common/jcalendar.tpl" elementName=end_date}
	    </td>    
    </tr>
</table>
<table class="form-layout-compressed">
      		<tr class="crm-contribution-contributionpage-settings-form-block-is_active"><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}<br />
	{if $contributionPageID}
    		<span class="description">
        	{if $config->userSystem->is_drupal EQ '1'}
            	{ts}When your page is active, you can link people to the page by copying and pasting the following URL:{/ts}<br />
            	<strong>{crmURL a=true p='civicrm/grant/transact' q="reset=1&id=`$contributionPageID`"}</strong>
        	{elseif $config->userFramework EQ 'Joomla'}
            	{ts 1=$title}When your page is active, create front-end links to the contribution page using the Menu Manager. Select <strong>Administer CiviCRM &raquo; CiviContribute &raquo; Manage Contribution Pages</strong> and select <strong>%1</strong> for the contribution page.{/ts}
        	{/if}
		</span>
    	{/if}
	</td>
	</tr>    	
     	</table>
	 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

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
<script type="text/javascript">
 showHonor();
 {literal}
     function showHonor() {
        var checkbox = document.getElementsByName("honor_block_is_active");
        if (checkbox[0].checked) {
            document.getElementById("honor").style.display = "block";
        } else {
            document.getElementById("honor").style.display = "none";
        }  
     } 
 {/literal} 
</script>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}

