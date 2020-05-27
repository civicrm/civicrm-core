{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays recently viewed objects (contacts and other objects like groups, notes, etc. *}
<div id="recently-viewed">
    <ul>
    <li>{ts}Recently Viewed:{/ts}</li>
    {foreach from=$recentlyViewed item=item}
         <li><a href="{$item.url}">{$item.icon}</a><a href="{$item.url}">{$item.title}</a></li>
    {/foreach}
   </ul>
</div>
