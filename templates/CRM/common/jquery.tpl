{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
{*
 * If you MODIFY this file, please make sure you also modify jquery.files.tpl.
 * Cannot get rid of this since we use it for print html, standalone profile etc
 *}
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery.min.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery-ui-1.8.16/js/jquery-ui-1.8.16.custom.min.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/jquery-ui-1.8.16/css/smoothness/jquery-ui-1.8.16.custom.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.autocomplete.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/jquery.autocomplete.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jstree/jquery.jstree.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/plugins/jstree/themes/default/style.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.menu.pack.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/menu.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.chainedSelects.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.contextMenu.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.tableHeader.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.textarearesizer.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.form.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.tokeninput.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/token-input-facebook.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.timeentry.pack.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.mousewheel.pack.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.toolTip.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/DataTables/media/js/jquery.dataTables.min.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/plugins/DataTables/media/css/demo_table_jui.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.dashboard.js"></script>
<style type="text/css">@import url("{$config->resourceBase}packages/jquery/css/dashboard.css");</style>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.FormNavigate.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.validate.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.civicrm-validate.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.ui.datepicker.validation.pack.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery-fieldselection.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.jeditable.mini.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.mustache.js"></script>

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.blockUI.js"></script>

<script type="text/javascript" src="{$config->resourceBase}js/rest.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/Common.js"></script>

<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmeditable.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmaccordions.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmasmselect.js"></script>
<script type="text/javascript" src="{$config->resourceBase}js/jquery/jquery.crmtooltip.js"></script>

{* CRM-6819: localize datepicker *}
{if $l10nURL}
  <script type="text/javascript" src="{$l10nURL}"></script>
{/if}

<script type="text/javascript">var cj = jQuery.noConflict();</script>
