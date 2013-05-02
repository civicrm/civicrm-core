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
{* this template is used for adding/editing group (name and description only)  *}
<div class="crm-block crm-form-block crm-group-form-block">
    <div id="help">
  {if $action eq 2}
      {capture assign=crmURL}{crmURL p="civicrm/group/search" q="reset=1&force=1&context=smog&gid=`$group.id`"}{/capture}
      {ts 1=$crmURL}You can edit the Name and Description for this group here. Click <a href='%1'>Contacts in this Group</a> to view, add or remove contacts in this group.{/ts}
  {else}
      {ts}Enter a unique name and a description for your new group here. Then click 'Continue' to find contacts to add to your new group.{/ts}
  {/if}
    </div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        <tr class="crm-group-form-block-title">
      <td class="label">{$form.title.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_group' field='title' id=$group.id}{/if}</td>
            <td>{$form.title.html|crmAddClass:huge}
                {if $group.saved_search_id}&nbsp;({ts}Smart Group{/ts}){/if}
            </td>
        </tr>

        <tr class="crm-group-form-block-created">
           <td class="label">{ts}Created By{/ts}</td>
           <td>{if $group.created_by}{$group.created_by}{else}&nbsp;{/if}</td>
        </tr>

        <tr class="crm-group-form-block-description">
      <td class="label">{$form.description.label}</td>
      <td>{$form.description.html}<br />
    <span class="description">{ts}Group description is displayed when groups are listed in Profiles and Mailing List Subscribe forms.{/ts}</span>
            </td>
        </tr>

  {if $form.group_type}
      <tr class="crm-group-form-block-group_type">
    <td class="label">{$form.group_type.label}</td>
    <td>{$form.group_type.html} {help id="id-group-type" file="CRM/Group/Page/Group.hlp"}</td>
      </tr>
  {/if}

        <tr class="crm-group-form-block-visibility">
      <td class="label">{$form.visibility.label}</td>
      <td>{$form.visibility.html|crmAddClass:huge} {help id="id-group-visibility" file="CRM/Group/Page/Group.hlp"}</td>
  </tr>

  <tr class="crm-group-form-block-isReserved">
    <td class="report-label">{$form.is_reserved.label}</td>
    <td>{$form.is_reserved.html}
      <span class="description">{ts}If reserved, only users with 'administer reserved groups' permission can disable, delete, or change settings for this group. The reserved flag does NOT affect users ability to add or remove contacts from a group.{/ts}</span>
    </td>
  </tr>

  <tr>
      <td colspan=2>{include file="CRM/Custom/Form/CustomData.tpl"}</td>
  </tr>
    </table>

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

    {if $form.organization}
  <h3>{ts}Associated Organization{/ts} {help id="id-group-organization" file="CRM/Group/Page/Group.hlp"}</h3>
          <table class="form-layout-compressed">
        <tr class="crm-group-form-block-organization">
            <td class="label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$form.organization.label}</td>
      <td>{$form.organization.html|crmAddClass:huge}
          <div id="organization_address" style="font-size:10px"></div>
      </td>
        </tr>
    </table>
    {/if}

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    {if $action neq 1}
  <div class="action-link">
      <a href="{$crmURL}">&raquo; {ts}Contacts in this Group{/ts}</a>
      {if $group.saved_search_id}
          <br />
    {if $group.mapping_id}
        <a href="{crmURL p="civicrm/contact/search/builder" q="reset=1&force=1&ssID=`$group.saved_search_id`"}">&raquo; {ts}Edit Smart Group Criteria{/ts}</a>
    {elseif $group.search_custom_id}
                    <a href="{crmURL p="civicrm/contact/search/custom" q="reset=1&force=1&ssID=`$group.saved_search_id`"}">&raquo; {ts}Edit Smart Group Criteria{/ts}</a>
    {else}
        <a href="{crmURL p="civicrm/contact/search/advanced" q="reset=1&force=1&ssID=`$group.saved_search_id`"}">&raquo; {ts}Edit Smart Group Criteria{/ts}</a>
    {/if}

      {/if}
  </div>
    {/if}
</fieldset>

{literal}
<script type="text/javascript">
{/literal}{if $freezeMailignList}{literal}
cj('input[type=checkbox][name="group_type[{/literal}{$freezeMailignList}{literal}]"]').attr('disabled',true);
{/literal}{/if}{literal}
{/literal}{if $hideMailignList}{literal}
cj('input[type=checkbox][name="group_type[{/literal}{$hideMailignList}{literal}]"]').hide();
cj('label[for="group_type[{/literal}{$hideMailignList}{literal}]"]').hide();
{/literal}{/if}{literal}
{/literal}{if $organizationID}{literal}
    cj(document).ready( function() {
  //group organzation default setting
  var dataUrl = "{/literal}{crmURL p='civicrm/ajax/search' h=0 q="org=1&id=$organizationID"}{literal}";
  cj.ajax({
          url     : dataUrl,
          async   : false,
          success : function(html){
                      //fixme for showing address in div
                      htmlText = html.split( '|' , 2);
                      htmlDiv = htmlText[0].replace( /::/gi, ' ');
          cj('#organization').val(htmlText[0]);
                      cj('div#organization_address').html(htmlDiv);
                    }
  });
    });
{/literal}{/if}{literal}

var dataUrl = "{/literal}{$groupOrgDataURL}{literal}";
cj('#organization').autocomplete( dataUrl, {
              width : 250, selectFirst : false, matchContains: true
              }).result( function(event, data, formatted) {
                                                       cj( "#organization_id" ).val( data[1] );
                                                       htmlDiv = data[0].replace( /::/gi, ' ');
                                                       cj('div#organization_address').html(htmlDiv);
                  });
</script>
{/literal}
</div>
