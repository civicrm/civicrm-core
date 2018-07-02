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
{* Links for scheduling/logging meetings and calls and Sending Email *}

{if $contact_id }
{assign var = "contactId" value= $contact_id }
{/if}

{if $as_select} {* on 3.2, the activities can be either a drop down select (on the activity tab) or a list (on the action menu) *}
<select name="other_activity" class="crm-form-select crm-select2 crm-action-menu fa-plus">
  <option value="">{ts}New Activity{/ts}</option>
{foreach from=$activityTypes item=act}
  <option value="{$act.url}" data-icon="{$act.icon}">{$act.label}</option>
{/foreach}
</select>
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('[name=other_activity].crm-action-menu').change(function() {
      var
        $el = $(this),
        url = $el.val();
      if (url) {
        $el.select2('val', '');
        CRM.loadForm(url).on('crmFormSuccess', function() {
          CRM.refreshParent($el);
        });
      }
    });
  });
</script>
{/literal}
{else}
<ul>
  <li class="crm-activity-tab"><a href="#" data-tab="activity">{ts}Record Activity:{/ts}</a></li>
{foreach from=$activityTypes key=k item=act}
<li class="crm-activity-type_{$k}">
  <a href="{$act.url}" data-tab="activity">
    <i class="crm-i {$act.icon}"></i> {$act.label}
  </a>
</li>
{/foreach}

{* add hook links if any *}
{if $hookLinks}
   {foreach from=$hookLinks item=link}
    <li>
        <a href="{$link.url}" data-tab="activity"{if !empty($link.title)} title="{$link.title|escape}"{/if}
        {if !empty($link.class)} class="{$link.class}"{/if}>
          {if $link.img}
                <img src="{$link.img}" alt="{$link.title|escape}" />&nbsp;
          {/if}
          {$link.name}
        </a>
    </li>
   {/foreach}
{/if}

</ul>

{/if}
