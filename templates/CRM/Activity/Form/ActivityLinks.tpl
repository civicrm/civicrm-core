{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Links for scheduling/logging meetings and calls and Sending Email *}

{if $as_select} {* on 3.2, the activities can be either a drop down select (on the activity tab) or a list (on the action menu) *}
<select name="other_activity" class="crm-form-select crm-select2 crm-action-menu fa-plus" title="{ts}New Activity{/ts}">
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
    <i class="crm-i {$act.icon}" aria-hidden="true"></i> {$act.label}
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
