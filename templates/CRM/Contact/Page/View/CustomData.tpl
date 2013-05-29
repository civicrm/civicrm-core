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
{* template for custom data *}
{if $action eq 0 or $action eq 1 or $action eq 2 or $recordActivity}
  {include file="CRM/Contact/Form/CustomData.tpl" mainEdit=$mainEditForm}
{/if}

{strip}
  {if $action eq 16 or $action eq 4} {* Browse or View actions *}
    <div class="form-item">
      {include file="CRM/Custom/Page/CustomDataView.tpl"}
    </div>
  {/if}
{/strip}

{if $mainEditForm}
  <script type="text/javascript">
    var showBlocks1 = new Array({$showBlocks1});
    var hideBlocks1 = new Array({$hideBlocks1});

    on_load_init_blocks(showBlocks1, hideBlocks1);
  </script>
{else}
  <script type="text/javascript">
    var showBlocks = new Array({$showBlocks});
    var hideBlocks = new Array({$hideBlocks});

    {* hide and display the appropriate blocks as directed by the php code *}
    on_load_init_blocks(showBlocks, hideBlocks);
  </script>
{/if}
