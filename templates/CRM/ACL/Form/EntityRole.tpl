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
{* this template is used for adding/editing ACL EntityRole objects *}
<div class="crm-block crm-form-block crm-acl-entityrole-form-block">
<fieldset><legend>{if $action eq 1}{ts}Assign ACL Role{/ts}{elseif $action eq 2}{ts}Assign ACL Role{/ts}{else}{ts}Delete ACL Role Assignment{/ts}{/if}</legend>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 8}
  <div class="messages status no-popup">
  <div class="icon inform-icon"></div>
       {ts}WARNING: Deleting this option will remove this ACL Role Assignment.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
    <table class="form-layout-compressed">
      <tr class="crm-acl-entityrole-form-block-acl_role_id">
         <td class="label">{$form.acl_role_id.label}</td>
         <td>{$form.acl_role_id.html}<br />
            <span class="description">{ts}Select an ACL Role to assign.{/ts}</span>
         </td>
      </tr>
      <tr class="crm-acl-entityrole-form-block-entity_id">
         <td class="label">{$form.entity_id.label}</td>
         <td>{$form.entity_id.html}<br />
            <span class="description">{ts}Select a group of contacts who should have this role when logged in to your site. Groups must be assigned the 'Access Control' type (Contacts &raquo; Manage Groups &raquo; Settings) to be included in this list.{/ts}</span>
         </td>
      <tr class="crm-acl-entityrole-form-block-is_active">
         <td class="label">{$form.is_active.label}</td>
         <td>{$form.is_active.html}</td>
      </tr>
  </table>
{/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</fieldset>
</div>
