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
{literal}<?xml version="1.0" ?>{/literal}
{strip}
<page>
<title>{$title}</title>
<query>{$query}</query>
<center lat="{$center.lat}" lng="{$center.lng}"/>
<span lat="{$span.lat}" lng="{$span.lng}"/>
<overlay panelStyle="{$panelStyle}">
{foreach from=$locations key=id item=location}
<location id="location_{$id}" infoStyle="/maps?file=gi&hl=en">
  <point lat="{$location.lat}" lng="{$location.lng}"/>
  <icon class="local" image="http://maps.google.com/mapfiles/marker.png"/>
  <info>
     <address>
       <line>{$location.displayName}</line>
       <line>{$location.address}</line>
     </address>
  </info>
</location>
{/foreach}
</overlay>
</page>
{/strip}
