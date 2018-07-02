{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{assign var=defaultZoom value=12}
{literal}
<script src="https://cdnjs.cloudflare.com/ajax/libs/openlayers/2.13.1/OpenLayers.js" type="text/javascript"></script>
<script type="text/javascript">
    var popup = new Popup();

    function Popup() {
        this.popup = null;
        this.active = false;
    }

    Popup.prototype.create = function(evt) {
        this.destroy();

        this.popup = new OpenLayers.Popup.FramedCloud
            (
                "data",
                evt.object.customLonLat,
                new OpenLayers.Size(200,200),
                evt.object.customContent,
                null,
                true,
                function() {
                    this.toggle();
                }
            );
        evt.object.customMarkers.map.addPopup(this.popup);
        OpenLayers.Event.stop(evt);
    }

    Popup.prototype.destroy = function() {
        if(this.active) {
            this.popup.destroy();
            this.popup = null;
        }
    }

    function initMap() {
        var map = new OpenLayers.Map("osm_map");
        map.addLayer(new OpenLayers.Layer.OSM("CARTO OSM", [
          "https://cartodb-basemaps-1.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
          "https://cartodb-basemaps-2.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
          "https://cartodb-basemaps-3.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
          "https://cartodb-basemaps-4.global.ssl.fastly.net/light_all/${z}/${x}/${y}.png",
        ], {
            attribution: 'Data &copy; <a href="http://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>. Map tiles &copy; <a href="https://carto.com/attribution" target="_blank">CARTO</a>.'
        }));

        var lonLat = new OpenLayers.LonLat(
            {/literal}{$center.lng}{literal},
            {/literal}{$center.lat}{literal}).transform(
                new OpenLayers.Projection("EPSG:4326"),
                map.getProjectionObject()
            );

        map.setCenter(lonLat, {/literal}{$defaultZoom}{literal});

        setMapOptions(map);
    }

    function setMapOptions(map) {
        markers = new OpenLayers.Layer.Markers("Markers");
        map.addLayer(markers);

        bounds = map.calculateBounds();
        {/literal}
        {foreach from=$locations item=location}
            {if $location.url and ! $profileGID}
            {literal}
                var data = "{/literal}<a href='{$location.url}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}";
            {else}
                {capture assign="profileURL"}{crmURL p='civicrm/profile/view' q="reset=1&id=`$location.contactID`&gid=$profileGID"}{/capture}
                {literal}
                var data = "{/literal}<a href='{$profileURL}'>{$location.displayName}</a><br />{if !$skipLocationType}{$location.location_type}<br />{/if}{$location.address}";
            {/if}
            {literal}
            var address = "{/literal}{$location.address}{literal}";
            {/literal}
            {if $location.lat}
                point = new OpenLayers.LonLat(
                    {$location.lng},
                    {$location.lat}).transform(
                        new OpenLayers.Projection("EPSG:4326"),
                        map.getProjectionObject()
                );
                {if $location.image && ( $location.marker_class neq 'Event' ) }
                    var image = '{$location.image}';
                {else}
                    {if $location.marker_class eq 'Individual'}
                        var image = "{$config->resourceBase}i/contact_ind.gif";
                    {/if}
                    {if $location.marker_class eq 'Household'}
                        var image = "{$config->resourceBase}i/contact_house.png";
                    {/if}
                    {if $location.marker_class eq 'Organization' || $location.marker_class eq 'Event'}
                        var image = "{$config->resourceBase}i/contact_org.gif";
                    {/if}
                {/if}
                {literal}
                createMarker(map, markers, point, data, image);
                bounds.extend(point);
                {/literal}
            {/if}
        {/foreach}
        map.setCenter(bounds.getCenterLonLat());
        {if count($locations) gt 1}
            map.zoomToExtent(bounds);
        {elseif $location.marker_class eq 'Event' || $location.marker_class eq 'Individual'|| $location.marker_class eq 'Household' || $location.marker_class eq 'Organization' }
            map.zoomTo({$defaultZoom});
        {else}
            map.zoomTo({$defaultZoom});
        {/if}
        {literal}
        //attribution sits awkwardly high, move it down
        jQuery('.olControlAttribution').css('bottom','0px');
    }

    function createMarker(map, markers, point, data, image) {
        var marker = new OpenLayers.Marker(point);

        var size = new OpenLayers.Size(20,20);
        var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
        marker.icon.size = size;
        marker.icon.offset = offset;
        marker.icon.url = image;

        marker.customContent = data;
        marker.customLonLat = point;
        marker.customMarkers = markers;

        marker.events.register('mousedown', marker, markerClick);

        markers.addMarker(marker);
    }

    function markerClick(evt) {
        popup.create(evt);
    }

    if (window.addEventListener) {
        window.addEventListener("load", initMap, false);
    } else if (window.attachEvent) {
        document.attachEvent("onreadystatechange", initMap);
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
<div id="osm_map" style="width: {$width}; height: {$height}"></div>
