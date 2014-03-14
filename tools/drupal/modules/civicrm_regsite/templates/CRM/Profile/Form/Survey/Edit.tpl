{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
{* Profile forms when embedded in CMS account create (mode=1) or cms account edit (mode=8) or civicrm/profile (mode=4) pages *}
{if $context neq 'dialog'}
<script type="text/javascript" src="{$config->resourceBase}js/Common.js"></script>
{/if}
{if ! empty( $fields )}
{* Wrap in crm-container div so crm styles are used.*}
{* Replace div id with this logic if you want CMS account create and CMS edit to use CMS theme styles: id="{if $mode eq 4}crm-container{else}crm-profile-block{/if}" *}
<div id="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
    {if $isDuplicate and ( ($action eq 1 and $mode eq 4 ) or ($action eq 2) or ($action eq 8192) ) }
        <div class="crm-submit-buttons"> 
             <span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>
        </div>
    {/if}
    {if $mode eq 1 || $activeComponent neq "CiviCRM"}
        {include file="CRM/Form/body.tpl"}
    {/if}
    {strip}
    {if $help_pre && $action neq 4}
        <div class="messages help">{$help_pre}</div>
    {/if}

    {include file="CRM/common/CMSUser.tpl"}

    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {foreach from=$fields item=field key=fieldName}
        {assign var="profileID" value=$field.group_id}
        {assign var=n value=$field.name}
        {if $form.$n}
            {if $field.groupTitle != $fieldset}
                {if $fieldset != $zeroField}
                   {if $groupHelpPost}
                      <div class="messages help">{$groupHelpPost}</div>
                   {/if}
                   {if $mode neq 8 && $mode neq 4}
                        </div><!-- end form-layout-compressed-div -->
                      </fieldset>
                      </div>
                   {/if}
                {/if}

                {if $mode neq 8 && $mode neq 4}
                      <div {if $context neq 'dialog'}id="profilewrap{$field.group_id}"{/if}>
                      <fieldset><legend>{$field.groupTitle}</legend>
                {/if}
                {assign var=fieldset  value=`$field.groupTitle`}
                {assign var=groupHelpPost  value=`$field.groupHelpPost`}
                {if $field.groupHelpPre}
                    <div class="messages help">{$field.groupHelpPre}</div>
                {/if}
                <div class="form-layout-compressed">
            {/if}

            {if $field.options_per_line}
            	<div class="crm-section editrow_{$n}-section form-item" id="editrow-{$n}">
                    <div class="label">{$form.$n.label}</div>
                    <div class="content edit-value">
                	    {assign var="count" value="1"}
                        {strip}
                        <table class="form-layout-compressed">
                        <tr>
                          {* sort by fails for option per line. Added a variable to iterate through the element array*}
                          {assign var="index" value="1"}
                          {foreach name=outer key=key item=item from=$form.$n}
                          {if $index < 10}
                              {assign var="index" value=`$index+1`}
                          {else}
                              <td class="labels font-light">{$form.$n.$key.html}</td>
                              {if $count == $field.options_per_line}
                                  </tr>
                                  <tr>
                                   {assign var="count" value="1"}
                              {else}
                        	   {assign var="count" value=`$count+1`}
                              {/if}
                          {/if}
                          {/foreach}
                        </tr>
                        </table>
                        {/strip}
                    </div>
                    <div class="clear"></div>
                </div>{* end of main edit section div*}
        	{else}
                <div id="editrow-{$n}" class="crm-section editrow_{$n}-section form-item">
                   <div class="label">{$form.$n.label}</div>
                   <div class="edit-value content">
                       {if $n|substr:0:3 eq 'im-'}
                         {assign var="provider" value=$n|cat:"-provider_id"}
                         {$form.$provider.html}&nbsp;
                       {else if $n|substr:0:4 eq 'url-'}
                         {assign var="websiteType" value=$n|cat:"-website_type_id"}
                         {$form.$websiteType.html}&nbsp;
                       {/if}
                       {if $n eq 'email_greeting' or  $n eq 'postal_greeting' or $n eq 'addressee'}
                            {include file="CRM/Profile/Form/GreetingType.tpl"}  
                       {elseif ( $n eq 'group' && $form.group ) || ( $n eq 'tag' && $form.tag )}
            				{include file="CRM/Contact/Form/Edit/TagsAndGroups.tpl" type=$n}
                       {elseif ( $form.$n.name eq 'image_URL' )}
            	            {$form.$n.html}
                		    {if $imageURL}
                 	 	        <div class="crm-section contact_image-section">
                 	 	            <div class="content">
                 	 	                {include file="CRM/Contact/Page/ContactImage.tpl"}
                 	 	            </div>
                 	 	        </div>
                 	        {/if}
            	       {else}
                           {if ( $field.data_type eq 'Date' or
                                      ( ( ( $n eq 'birth_date' ) or ( $n eq 'deceased_date' ) ) ) ) and $field.is_view neq 1 }
                              {include file="CRM/common/jcalendar.tpl" elementName=$n}  
               		       {else}       
                              {$form.$n.html}
                           {/if}
            		       {if $field.html_type eq 'Autocomplete-Select'}
                                {include file="CRM/Custom/Form/AutoComplete.tpl" element_name = $n}
            			   {/if}
                      {/if}
                   </div>
                   <div class="clear"></div>
                </div>

                {if $form.$n.type eq 'file'}
        	        <div class="crm-section file_displayURL-section file_displayURL{$n}-section"><div class="content">{$customFiles.$n.displayURL}</div></div>
        	        <div class="crm-section file_deleteURL-section file_deleteURL{$n}-section"><div class="content">{$customFiles.$n.deleteURL}</div></div>
                {/if} 
        	{/if}

            {* Show explanatory text for field if not in 'view' mode *}
            {if $field.help_post && $action neq 4 && $form.$n.html}
                <div class="crm-section helprow-{$n}-section" id="helprow-{$n}">
                    <div class="content description">{$field.help_post}</div>
                </div>
            {/if}
        {/if}{* end of main if field name if *}        
    {/foreach}
    </div><!-- end form-layout-compressed for last profile --> {* closing main form layout div when all the fields are built*}
    
    
    {if $isCaptcha && ( $mode eq 8 || $mode eq 4 || $mode eq 1 ) }
        {include file='CRM/common/ReCAPTCHA.tpl'}
        <script type="text/javascript">cj('.recaptcha_label').attr('width', '140px');</script>
    {/if}

    {if $field.groupHelpPost}
        <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode neq 8 && $mode neq 4}
        </fieldset>
        </div>
    {/if}

    {foreach from=$event_survey_groupTree item=cd_edit key=group_id}
      {foreach from=$cd_edit.fields item=element key=field_id}
        <table class="form-layout-compressed">
        {include file="CRM/Custom/Form/CustomField.tpl"}
        </table>
      {/foreach}
    {/foreach}

    {if ($action eq 1 and $mode eq 4 ) or ($action eq 2) or ($action eq 8192)}
    <div class="crm-submit-buttons"> 
         {include file="CRM/common/formButtons.tpl"}{if $isDuplicate}<span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>{/if}
    </div>
    {/if}
     {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
    {/strip}

</div> {* end crm-container div *}

<script type="text/javascript">
  {if $drupalCms}
  {literal}
    if ( document.getElementsByName("cms_create_account")[0].checked ) {
       show('details');
    } else {
       hide('details');
    }
  {/literal}
  {/if}
</script>
{/if} {* fields array is not empty *}

{if $drupalCms}
{include file="CRM/common/showHideByFieldValue.tpl" 
trigger_field_id    ="create_account"
trigger_value       =""
target_element_id   ="details" 
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{elseif $statusMessage}
    <div class="messages status">
        <div class="icon inform-icon"></div>
        {$statusMessage}
    </div>
{/if}
{literal}
<script type="text/javascript">
    
cj(document).ready(function(){ 
	cj('#selector tr:even').addClass('odd-row ');
	cj('#selector tr:odd ').addClass('even-row');
});
{/literal}
{if $context eq 'dialog'}
{literal}
    var options = { 
        beforeSubmit:  showRequest  // pre-submit callback  
    }; 
    
    // bind form using 'ajaxForm'
    cj('#Edit').ajaxForm( options );

   	// pre-submit callback 
    function showRequest(formData, jqForm, options) { 
        // formData is an array; here we use $.param to convert it to a string to display it 
        // but the form plugin does this for you automatically when it submits the data 
        var queryString = cj.param(formData); 
        queryString = queryString + '&snippet=5&gid=' + {/literal}"{$profileID}"{literal};
        var postUrl = {/literal}"{crmURL p='civicrm/profile/create' h=0 }"{literal}; 
        var response = cj.ajax({
           type: "POST",
           url: postUrl,
           async: false,
           data: queryString,
           dataType: "json",
           success: function( response ) {
               if ( response.newContactSuccess ) {
                   cj("#contact").val( response.sortName ).focus( );
		   if ( typeof(allowMultiClient) != "undefined" ) {
		      if ( allowMultiClient ) {
	              	 var newToken = '{"name":"'+response.sortName+'","id":"'+response.contactID+'"},';
		      	 cj('ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).remove();
		      	 addMultiClientOption(newToken);
		      }
		   }
                   cj("input[name=contact_select_id]").val( response.contactID );
                   cj("#contact-success").show( );
                   cj("#contact-dialog").dialog("close");
               }
           }
         }).responseText;

         cj("#contact-dialog").html( response );

        // here we could return false to prevent the form from being submitted; 
        // returning anything other than false will allow the form submit to continue 
        return false; 
    }

{/literal}    
{/if}
{literal}
</script>
{/literal}

