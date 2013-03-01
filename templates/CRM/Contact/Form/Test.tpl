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
{* test template for testing ajax *}
<script type="text/javascript" src="{crmURL p='civicrm/contact/StateCountryServer' q="set=1&path=civicrm/contact/StateCountryServer"}"></script>
<script type="text/javascript" src="{$config->resourceBase}js/Test.js"></script>

<form id="autoCompleteForm" name="autoCompleteForm">

{$form.state.label} {$form.state.html}<br />
{$form.state_id.label} {$form.state_id.html}<br />
{$form.country.label} {$form.country.html}<br />
<!--{$form.country_id.label} {$form.country_id.html}<br /> -->
<!--
Enter a State: <input type="text" id="state" name="state" value="" onkeyup="getWord(this,event);" autocomplete="off" onblur="getWord(this,event);"-->
<!--input type="text" id="state" name="state" value="" onkeyup="getWord(this,event);" autocomplete="off"-->

<!-- Note the autocomplete="off": without it you get errors like;
"Permission denied to get property XULElement.selectedIndex..."
-->
<!--
state id: <input type="text" id="state_id" name="state_id" value="" READONLY>
<br />
Country :<input type="text" id="country" name ="country" READONLY>
country id: <input type="text" name="country_id" id="country_id" value="" READONLY>
-->
