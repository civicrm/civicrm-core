{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$config->lcMessages|truncate:2:"":true}">
<head>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="Shortcut Icon" type="image/x-icon" href="{$config->resourceBase}i/widget/favicon.png" />

  {crmRegion name='html-header' allowCmsOverride=0}
  {/crmRegion}

  {if $config->customCSSURL}
    <link rel="stylesheet" href="{$config->customCSSURL}" type="text/css" />
  {else}
    <link rel="stylesheet" href="{$config->resourceBase}css/civicrm.css" type="text/css" />
  {/if}
  <link rel="stylesheet" href="{$config->resourceBase}css/standalone.css" type="text/css" />
  <link rel="stylesheet" href="{$config->resourceBase}css/extras.css" type="text/css" />
  <link rel="stylesheet" href="{$config->resourceBase}css/garland.css" type="text/css" />

  {$pageHTMLHead}

  <title>{$docTitle}</title>
</head>
<body class="sidebar-left">
{if !$config->inCiviCRM}
  <div id="crm-container" class="crm-container{if $urlIsPublic} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
    {include file="CRM/common/snippet.tpl"}
  </div>
{else}
  {if $config->debug}
    {include file="CRM/common/debug.tpl"}
  {/if}

  {crmNavigationMenu is_default=1}

  {* include wysiwyg related files*}
  {include file="CRM/common/wysiwyg.tpl"}

  <div id="header-region" class="clear-block"></div>

  <div id="wrapper">
    <div id="container" class="clear-block">
      {if $sidebarLeft}
        <div id="sidebar-left" class="sidebar">
          {$sidebarLeft}
        </div>
      {/if}
      <div id="center"><div id="squeeze"><div class="right-corner"><div id="main-content" class="left-corner">
      {if $breadcrumb}
        <div class="breadcrumb">
          {foreach from=$breadcrumb item=crumb key=key}
             {if $key != 0}
              &raquo;
            {/if}
            <a href="{$crumb.url}">{$crumb.title}</a>
          {/foreach}
        </div>
      {/if}

      {if $pageTitle}
        <div class="crm-title">
          <h1 class="title">{if $isDeleted}<del>{/if}{$pageTitle}{if $isDeleted}</del>{/if}</h1>
        </div>
      {/if}

      <div class="clear-block">
        <div id="crm-container" class="crm-container{if $urlIsPublic} crm-public{/if}" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
          {if $browserPrint}
          {* Javascript window.print link. Used for public pages where we can't do printer-friendly view. *}
              <div id="printer-friendly">
                <a href="#" onclick="window.print(); return false;" title="{ts}Print this page.{/ts}">
                  <div class="ui-icon ui-icon-print"></div>
                </a>
              </div>
          {else}
          {* Printer friendly link/icon. *}
            <div id="printer-friendly">
              <a href="{$printerFriendly}" target='_blank' title="{ts}Printer-friendly view of this page.{/ts}">
                <div class="ui-icon ui-icon-print"></div>
              </a>
            </div>
          {/if}

          {crmRegion name='page-header'}
          {/crmRegion}

          <div class="clear"></div>
          
          {if $localTasks}
            {include file="CRM/common/localNav.tpl"}
          {/if}

          {* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}
          {include file="CRM/common/status.tpl"}

          <!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
          {crmRegion name='page-body'}
          {if isset($isForm) and $isForm}
            {include file="CRM/Form/$formTpl.tpl"}
          {else}
            {include file=$tplFile}
          {/if}
          {/crmRegion}

          {crmRegion name='page-footer'}
          {if ! $urlIsPublic}
            {include file="CRM/common/footer.tpl"}
          {/if}
          {/crmRegion}

        </div>
      </div>

    </div></div></div></div>
  </div>
</div>
{/if} {* $config->inCiviCRM *}

</body>
</html>
