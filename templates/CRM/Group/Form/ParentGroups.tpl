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
{*CRM-14190*}
{if $parent_groups|@count > 0 or $form.parents.html}
  <h3>{ts}Parent Groups{/ts} {help id="id-group-parent" file="CRM/Group/Page/Group.hlp"}</h3>
  {if $parent_groups|@count > 0}
    <table class="form-layout-compressed">
      <tr>
        <td><label>{ts}Remove Parent?{/ts}</label></td>
      </tr>
      {foreach from=$parent_groups item=cgroup key=group_id}
        {assign var="element_name" value="remove_parent_group_"|cat:$group_id}
        <tr>
          <td>&nbsp;&nbsp;{$form.$element_name.html}&nbsp;{$form.$element_name.label}</td>
        </tr>
      {/foreach}
    </table>
    <br />
  {/if}
  <table class="form-layout-compressed">
    <tr class="crm-group-form-block-parents">
      <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.parents.label}</td>
      <td>{$form.parents.html|crmAddClass:huge}</td>
    </tr>
  </table>
{/if}
