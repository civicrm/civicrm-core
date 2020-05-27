{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if empty($hookContent)}
    {include file="CRM/Contact/Page/DashBoardDashlet.tpl"}
{else}
    {if $hookContentPlacement != 2 && $hookContentPlacement != 3}
        {include file="CRM/Contact/Page/DashBoardDashlet.tpl"}
    {/if}

    {foreach from=$hookContent key=title item=content}
    <fieldset><legend>{$title}</legend>
        {$content}
    </fieldset>
    {/foreach}

    {if $hookContentPlacement == 2}
        {include file="CRM/Contact/Page/DashBoardDashlet.tpl"}
    {/if}
{/if}
