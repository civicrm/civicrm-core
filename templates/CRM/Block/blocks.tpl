{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{foreach from=$blocks item=block}
<div class="block {$block.name}" id="{$block.id}">
   <h2 class="title">{$block.subject}</h2>
   <div class="content">
      {$block.content}
   </div>
</div>
{/foreach}
