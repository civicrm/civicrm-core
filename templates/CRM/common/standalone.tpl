<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$config->lcMessages|substr:0:2}">
 <head>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

  <link rel="Shortcut Icon" type="image/x-icon" href="{$config->resourceBase}i/widget/favicon.png" />

  {* @todo crmRegion below should replace this, but not working? *}
  {if isset($pageHTMLHead)}
    {foreach from=$pageHTMLHead item=i}
      {$i}
    {/foreach}
  {/if}

  {crmRegion name='html-header'}
  {/crmRegion}

{* @todo This is probably not required? *}
{if isset($buildNavigation) and !$urlIsPublic }
    {include file="CRM/common/Navigation.tpl" }
{/if}

  <title>{$docTitle}</title>
</head>
<body>

  {if $config->debug}
  {include file="CRM/common/debug.tpl"}
  {/if}

  <div id="crm-container" class="crm-container" lang="{$config->lcMessages|substr:0:2}" xml:lang="{$config->lcMessages|substr:0:2}">
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

    {crmRegion name='page-header'}
    {/crmRegion}

    <div class="clear"></div>

    <div id="crm-main-content-wrapper">
      {* include file="CRM/common/status.tpl" @todo FIXME *}
      {crmRegion name='page-body'}
        {if isset($isForm) and $isForm and isset($formTpl)}
          {include file="CRM/Form/$formTpl.tpl"}
        {else}
          {include file=$tplFile}
        {/if}
      {/crmRegion}
    </div>

    {if isset($localTasks)}
      {include file="CRM/common/localNav.tpl"}
    {/if}

    {crmRegion name='page-footer'}
      {if !empty($urlIsPublic)}
        {include file="CRM/common/publicFooter.tpl"}
      {else}
        {include file="CRM/common/footer.tpl"}
      {/if}
    {/crmRegion}
  </div>
</body>
</html>
