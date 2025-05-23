<table class="crm-info-panel">
    <tr>
        <td class="label">{ts}Author{/ts}</td>
        <td>
          {foreach from=$extension.authors item=author}
            {capture assign=authorDetails}
              {if !empty($author.role)}{$author.role|escape};{/if}
              {if !empty($author.email)}<a href="mailto:{$author.email|escape}">{$author.email|escape}</a>;{/if}
              {if !empty($author.homepage)}<a href="{$author.homepage|escape}">{$author.homepage|escape}</a>;{/if}
            {/capture}
            {$author.name|escape} {if $authorDetails}({$authorDetails|trim:'; '}){/if}<br/>
          {/foreach}
        </td>
    </tr>
    <tr>
      <td class="label">
        {ts}Version{/ts}</td><td>{$extension.version|escape}
        {if $extension.ready == 'ready'}
          {icon icon="fa-trophy crm-extensions-stage"}{ts}This extension has been reviewed by the community.{/ts}{/icon}
        {elseif $extension.develStage == 'stable' && $extension.ready == 'not_ready'}
          {icon icon="fa-warning crm-extensions-stage"}{ts}This extension has not been reviewed by the community.{/ts} {ts}Please proceed with caution.{/ts}{/icon}
        {/if}
      </td>
    </tr>
    {if $extension.develStage}
    <tr>
      <td class="label">{ts}Stability{/ts}</td>
      <td>
        {if $extension.ready == 'ready'}
          {ts}This extension has been reviewed by the community.{/ts}
        {elseif $extension.develStage == 'stable' && $extension.ready == 'not_ready'}
          <div class="crm-error alert alert-danger">{ts}Please proceed with caution.{/ts} {ts}This extension has not been reviewed by the community and therefore may conflict with your configuration.{/ts} {ts}Consider evaluating the stated version compatibility, the total number of active installations and the date of the latest release of the extension as these may be good indicators of the extension's stability. If a support link is listed, please consult their issue queue to review any known issues.{/ts} {docURL page="dev/extensions/lifecycle"}</div>
        {else}
          {$extension.develStage_formatted|escape}
        {/if}
      </td>
    </tr>
    {/if}
    <tr>
      <td class="label">{ts}Released on{/ts}</td><td>{$extension.releaseDate|escape}</td>
    </tr>
    <tr>
      <td class="label">{ts}Active Installs{/ts}</td><td>{$extension.usage}</td>
    </tr>
    {if !empty($extension.requires)}
    <tr>
        <td class="label">{ts}Requires{/ts}</td>
        <td>
            {foreach from=$extension.requires item=ext}
                {if array_key_exists($ext, $localExtensionRows)}
                    {$localExtensionRows.$ext.label|escape} (already downloaded)
                {elseif array_key_exists($ext, $remoteExtensionRows)}
                    {$remoteExtensionRows.$ext.label|escape} (not downloaded)
                {else}
                    {$ext} {ts}(not available){/ts}
                {/if}
                <br/>
            {/foreach}
        </td>
    </tr>
    {/if}
    <tr>
      <td class="label">{ts}Compatible with CiviCRM version{/ts}</td>
      <td>
        {if $extension.compatibility}
          {foreach from=$extension.compatibility.ver item=ver}
            {$ver|escape} &nbsp;
          {/foreach}
        {/if}
      </td>
  </tr>
  <tr>
    <td class="label">{ts}Compatible with PHP version{/ts}</td>
    <td>
      {if $extension.php_compatibility}
        {foreach from=$extension.php_compatibility.ver item=ver}
          {$ver|escape} &nbsp;
        {/foreach}
      {else}
        {ts}Unknown{/ts}
      {/if}
    </td>
  </tr>
  <tr>
    <td class="label">{ts}Compatible with Smarty version{/ts}</td>
    <td>
      {if $extension.smarty_compatibility}
        {foreach from=$extension.smarty_compatibility.ver item=ver}
          {$ver|escape} &nbsp;
        {/foreach}
      {else}
        {ts}Unknown{/ts}
      {/if}
    </td>
  </tr>
    <tr>
      <td class="label">{ts}License{/ts}</td><td>{$extension.license|escape}</td>
    </tr>
    {if !empty($extension.path)}
      <tr>
        <td class="label">{ts}Local path{/ts}</td><td>{$extension.path|escape}</td>
      </tr>
    {/if}
    {if $extension.urls}
        {foreach from=$extension.urls key=label item=url}
            <tr><td class="label">{$label|escape}</td><td><a href="{$url|escape}">{$url|escape}</a></td></tr>
        {/foreach}
    {/if}
    {if $extension.downloadUrl}
    <tr>
      <td class="label">{ts}Download location{/ts}</td><td>{$extension.downloadUrl|escape}</td>
    </tr>
    {/if}
</table>
