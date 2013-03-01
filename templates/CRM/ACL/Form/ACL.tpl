{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
{* this template is used for adding/editing ACL  *}
<h3>{if $action eq 1}{ts}New ACL{/ts}{elseif $action eq 2}{ts}Edit ACL{/ts}{else}{ts}Delete ACL{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-acl-form-block">
{if $action eq 8}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;
        {ts}WARNING: Delete will remove this permission from the specified ACL Role.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   <table class="form-layout-compressed">
     <tr class="crm-acl-form-block-name">
        <td class="label">{$form.name.label}</td>
        <td>{$form.name.html}<br />
           <span class="description">{ts}Enter a descriptive name for this permission (e.g. 'Edit Advisory Board Contacts').{/ts}</span>
        </td>
     </tr>
     <tr class="crm-acl-form-block-entity_id">
        <td class="label">{$form.entity_id.label}</td>
        <td>{$form.entity_id.html}<br />
            <span class="description">{ts}Select a Role to assign (grant) this permission to. Select the special role 'Everyone' if you want to grant this permission to ALL users. 'Everyone' includes anonymous (i.e. not logged in) users. Select the special role 'Authenticated' if you want to grant it to any logged in user.{/ts}</span>
        </td>
     </tr>
     <tr class="crm-acl-form-block-operation">
         <td class="label">{$form.operation.label}</td>
         <td>{$form.operation.html}<br />
            <span class="description">{ts}What type of operation (action) is being permitted?{/ts}</span>
         </td>
     </tr>
     <tr class="crm-acl-form-block-object_type">
         <td class="label">{$form.object_type.label}</td>
         <td>{$form.object_type.html}</td>
     </tr>
     <tr class="crm-acl-form-block-description">
        <td class="{$form.object_type.name}">&nbsp;</dt><td class="description">{ts}Select the type of data this ACL operates on.{/ts}<br />
        {if $config->userSystem->is_drupal EQ '1'}
           <div class="status description">{ts}IMPORTANT: The Drupal permissions for 'access all custom data' and 'profile listings and forms' override and disable specific ACL settings for custom field groups and profiles respectively. Do not enable those Drupal permissions for a Drupal role if you want to use CiviCRM ACL's to control access.{/ts}</div></td>
        {/if}
     </tr>
  </table>
  <div id="id-group-acl">
   <table  class="form-layout-compressed">
     <tr class="crm-acl-form-block-group_id">
         <td class="label">{$form.group_id.label}</td>
         <td>{$form.group_id.html}<br />
         <span class="description">{ts}Select a specific group of contacts, OR apply this permission to ALL groups.{/ts}</span>
         </td>
     </tr>
   </table>
  </div>
  <div id="id-profile-acl">
   <table class="form-layout-compressed" >
     <tr class="crm-acl-form-block-uf_group_id">
        <td class="label">{$form.uf_group_id.label}</td>
        <td>{$form.uf_group_id.html}<br />
        <span class="description">{ts}Select a specific profile, OR apply this permission to ALL profiles.{/ts}</span>
        </td>
     </tr>
   </table>
    <div class="status message">{ts}NOTE: Profile ACL operations affect which modes a profile can be used in (i.e. Create a new contact, Edit your own contact record, View a contact record, etc.). The Create operation is required for profiles embedded in online contribution or event registration forms. None of the operations for Profile ACLs grant access to administration of profiles.{/ts}</div>
  </div>
  <div id="id-custom-acl">
   <table class="form-layout-compressed">
     <tr class="crm-acl-form-block-custom_group_id">
         <td class="label">{$form.custom_group_id.label}</td>
         <td>{$form.custom_group_id.html}<br />
         <span class="description">{ts}Select a specific group of custom fields, OR apply this permission to ALL custom fields.{/ts}</span>
         </td>
     </tr>
   </table>
  <div class="status message">{ts}NOTE: For Custom Data ACLs, the 'View' and 'Edit' operations currently do the same thing. Either option grants the right to view AND / OR edit custom data fields (in all groups, or in a specific custom data group). Neither option grants access to administration of custom data fields.{/ts}</div>
  </div>
  <div id="id-event-acl">
   <table  class="form-layout-compressed">
     <tr class="crm-acl-form-block-event_id">
         <td class="label">{$form.event_id.label}</td>
         <td>{$form.event_id.html}<br />
         <span class="description">{ts}Select an event, OR apply this permission to ALL events.{/ts}</span>
         </td>
     </tr>
   </table>
    <div class="status message">{ts}NOTE: For Event ACLs, the 'View' operation allows access to the event information screen. "Edit" allows users to register for the event if online registration is enabled.{/ts}<br /> 
    {if $config->userSystem->is_drupal EQ '1'}
    {ts}Please remember that Drupal's "register for events" permission overrides CiviCRM's control over event information access.{/ts}
    {/if}
    </div>
  </div>
   <table  class="form-layout-compressed">
     <tr class="crm-acl-form-block-is_active">
         <td class="label">{$form.is_active.label}</td>
         <td>{$form.is_active.html}</td>
     </tr>
   </table>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{include file="CRM/common/showHide.tpl"}
{literal}
<script type="text/javascript">
 showObjectSelect( );
 function showObjectSelect( ) {
    var ot = document.getElementsByName('object_type');
    for (var i = 0; i < ot.length; i++) {
        if ( ot[i].checked ) {
            switch(ot[i].value) {
                case "1":
                    cj('#id-group-acl').show();
                    cj('#id-profile-acl').hide();
                    cj('#id-custom-acl').hide();
                    cj('#id-event-acl').hide();
                    break;
                case "2":
                    cj('#id-group-acl').hide();
                    cj('#id-profile-acl').show();
                    cj('#id-custom-acl').hide();
                    cj('#id-event-acl').hide();
                    break;
                case "3":
                    cj('#id-group-acl').hide();
                    cj('#id-profile-acl').hide();
                    cj('#id-custom-acl').show();
                    cj('#id-event-acl').hide();
                    break;
                case "4":
                    cj('#id-group-acl').hide();
                    cj('#id-profile-acl').hide();
                    cj('#id-custom-acl').hide();
                    cj('#id-event-acl').show();
                    break;
            }
        }
    }
 return;
}
</script>
{/literal}
