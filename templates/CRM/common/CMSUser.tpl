{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{if $showCMS }{*true if is_cms_user field is set *}
   <fieldset class="crm-group crm_user-group">
      <div class="messages help cms_user_help-section">
   {if !$isCMS}
      {ts}If you would like to create an account on this site, check the box below and enter a Username{/ts}{if $form.cms_pass} {ts}and a password{/ts}.{/if}
   {else}
      {ts}Please enter a Username to create an account.{/ts}
   {/if}
   {ts 1=$loginURL}If you already have an account <a href='%1'>please login</a> before completing this form.{/ts}
      </div>
      <div>{$form.cms_create_account.html} {$form.cms_create_account.label}</div>
      <div id="details" class="crm_user_signup-section">

         <div class="form-layout-compressed">
           <div class="crm-section cms_name-section">
             <div class="label">
               <label for="cms_name">{$form.cms_name.label}</label>
             </div>
             <div class="content">
               {$form.cms_name.html} <a id="checkavailability" href="#" onClick="return false;">{ts}<strong>Check Availability</strong>{/ts}</a>
               <span id="msgbox" style="display:none"></span><br />
               <span class="description">{ts}Punctuation is not allowed in a Username with the exception of periods, hyphens and underscores.{/ts}</span>
             </div>
           </div>

           {if $form.cms_pass}
           <div class="crm-section cms_pass-section">
             <div class="label">
               <label for="cms_pass">{$form.cms_pass.label}</label>
             </div>
             <div class="content">
               {$form.cms_pass.html}
             </div>
             <div class="clear"></div>
             <div class="label">
               <label for="crm_confirm_pass-section">{$form.cms_confirm_pass.label}</label>
             </div>
             <div class="content">
               {$form.cms_confirm_pass.html}<br/>
               <span class="description">{ts}Provide a password for the new account in both fields.{/ts}</span>
             </div>
           </div>
           {/if}
         </div>

     </div>
   </fieldset>

   {literal}
   <script type="text/javascript">
   {/literal}
   {if !$isCMS}
      {literal}
      if ( document.getElementsByName("cms_create_account")[0].checked ) {
   cj('#details').show();
      } else {
   cj('#details').hide();
      }
      {/literal}
   {/if}
   {literal}
   function showMessage( frm )
   {
      var cId = {/literal}'{$cId}'{literal};
      if ( cId ) {
   alert('{/literal}{ts escape="js"}You are logged-in user{/ts}{literal}');
   frm.checked = false;
      } else {
   var siteName = {/literal}'{$config->userFrameworkBaseURL}'{literal};
   alert('{/literal}{ts escape="js"}Please login if you have an account on this site with the link{/ts}{literal} ' + siteName  );
      }
   }
   {/literal}
   {include file="CRM/common/checkUsernameAvailable.tpl"}
   {literal}
   </script>
   {/literal}
   {if !$isCMS}
      {include file="CRM/common/showHideByFieldValue.tpl"
      trigger_field_id    ="cms_create_account"
      trigger_value       =""
      target_element_id   ="details"
      target_element_type ="block"
      field_type          ="radio"
      invert              = 0
      }
   {/if}
{/if}
