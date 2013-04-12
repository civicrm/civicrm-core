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
{if $config->stateCountryMap}
<script language="javaScript" type="text/javascript">
{foreach from=$config->stateCountryMap item=stateCountryMap}
  {if $stateCountryMap.country && $stateCountryMap.state_province}
    {literal}
    cj(function() {
        countryID       = {/literal}"{$stateCountryMap.country}"{literal}
        // sometimes we name != id, hence if element does not exists
        // fetch the id
        if ( cj( '#' + countryID ).length == 0 ) {
          countryID = cj( 'select[name="' + countryID + '"]' ).prop('id');
        }

        stateProvinceID = {/literal}"{$stateCountryMap.state_province}"{literal}
        if ( cj( '#' + stateProvinceID ).length == 0 ) {
          stateProvinceID = cj( 'select[name="' + stateProvinceID + '"]' ).prop('id');
        }

        callbackURL     = {/literal}"{crmURL p='civicrm/ajax/jqState' h=0}"{literal}

        cj( '#' + countryID ).chainSelect( 
          '#' + stateProvinceID, 
          callbackURL, 
          { 
            before : function (target) {
              if (typeof(setdefault) === "undefined") { setdefault = new Array(); }
              targetid = cj(target).attr("id");
              eval('setdefault[targetid] = cj(target).val()');
            },
            after : function(target) { 
              targetid = cj(target).attr("id");
              cj(target).val(setdefault[targetid]); 
            } 
          }
        );
    });
    {/literal}
  {/if}
  
  {if $stateCountryMap.state_province && $stateCountryMap.county}
    {literal}
    cj(function() {
        stateProvinceID = {/literal}"{$stateCountryMap.state_province}"{literal}
        if ( cj( '#' + stateProvinceID ).length == 0 ) {
          stateProvinceID = cj( 'select[name="' + stateProvinceID + '"]' ).prop('id');
        }

        countyID       = {/literal}"{$stateCountryMap.county}"{literal}
        if ( cj( '#' + countyID ).length == 0 ) {
          countyID = cj( 'select[name="' + countyID + '"]' ).prop('id');
        }

        callbackURL     = {/literal}"{crmURL p='civicrm/ajax/jqCounty' h=0}"{literal}
        
        cj( '#' + stateProvinceID ).chainSelect( '#' + countyID, callbackURL,
          { 
            before : function (target) {
              if (typeof(setdefault) === "undefined") { setdefault = new Array(); }
              targetid = cj(target).attr("id");
              eval('setdefault[targetid] = cj(target).val()');
            },
            after : function(target) { 
              targetid = cj(target).attr("id");
              cj(target).val(setdefault[targetid]); 
            } 
          }
        );
    });
    {/literal}
  {/if}
{/foreach}
</script>
{/if}
