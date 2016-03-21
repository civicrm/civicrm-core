{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{if ! empty( $row )}
{* wrap in crm-container div so crm styles are used *}
    {if $overlayProfile }
        {include file="CRM/Profile/Page/Overlay.tpl"}
    {else}
        <div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
            <div class="crm-profile-name-{$ufGroupName}">
            {crmRegion name=profile-view-`$ufGroupName`}
            {foreach from=$profileFields item=field key=rowName}
              <div id="row-{$rowName}" class="crm-section {$rowName}-section">
                <div class="label">
                    {$field.label}
                </div>
                 <div class="content">
                    {$field.value}
                 </div>
                 <div class="clear"></div>
              </div>
            {/foreach}
            {/crmRegion}
            </div>
        </div>
    {/if}
{/if}
{* fields array is not empty *}
