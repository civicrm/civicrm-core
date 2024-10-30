{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays search criteria assigned to $qill variable, for all search forms - basic, advanced, search builder, and component searches. *}
{foreach from=$qill name=sets key=setKey item=orClauses}
    {if $smarty.foreach.sets.total > 2}
        {* We have multiple criteria sets, so display AND'd items in each set on the same line. *}
        {if $orClauses}
        <ul>
        <li>
        {foreach from=$orClauses name=criteria item=item}
            {$item|escape}
            {if !$smarty.foreach.criteria.last}
                <span class="font-italic">...{ts}AND{/ts}...</span>
            {/if}
        {/foreach}
        </li>
        </ul>

        {* If there's a criteria set with key=0, this set is AND'd with other sets (if any). Otherwise, multiple sets are OR'd together. *}
        {if !$smarty.foreach.sets.last}
            <ul class="menu"><li class="no-display">
            {if $setKey == 0}AND<br />
            {else}OR<br />
            {/if}
            </li></ul>
        {/if}
        {/if}

    {else}
        {foreach from=$orClauses name=criteria item=item}
            <div class="qill">
            {$item|escape nofilter}
            {if !$smarty.foreach.criteria.last}
                {if !empty($operator)}
                  <span class="font-italic">...{$operator|escape}...</span>
                {else}
                  <span class="font-italic">...{ts}AND{/ts}...</span>
                {/if}
            {/if}
            </div>
        {/foreach}
    {/if}
{/foreach}
