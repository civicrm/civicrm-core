{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
{if $form.organization_id}
  <h3>{ts}Associated Organization{/ts} {help id="id-group-organization" file="CRM/Group/Page/Group.hlp"}</h3>
  <table class="form-layout-compressed">
    <tr class="crm-group-form-block-organization">
      <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.organization_id.label}</td>
      <td>{$form.organization_id.html|crmAddClass:huge}
      </td>
    </tr>
  </table>
{/if}
