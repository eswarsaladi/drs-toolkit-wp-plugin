jQuery(document).ready(function($) {

    var items = getItemsFromJqueryArray($('.coordinates'));
    var apiKey = getApiKey($('#map'));
    var projectKey = getProjectKey($('#map'));

    var mymap = createMap('map');

    addTileLayerToMap(mymap, apiKey, projectKey);

    addPopupsToItems(items, mymap);
    var bounds = getBoundsForMap(items);

    mymap.fitBounds(bounds);
});//end doc ready

function getCordinatesFromString(input) {
    if (!input) {
        return null;
    }
    return [
        input.split(',')[0].trim(),
        input.split(',')[1].trim()
    ];
}

function getItemsFromJqueryArray(jqArray) {
    var items = [];
    jqArray.each(function(index) {
        if (dataExists(jqArray[index])) {
            items.push({
                pid: jQuery(jqArray[index]).data('pid'),
                title: jQuery(jqArray[index]).data('title'),
                coordinates: getCordinatesFromString(jQuery(jqArray[index]).data('coordinates')),
                metadata: jQuery(jqArray[index]).data('metadata'),
                url: jQuery(jqArray[index]).data('url')
            });
        }
    });

    return items;
}

function dataExists(input) {
    return pidExists(input) && titleExists(input) && coordinatesExists(input) && metaDataExists(input) && urlExists(input);
}

function pidExists(input) {
    return (jQuery(input).data('pid')) ? true : false;
}
function titleExists(input) {
    return (jQuery(input).data('title')) ? true : false;
}
function coordinatesExists(input) {
    return (jQuery(input).data('coordinates')) ? true : false;
}
function metaDataExists(input) {
    return (jQuery(input).data('metadata')) ? true : false;
}
function urlExists(input) {
    return (jQuery(input).data('url')) ? true : false;
}

function getApiKey(jqSelector) {
    return jqSelector.data('map_api_key');
}

function getProjectKey(jqSelector) {
    return jqSelector.data('map_project_key');
}

function createMap(mapID) {
    if (!mapID) {
        return null;
    }
    return L.map(mapID);
}

function getBoundsForMap(items) {
    var bounds = [];
    jQuery.each(items, function(index, item) {
        bounds.push(item.coordinates);
    });
    return bounds;
}

function addPopupsToItems(items, map) {
    var markers = L.markerClusterGroup();

    jQuery.each(items, function(index, item) {
        var marker = L.marker(new L.LatLng(item.coordinates[0], item.coordinates[1]), { title: item.title });
        var popupContent = "<a href='" + item.url + "' target='_blank'>" + item.title + "</a><br/>" + item.metadata;
        marker.bindPopup(popupContent);
        markers.addLayer(marker);
    });
    map.addLayer(markers);
}

function addTileLayerToMap(map, apiKey, projectKey) {
    L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
        maxZoom: 18,
        id: projectKey,
        accessToken: apiKey
    }).addTo(map);
}