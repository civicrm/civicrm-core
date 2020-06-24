{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays local tasks (secondary menu) for any pages that have them *}
<div class="tabs">
    <ul class="tabs primary">
    {foreach from=$localTasks item=task}
        <li {if $task.class}class="{$task.class}"{/if}><a href="{$task.url}" {if $task.class}class="{$task.class}"{/if}>{$task.title}</a></li>
    {/foreach}
   </ul>
</div>
<br class="clear" />
