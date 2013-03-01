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
{* this template is used for adding/editing a saved mapping *}
<h3>{if $action eq 1}{ts}New Tag{/ts}{elseif $action eq 2}{ts}Edit Mapping{/ts}{else}{ts}Delete Mapping{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-mapping-form-block">
    {if $action eq 1 or $action eq 2 }
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>                  
      <table class="form-layout-compressed">
       <tr class="crm-mapping-form-block-name">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}</td>
       </tr>
       <tr class="crm-mapping-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
       </tr>
       <tr class="crm-mapping-form-block-mapping_type_id">
          <td class="label">{$form.mapping_type_id.label}</td>
          <td>{$form.mapping_type_id.html}</td>
       </tr>
      </table>
    {else}
        <div class="messages status no-popup">
            <div class="icon inform-icon"></div> &nbsp;
            {ts 1=$mappingName}WARNING: Are you sure you want to delete mapping '<b>%1</b>'?{/ts} {ts}This action cannot be undone.{/ts}
        </div>
        <br />
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" loaction="bottom"}</div>
    <div class="spacer"></div>
</div>
