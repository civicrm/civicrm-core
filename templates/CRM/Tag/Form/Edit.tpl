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
{* this template is used for adding/editing a tag (admin)  *}
<div class="crm-block crm-form-block crm-tag-form-block">
  {if $action eq 1 or $action eq 2 }
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout-compressed">
       <tr class="crm-tag-form-block-label">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}</td>
       </tr>
       <tr class="crm-tag-form-block-description">
          <td class="label">{$form.description.label}</td>
          <td>{$form.description.html}</td>
       </tr>
         {if $form.parent_id.html}
       <tr class="crm-tag-form-block-parent_id">
         <td class="label">{$form.parent_id.label}</td>
         <td>{$form.parent_id.html}</td>
       </tr>
   {/if}
       <tr class="crm-tag-form-block-used_for">
          <td class="label">{$form.used_for.label}</td>
          <td>{$form.used_for.html} <br />
            <span class="description">
              {if $is_parent}{ts}You can change the types of records which this tag can be used for by editing the 'Parent' tag.{/ts}
              {else}{ts}What types of record(s) can this tag be used for?{/ts}
              {/if}
            </span>
          </td>
        </tr>
        <tr class="crm-tag-form-block-is_reserved">
           <td class="label">{$form.is_reserved.label}</td>
           <td>{$form.is_reserved.html} <br /><span class="description">{ts}Reserved tags can not be deleted. Users with 'administer reserved tags' permission can set or unset the reserved flag. You must uncheck 'Reserved' (and delete any child tags) before you can delete a tag.{/ts}
           </td>
        </tr>
        {if ! $isTagSet} {* Tagsets are not selectable by definition, so exclude this field for tagsets *}
          <tr class="crm-tag-form-block-is_slectable">
             <td class="label">{$form.is_selectable.label}</td>
             <td>{$form.is_selectable.html}<br /><span class="description">{ts}Defines if you can select this tag.{/ts}
             </td>
          </tr>
        {/if}
    </table>
        {if $parent_tags|@count > 0}
        <table class="form-layout-compressed">
            <tr><td><label>{ts}Remove Parent?{/ts}</label></td></tr>
            {foreach from=$parent_tags item=ctag key=tag_id}
                {assign var="element_name" value="remove_parent_tag_"|cat:$tag_id}
                <tr><td>&nbsp;&nbsp;{$form.$element_name.html}&nbsp;{$form.$element_name.label}</td></tr>
            {/foreach}
        </table><br />
        {/if}
    {else}
        <div class="status">{ts 1=$delName}Are you sure you want to delete <b>%1</b> Tag?{/ts}<br />{ts}This tag will be removed from any currently tagged contacts, and users will no longer be able to assign contacts to this tag.{/ts}</div>
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
