{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $showDirectly}
  {assign var=height value="350px"}
  {assign var=width  value="425px"}
{else}
  {assign var=height value="600px"}
  {assign var=width  value="100%"}
{/if}
{assign var=defaultZoom value=16}
{literal}
<script src="//maps.googleapis.com/maps/api/js?{/literal}{if $mapKey}key={$mapKey}{/if}{literal}&sensor=false&callback=initMap" type="text/javascript" defer="defer"></script>
<script type="text/javascript">
    function initMap() {
        var latlng = new google.maps.LatLng({/literal}{$center.lat},{$center.lng}{literal});
        var map = new google.maps.Map(document.getElementById("google_map"));
        map.setCenter(latlng);
        map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
        setMapOptions(map);
    }

    function setMapOptions(map) {
        bounds = new google.maps.LatLngBounds( );
  {/literal}
  {foreach from=$locations item=location}
      {if $location.url and ! $profileGID}
    {literal}
      var data = "{/literal}<a href='{$location.url}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}<br /><br />{ts}Get Directions FROM:{/ts}&nbsp;<input type=hidden id=to value='{$location.displayAddress}'><input type=text id=from size=20>&nbsp;<a href=\"#\" onclick=\"gpopUp(); return false;\">&raquo; Go</a>";
      {else}
    {capture assign="profileURL"}{crmURL p='civicrm/profile/view' q="reset=1&id=`$location.contactID`&gid=$profileGID"}{/capture}
    {literal}
      var data = "{/literal}<a href='{$profileURL}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}<br /><br />{ts}Get Directions FROM:{/ts}&nbsp;<input type=hidden id=to value='{$location.displayAddress}'><input type=text id=from size=20>&nbsp;<a href=\"#\" onclick=\"gpopUp(); return false;\">&raquo; Go</a>";
      {/if}
      {literal}
      var address = "{/literal}{$location.address}{literal}";
      {/literal}
      {if $location.lat}
    var point  = new google.maps.LatLng({$location.lat},{$location.lng});
    var image  = null;
    {if $location.image && ( $location.marker_class neq 'Event' ) }
       image = '{$location.image}';
    {else}
                 {if $location.marker_class eq 'Individual'}
           image = "{$config->resourceBase}i/contact_ind.gif";
       {/if}
       {if $location.marker_class eq 'Household'}
           image = "{$config->resourceBase}i/contact_house.png";
       {/if}
       {if $location.marker_class eq 'Organization'}
            image = "{$config->resourceBase}i/contact_org.gif";
       {/if}
                {/if}
           {literal}
                createMarker(map, point, data, image);
                bounds.extend(point);
                {/literal}
      {/if}
  {/foreach}
        map.setCenter(bounds.getCenter());
        {if count($locations) gt 1}
            map.fitBounds(bounds);
            map.setMapTypeId(google.maps.MapTypeId.TERRAIN);
        {elseif $location.marker_class eq 'Event' || $location.marker_class eq 'Individual'|| $location.marker_class eq 'Household' || $location.marker_class eq 'Organization' }
            map.setZoom({$defaultZoom});
        {else}
            map.setZoom({$defaultZoom});
        {/if}
  {literal}
    }

    function createMarker(map, point, data, image) {
        var marker = new google.maps.Marker({ position: point,
                                              map: map,
                                              icon: image
                                            });
        var infowindow = new google.maps.InfoWindow();
        google.maps.event.addListener(marker, 'click', function() { infowindow.setContent(data);
                                                                    infowindow.open(map,marker);
                                                                   });
    }

    function gpopUp() {
  var from   = document.getElementById('from').value;
  var to     = document.getElementById('to').value;
  var URL    = "http://maps.google.com/maps?saddr=" + from + "&daddr=" + to;
  day = new Date();
  id  = day.getTime();
  eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=780,height=640,left = 202,top = 100');");
    }
</script>
{/literal}
<div id="google_map" style="width: {$width}; height: {$height}"></div>
