{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* If you want a custom profile view, you can access field labels and values in $profileFields_N array - where N is profile ID. *}
{* EXAMPLES *}{* $profileFields_1.last_name.label *}{* $profileFields_1.last_name.value *}

{if $overlayProfile }
    {foreach from=$profileGroups item=group}
        <div class="crm-summary-group">
           {$group.content}
        </div>
    {/foreach}
{else}
    {foreach from=$profileGroups item=group}
        <h2>{$group.title}</h2>
        <div id="profilewrap{$groupID}" class="crm-profile-view">
           {$group.content}
        </div>
    {/foreach}
    <div class="action-link">
        {if $listingURL}
            <a href="{$listingURL}">&raquo; {ts}Back to Listings{/ts}</a>&nbsp;&nbsp;&nbsp;&nbsp;
        {/if}
        {if $mapURL}
            <a href="{$mapURL}">&raquo; {ts}Map Primary Address{/ts}</a>
        {/if}
    </div>
{/if}
{literal}
     <script type='text/javascript'>
          function contactImagePopUp (url, width, height) {
             newWindow = window.open( url,'name', 'width='+width+', height='+height );
          }
     </script>
{/literal}
