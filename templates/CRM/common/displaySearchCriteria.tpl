{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* Displays search criteria assigned to $qill variable, for all search forms - basic, advanced, search builder, and component searches. *}
{foreach from=$qill name=sets key=setKey item=orClauses}
    {if $smarty.foreach.sets.total > 2}
        {* We have multiple criteria sets, so display AND'd items in each set on the same line. *}
        {if count($orClauses) gt 0}
        <ul>
        <li>
        {foreach from=$orClauses name=criteria item=item}
            {$item}
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
            {$item}
            {if !$smarty.foreach.criteria.last}
                {if $operator}
                  <span class="font-italic">...{$operator}...</span>
                {else}
                  <span class="font-italic">...{ts}AND{/ts}...</span>
                {/if}
            {/if}
            </div>
        {/foreach}
    {/if}
{/foreach}
