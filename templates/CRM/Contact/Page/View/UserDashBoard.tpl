{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<table class="dashboard-elements">
  {if $showGroup}
    <tr class="crm-dashboard-groups">
      <td>
        <div class="header-dark">
          {ts}Your Group(s){/ts}
        </div>
        {include file="CRM/Contact/Page/View/UserDashBoard/GroupContact.tpl"}

      </td>
    </tr>
  {/if}

  {foreach from=$dashboardElements item=element}
    <tr{if $element.class} class="{$element.class}"{/if}>
      <td>
        <div class="header-dark">{$element.sectionTitle}</div>
        {include file=$element.templatePath context="user"}
      </td>
    </tr>
  {/foreach}
</table>
