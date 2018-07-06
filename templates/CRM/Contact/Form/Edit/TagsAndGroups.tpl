{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $title}
<div class="crm-accordion-wrapper crm-tagGroup-accordion collapsed">
  <div class="crm-accordion-header">{$title}</div>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
        {if !$type || $type eq 'tag'}
          <td>
            <div class="crm-section tag-section">
              {if $title}{$form.tag.label}{/if}
              {$form.tag.html}
            </div>
            {if $context NEQ 'profile'}
              {include file="CRM/common/Tagset.tpl"}
            {/if}
          </td>
        {/if}
        {if !$type || $type eq 'group'}
          <td>
            {if $groupElementType eq 'select'}
              <div class="crm-section group-section">
              {if $title}{$form.group.label}<br>{/if}
              {$form.group.html}
            </div>
            {else}
              {foreach key=key item=item from=$tagGroup.group}
                <div class="group-wrapper">
                  {$form.group.$key.html}
                  {if $item.description}
                    <div class="description">{$item.description}</div>
                  {/if}
                </div>
              {/foreach}
            {/if}
          </td>
        {/if}
      </tr>
    </table>
{if $title}
  </div>
</div><!-- /.crm-accordion-wrapper -->
{/if}
