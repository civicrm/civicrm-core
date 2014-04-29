{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{if empty($tagsetType)}
  {capture assign="tagsetType"}contact{/capture}
{/if}
{foreach from=$tagsetInfo.$tagsetType item=tagset}
  <div class="crm-section tag-section {$tagsetType}-tagset {$tagsetType}-tagset-{$tagset.parentID}-section">
    <label>{$tagset.parentName}</label>
    <div class="crm-clearfix"{if $context EQ "contactTab"} style="margin-top:-15px;"{/if}>
      {assign var=elemName  value = $tagset.tagsetElementName}
      {assign var=parID     value = $tagset.parentID}
      {assign var=editTagSet value=false}
      {$form.$elemName.$parID.html}
      {if $action ne 4 }
        {assign var=editTagSet value=true}
        {if $action eq 16 and !($permission eq 'edit') }
          {assign var=editTagSet value=false}
        {/if}
      {/if}
    </div>
  </div>
{/foreach}
