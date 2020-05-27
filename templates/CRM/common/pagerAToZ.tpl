{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays alphabetic filter bar for search results. If one more records in resultset starts w/ that letter, item is a link. *}
<div id="alpha-filter">
    <ul>
    {foreach from=$aToZ item=letter}
        <li {if $letter.class}class="{$letter.class}"{/if}>{$letter.item}</li>
    {/foreach}
    </ul>
</div>
