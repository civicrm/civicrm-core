{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="block-civicrm crm-container">
{foreach from=$dashboardLinks item=dash}
<a accesskey="{$dash.key}" href="{$dash.url}">{$dash.title}</a>
{/foreach}
</div>
