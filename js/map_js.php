<?php
session_start();
Header("content-type: application/x-javascript");
?>

var map, mapDiv, Ext, popup, mapPanel, centerPanel, northPanel, legendPanel, westPanel, aboutPanel, helpPanel, layerTree, opacitySlider, navCtrl, infoCtrl, selectCtrl, permalinkProvider, mapLink, keyboardnav, highlightLayer, layerRuler, bingmap, bingsat, binghyb, gmap, gsat, ghyb, gphy, mapquestOSM, mapquestOAM, OSMterrain, USGSimagery, USGSdrg24k, USGSdrg100k, USGSdrg250k;

var nysdopLatest, nysdop11, nysdop10, nysdop09, nysdop08, nappImagery, ridgeRADAR, cloudImagery, blueline, citytown, landclass2012, streamflow, usgsNHD, watershedshuc8, lakes, streams, wetlands, lakechem1984_1987, fishsurveylakes, terrestrialinvasives, aquaticinvasives, leantos, campsite, slt, trailregisters, NLCD06, impervious06, firemap1916, blowdown1950, landclass2001;

OpenLayers.ProxyHost = "proxy.php?url=";

Proj4js.defs["EPSG:26918"] = "+proj=utm +zone=18 +ellps=GRS80 +datum=NAD83 +units=m +no_defs";//NAD83/UTM Zone 18N
Proj4js.defs["EPSG:2260"] = "+proj=tmerc +lat_0=38.83333333333334 +lon_0=-74.5 +k=0.9999 +x_0=150000 +y_0=0 +ellps=GRS80 +datum=NAD83"; //NAD83/SP NY East(ft)

// BEGIN LOADING MASK
Ext.onReady(function () {
    setTimeout(function () {
        Ext.get('loading').remove();
        Ext.get('loading-mask').fadeOut({
            remove: true
        });
    }, 250);
});
// END LOADING MASK

OpenLayers.Lang.en = {
    'scale': "1 : ${scaleDenom}"
};

// DISCLAIMER WITH COOKIES
function getCookie(c_name) {
    var i, x, y, ARRcookies = document.cookie.split(";");
    for (i = 0; i < ARRcookies.length; i++) {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
        x = x.replace(/^\s+|\s+$/g, "");
        if (x == c_name) {
            return unescape(y);
        }
    }
}

function setCookie(c_name, value, exdays) {
    var exdate = new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
    document.cookie = c_name + "=" + c_value;
}

function checkDisclaimer() {
    var disclaimer = getCookie("disclaimer");
    if (disclaimer == "accepted") {
        // Proceed to site without showing disclaimer...
    }
    else {
        // Show disclaimer and register cookie when they agree...
        //Ext.MessageBox.confirm('Disclaimer', 'The data provided on this site is for informational and planning purposes only.<br><br>Absolutely no accuracy or completeness guarantee is implied or intended. All information on this map is subject to such variations and corrections as might result from a complete title search and/or accurate field survey.<br><br>Please press "Yes" to accept this disclaimer and access the application.', disclaimerAccept);
        var disclaimerWin = new Ext.Window({
            layout: 'fit',
            modal: true,
            title: 'Application Disclaimer',
            width: 500,
            height: 150,
            closable: false,
            resizable: false,
            plain: true,
            border: true,
            closeAction: 'hide',
            html: '<div style="padding: 5px">The data provided on this site is for informational and planning purposes only.<br><br>Absolutely no accuracy or completeness guarantee is implied or intended. All information on this map is subject to such variations and corrections as might result from a complete title search and/or accurate field survey.  <strong>NOTE: This site is no longer being actively updated due to lack of funding.</strong></div>',
            buttons: [{
                text: 'Accept Disclaimer',
                handler: function () {
                    setCookie("disclaimer", "accepted", 1);
                    disclaimerWin.hide();
                }
            }]
        });
        disclaimerWin.show();
    }
}

function layerProperties() {
    if (layerTree.getSelectionModel().getSelectedNode() == null || layerTree.getSelectionModel().getSelectedNode().attributes.layer == undefined) {
        alert("Please select a layer!");
    } else{
        var propertiesTabs = new Ext.TabPanel({
            activeTab: 0,
            plain: true,
            defaults: {
                autoScroll: true,
                padding: 5
            },
            items: [{
                title: 'Layer Properties',
                layout: 'form',
                defaults: {
                    width: 350
                },
                defaultType: 'textfield',

                items: [new GeoExt.LayerOpacitySlider({
                    fieldLabel: 'Transparency',
                    layer: layerTree.getSelectionModel().getSelectedNode().layer,
                    aggressive: true,
                    width: 150,
                    isFormField: true,
                    inverse: true,
                    plugins: new GeoExt.LayerOpacitySliderTip({
                        template: "<div>Transparency: {opacity}%</div>"
                    })
                }), {
                    xtype: "combo",
                    fieldLabel: 'Quick Filter',
                    readOnly: false,
                    store: new Ext.data.SimpleStore({
                        fields: ['name', 'value'],
                        data: layerTree.getSelectionModel().getSelectedNode().attributes.queries
                    }),
                    displayField: 'name',
                    valueField: 'value',
                    value: layerTree.getSelectionModel().getSelectedNode().layer.params.CQL_FILTER,
                    mode: "local",
                    triggerAction: "all",
                    //editable: false,
                    typeAhead: true,
                    listeners: {
                        select: function(combo) {
                            layerTree.getSelectionModel().getSelectedNode().layer.mergeNewParams({
                                cql_filter: combo.getValue()
                            });
                        }
                    }
                }, {
                    fieldLabel: 'Originator',
                    readOnly: true,
                    name: 'originator',
                    value: layerTree.getSelectionModel().getSelectedNode().attributes.originator
                }, {
                    fieldLabel: 'Updated',
                    readOnly: true,
                    name: 'updated',
                    value: layerTree.getSelectionModel().getSelectedNode().attributes.updated
                }, {
                    fieldLabel: 'Metadata',
                    xtype: 'box',
                    html: '<a href="'+layerTree.getSelectionModel().getSelectedNode().attributes.metadata+'" target="_blank">'+layerTree.getSelectionModel().getSelectedNode().attributes.metadata+'</a>'
                }, {
                    fieldLabel: 'Abstract',
                    xtype: 'textarea',
                    readOnly: true,
                    grow: true,
                    growMax: 500,
                    width: 300,
                    value: layerTree.getSelectionModel().getSelectedNode().attributes.abstract
                }]
            }/*, {
                title: 'Metadata',
                html: "Metadata here..."
            }*/]
        });
        var propertiesWindow = new Ext.Window({
            title: layerTree.getSelectionModel().getSelectedNode().attributes.layer.name,
            id: 'winLogin',
            layout: 'fit',
            width: 500,
            height: 400,
            //y: 340,
            modal: true,
            resizable: true,
            closable: true,
            items: [propertiesTabs]
        });
        propertiesWindow.show();
    };
}

var parcelSearchStore = new Ext.data.JsonStore({
    autoLoad: false,
    root: 'features',
    fields: [{
        name: "gid",
        mapping: "properties.gid"
    }, {
        name: "parcel_id",
        mapping: "properties.parcel_id"
    }, {
        name: "bbox",
        mapping: "bbox"
    }],
    sortInfo: {
        field: "parcel_id",
        direction: "ASC"
    },
    proxy: new Ext.data.HttpProxy({
        url: "parcel_search.php"
    })
});

Ext.onReady(function () {
    checkDisclaimer();
    permalinkProvider = new GeoExt.state.PermalinkProvider({
        encodeType: false
    });
    Ext.state.Manager.setProvider(permalinkProvider);
    Ext.QuickTips.init();
    Ext.BLANK_IMAGE_URL = "resources/ext-3.4.0/resources/images/default/s.gif";
    keyboardnav = new OpenLayers.Control.KeyboardDefaults();

    map = new OpenLayers.Map({
        projection: "EPSG:900913",
        displayProjection: "EPSG:4326",
        controls: [new OpenLayers.Control.PanPanel(), new OpenLayers.Control.ZoomPanel(), new OpenLayers.Control.ScaleLine(), new OpenLayers.Control.Attribution(), new OpenLayers.Control.LoadingPanel(), keyboardnav],
        numZoomLevels: 20
    });

    bingmap = new OpenLayers.Layer.Bing({
        key: "Ap6PC13ktG2lQOnnRUqi7bX6pPwkP93-fshU6LWlMeN503YdcZInCVMczp6k2joo",
        type: "Road",
        name: "Bing Streets",
        numZoomLevels: 19,
        transitionEffect: "resize"
    });
    bingsat = new OpenLayers.Layer.Bing({
        key: "Ap6PC13ktG2lQOnnRUqi7bX6pPwkP93-fshU6LWlMeN503YdcZInCVMczp6k2joo",
        type: "Aerial",
        name: "Bing Imagery",
        numZoomLevels: 20,
        transitionEffect: "resize"
    });
    binghyb = new OpenLayers.Layer.Bing({
        key: "Ap6PC13ktG2lQOnnRUqi7bX6pPwkP93-fshU6LWlMeN503YdcZInCVMczp6k2joo",
        type: "AerialWithLabels",
        name: "Bing Imagery With Labels",
        numZoomLevels: 20,
        transitionEffect: "resize"
    });
    gmap = new OpenLayers.Layer.Google("Google Streets", {
        numZoomLevels: 22,
        transitionEffect: "resize"
    });
    gsat = new OpenLayers.Layer.Google("Google Imagery", {
        type: google.maps.MapTypeId.SATELLITE,
        numZoomLevels: 22,
        transitionEffect: "resize"
    });
    ghyb = new OpenLayers.Layer.Google("Google Imagery With Labels", {
        type: google.maps.MapTypeId.HYBRID,
        numZoomLevels: 22,
        transitionEffect: "resize"
    });
    gphy = new OpenLayers.Layer.Google("Google Terrain", {
        type: google.maps.MapTypeId.TERRAIN,
        transitionEffect: "resize"
    });
    mapquestOSM = new OpenLayers.Layer.XYZ("OpenStreetMap", ["http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png", "http://otile2.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png", "http://otile3.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png", "http://otile4.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png"], {
        attribution: 'Data CC-By-SA by <a href="http://openstreetmap.org" target="_blank">OpenStreetMap</a> Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">',
        transitionEffect: "resize"
    });
    mapquestOAM = new OpenLayers.Layer.XYZ("MapQuest OpenAerial", ["http://oatile1.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.png", "http://oatile2.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.png", "http://oatile3.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.png", "http://oatile4.mqcdn.com/tiles/1.0.0/sat/${z}/${x}/${y}.png"], {
        numZoomLevels: 21,
        attribution: 'Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency. Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">',
        transitionEffect: "resize"
    });
    OSMterrain = new OpenLayers.Layer.XYZ("OpenStreetMap Terrain", "http://tile.stamen.com/terrain/${z}/${x}/${y}.jpg", {
        attribution: 'Map tiles by <a href="http://stamen.com/" target="_blank">Stamen Design</a>, under <a href="http://creativecommons.org/licenses/by/3.0" target="_blank">CC BY 3.0</a>. Data by <a href="http://openstreetmap.org" target="_blank">OpenStreetMap</a>, under <a href="http://creativecommons.org/licenses/by-sa/3.0" target="_blank">CC BY SA</a>',
        transitionEffect: "resize",
        tileOptions: {
            crossOriginKeyword: null
        }
    });
    USGSimagery = new OpenLayers.Layer.ArcGIS93Rest("USGS 1 Meter NAIP Imagery", "http://raster.nationalmap.gov/ArcGIS/rest/services/TNM_Large_Scale_Imagery_Overlay/MapServer/export", {
        layers: '2,3',
        format: 'png'
    });
    USGSdrg24k = new OpenLayers.Layer.ArcGIS93Rest("USGS Scanned Topo (24K/25k)", "http://raster.nationalmap.gov/ArcGIS/rest/services/DRG/TNM_Digital_Raster_Graphics/MapServer/export", {
        layers: '4,19',
        format: 'png'
    });
    USGSdrg100k = new OpenLayers.Layer.ArcGIS93Rest("USGS Scanned Topo (100K)", "http://raster.nationalmap.gov/ArcGIS/rest/services/DRG/TNM_Digital_Raster_Graphics/MapServer/export", {
        layers: '22',
        format: 'png'
    });
    USGSdrg250k = new OpenLayers.Layer.ArcGIS93Rest("USGS Scanned Topo (250K)", "http://raster.nationalmap.gov/ArcGIS/rest/services/DRG/TNM_Digital_Raster_Graphics/MapServer/export", {
        layers: '24',
        format: 'png'
    });

    layerRuler = new OpenLayers.Layer.Vector("Measurements", {
        displayInLayerSwitcher: false
    });

    highlightLayer = new OpenLayers.Layer.Vector("Highlighted Features", {
	displayInLayerSwitcher: false,
	isBaseLayer: false,
	styleMap: new OpenLayers.StyleMap({
		"default": new OpenLayers.Style(OpenLayers.Util.applyDefaults({
			strokeColor: '#4BFFFF',
			strokeWidth: 3,
            fillOpacity: 0
		}, OpenLayers.Feature.Vector.style["default"]))
	})
	});

    nysdopLatest = new OpenLayers.Layer.ArcGIS93Rest("Latest NYSDOP Orthoimagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/Latest/MapServer/export", {
        layers: "0,1,2,3,4,5",
        format: "png24"
    }, {
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });
    nysdop11 = new OpenLayers.Layer.XYZ("2011 NYSDOP Imagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/2011/MapServer/tile/${z}/${y}/${x}", {
        sphericalMercator: true,
        numZoomLevels: 20,
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });
    nysdop10 = new OpenLayers.Layer.XYZ("2010 NYSDOP Imagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/2010/MapServer/tile/${z}/${y}/${x}", {
        sphericalMercator: true,
        numZoomLevels: 20,
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });
    nysdop09 = new OpenLayers.Layer.XYZ("2009 NYSDOP Imagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/2009/MapServer/tile/${z}/${y}/${x}", {
        sphericalMercator: true,
        numZoomLevels: 20,
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });
    nysdop08 = new OpenLayers.Layer.XYZ("2008 NYSDOP Imagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/2008/MapServer/tile/${z}/${y}/${x}", {
        sphericalMercator: true,
        numZoomLevels: 20,
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });
    nappImagery = new OpenLayers.Layer.XYZ("1994-1999 USGS NAPP Imagery", "http://www.orthos.dhses.ny.gov/ArcGIS/rest/services/NAPP/MapServer/tile/${z}/${y}/${x}", {
        sphericalMercator: true,
        numZoomLevels: 18,
        attribution: '<a style="color: black" href="http://orthos.dhses.ny.gov/" target="_blank">New York Statewide Digital Orthoimagery Program (NYSDOP)</a>',
        transitionEffect: "resize"
    });

    ridgeRADAR = new OpenLayers.Layer.WMS("NWS RIDGE Radar", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/obs", {
        layers: "RAS_RIDGE_NEXRAD",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RAS_RIDGE_NEXRAD&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    cloudImagery = new OpenLayers.Layer.WMS("Cloud Imagery (GOES Infrared)", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/obs", {
        layers: "RAS_GOES_I4",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    surfaceAirTemp = new OpenLayers.Layer.WMS("Surface Air Temperature", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "RTMA_RAS_AIRTEMP",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RTMA_RAS_AIRTEMP&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    surfaceWindSpeed = new OpenLayers.Layer.WMS("Surface Wind Speed", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "RTMA_RAS_WSPD",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RTMA_RAS_WSPD&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    surfaceWindVelocity = new OpenLayers.Layer.WMS("Surface Wind Velocity", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "RTMA_PT_WINDVECT_01,RTMA_PT_WINDVECT_05,RTMA_PT_WINDVECT_10,RTMA_PT_WINDVECT_15",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RTMA_PT_WINDVECT_01,RTMA_PT_WINDVECT_05,RTMA_PT_WINDVECT_10,RTMA_PT_WINDVECT_15&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation1hr = new OpenLayers.Layer.WMS("1-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "RTMA_RAS_QPE_1HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RTMA_RAS_QPE_1HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation3hr = new OpenLayers.Layer.WMS("3-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_3HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=NSSL_RAS_QPE_3HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation6hr = new OpenLayers.Layer.WMS("6-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_6HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=NSSL_RAS_QPE_6HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation12hr = new OpenLayers.Layer.WMS("12-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_12HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=NSSL_RAS_QPE_12HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation24hr = new OpenLayers.Layer.WMS("24-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_24HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=NSSL_RAS_QPE_24HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation48hr = new OpenLayers.Layer.WMS("48-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_48HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=NSSL_RAS_QPE_48HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    precipAccumulation72hr = new OpenLayers.Layer.WMS("72-hour Precipitation Accumulation", "http://nowcoast.noaa.gov/wms/com.esri.wms.Esrimap/analyses", {
        layers: "NSSL_RAS_QPE_72HR",
        transparent: true,
        format: "image/png"
    }, {
        attribution: "<img src='http://nowcoast.noaa.gov/LayerInfo?layer=RTMA_RAS_QPE_72HR&data=legend'></img>",
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 0.75,
        visibility: false,
        displayInLayerSwitcher: true
    });
    blueline = new OpenLayers.Layer.WMS("Adirondack Park Boundary", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:blueline",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: true,
        displayInLayerSwitcher: true
    });
    citytown = new OpenLayers.Layer.WMS("Cities & Towns", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:citytown",
        transparent: true,
        format: "image/png"/*,
        cql_filter: "name LIKE '%'"*/
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    landclass2012 = new OpenLayers.Layer.WMS("Land Use/Development Plan Map", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:landclass2012",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    streamflow = new OpenLayers.Layer.WMS("USGS Streamflow (Real-Time)", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:streamflow",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    usgsNHD = new OpenLayers.Layer.ArcGIS93Rest("National Hydrography Dataset (NHD)", "http://services.nationalmap.gov/ArcGIS/rest/services/nhd/MapServer/export", {
        layers: 'show:0,15,28',
        transparent: true,
        format: 'png'
    }, {
        isBaseLayer: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    watershedshuc8 = new OpenLayers.Layer.WMS("Major Watersheds (HUC8)", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:watershedshuc8",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    lakes = new OpenLayers.Layer.WMS("Lakes", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:lakes",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    streams = new OpenLayers.Layer.WMS("Streams", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:streams",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    wetlands = new OpenLayers.Layer.WMS("Wetlands", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:wetlands",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    lakechem1984_1987 = new OpenLayers.Layer.WMS("Lake Chemistry Data 1984-1987", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:lakechem1984_1987",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });

    fishsurveylakes = new OpenLayers.Layer.WMS("Fish Surveys", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:fishsurveylakes",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    terrestrialinvasives = new OpenLayers.Layer.WMS("Terrestrial Invasive Species", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:terrestrialinvasives",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    aquaticinvasives = new OpenLayers.Layer.WMS("Aquatic Invasive Species", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:aquaticinvasives",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    campsite = new OpenLayers.Layer.WMS("Campsites", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:campsite",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    slt = new OpenLayers.Layer.WMS("Public Trails", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:slt",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    trailregisters = new OpenLayers.Layer.WMS("Trail Registers", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:trailregisters",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });

        NLCD06 = new OpenLayers.Layer.ArcGIS93Rest("NLCD 2006 Land Cover", "http://raster.nationalmap.gov/ArcGIS/rest/services/LandCover/LandCover(NLCD)/MapServer/export", {
        layers: 'show:3',
        format: 'png'
    }, {
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    impervious06 = new OpenLayers.Layer.ArcGIS93Rest("NLCD 2006 Impervious Surface", "http://raster.nationalmap.gov/ArcGIS/rest/services/LandCover/LandCover(NLCD)/MapServer/export", {
        layers: 'show:4',
        format: 'png'
    }, {
        isBaseLayer: false,
        singleTile: true,
        ratio: 1,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    firemap1916 = new OpenLayers.Layer.WMS("1916 Fire Protection Map", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:firemap1916",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    blowdown1950 = new OpenLayers.Layer.WMS("1950 Blowdown Map", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:blowdown1950",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    landclass2001 = new OpenLayers.Layer.WMS("2001 Land Use and Development Plan Map (APA)", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:landclass2001",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });
    leantos = new OpenLayers.Layer.WMS("Lean-Tos", "http://208.105.130.200:8080/geoserver/argis/wms", {
        layers: "argis:leantos",
        transparent: true,
        format: "image/png"
    }, {
        isBaseLayer: false,
        singleTile: false,
        opacity: 1,
        visibility: false,
        displayInLayerSwitcher: true
    });

    map.addLayers([gphy,bingmap,mapquestOSM, OSMterrain, USGSimagery, USGSdrg24k, USGSdrg100k, USGSdrg250k, nysdop11, nysdopLatest, bingsat, binghyb, gmap, gsat, ghyb, precipAccumulation72hr, precipAccumulation48hr, precipAccumulation24hr, precipAccumulation12hr, precipAccumulation6hr, precipAccumulation3hr, precipAccumulation1hr, surfaceWindVelocity, surfaceWindSpeed, surfaceAirTemp, ridgeRADAR,  landclass2001, landclass2012, blowdown1950, firemap1916, streams, wetlands,lakes, aquaticinvasives, fishsurveylakes, lakechem1984_1987,watershedshuc8, citytown,  blueline, slt, streamflow, trailregisters, campsite, terrestrialinvasives, leantos, highlightLayer, layerRuler]);

    var treeConfig = [{
        text: "<b>&nbsp;Administrative Boundaries</b>",
        expanded: true,
        singleClickExpand: true,
        children: [{
            nodeType: "gx_layer",
            layer: blueline,
            extent: [-75.326, 43.041, -73.281, 44.884],
            originator: "New York State Adirondack Park Agency",
            updated: "1993-09-14",
            metadata: "http://www.apa.state.ny.us/gis/shared/htmlpages/metadata/blueline.html",
            abstract: "Adirondack Park Boundary. Polygon data produced by the Adirondack Park Agency. The legal Park boundary description is found in New York State Environmental Conservation Law Section 9-0101.",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: citytown,
            extent: [-79.997, 40.386, -71.45, 45.021],
            originator: "New York State Office of Cyber Security (OCS)",
            updated: "2011-04-30",
            metadata: "http://www.nysgis.state.ny.us/gisdata/metadata/alis.civil_boundaries.city_town.xml",
            abstract: "A vector file of New York State incorporated city and town boundaries.",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: landclass2012,
            extent: [-75.329, 43.039, -73.26, 44.885],
            originator: "New York State Adirondack Park Agency",
            updated: "2011-04-01",
            metadata: "http://www.apa.state.ny.us/gis/shared/htmlpages/metadata/apalandclass.html",
            abstract: "Adirondack Park Land Use and Development Plan Map and State Land Map (APLUDP/SLMP) produced by the Adirondack Park Agency. Contains polygon data representing state and private land classifications and open water. Mapped at various scales from tax maps and 1:24000 scale base maps.",
            queries: [
                ["All Classes", "lccd < 15"],
                ["Hamlet", "lccd = '1'"],
                ["Moderate Intensity Use", "lccd = '2'"],
                ["Low Intensity Use", "lccd = '3'"],
                ["Rural Use", "lccd = '4'"],
                ["Resource Management", "lccd = '5'"],
                ["Industrial Use", "lccd = '6'"],
                ["Wilderness", "lccd = '7'"],
                ["Canoe Area", "lccd = '8'"],
                ["Primitive Area", "lccd = '9'"],
                ["Wild Forest", "lccd = '10'"],
                ["Intensive Use", "lccd = '11'"],
                ["Historic", "lccd = '12'"],
                ["State Administrative", "lccd = '13'"],
                ["Pending Classification", "lccd = '14'"]
            ]
        }]
    },
    {
        text: "<b>&nbsp;Recreation</b>",
        expanded: true,
        singleClickExpand: true,
        children: [{
            nodeType: "gx_layer",
            layer: leantos,
            extent: [-75.329, 43.039, -73.26, 44.885],
            originator: "New York State Department of Environmental Conservation",
            updated: "2012-02-06",
            metadata: "",
            abstract: "This dataset was created by querying the NYS DEC \"State Land Assets\" layer WHERE asset=\"LEAN-TO\"",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: campsite,
            extent: [-75.198, 43.112, -73.339, 44.775],
            originator: "New York State Department of Environmental Conservation",
            updated: "2012-01-12",
            metadata: "",
            abstract: "This dataset was created by querying the NYS DEC \"State Land Assets\" layer WHERE asset contains=\"CAMPSITE\"",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: slt,
            extent: [-77.201, 41.854, -73.219, 44.831],
            originator: "New York State Department of Environmental Conservation",
            updated: "2012-02-06",
            metadata: "http://www.nysgis.state.ny.us/gisdata/metadata/nysdec.rds_trls.xml",
            abstract: "Line data locating and differentiating transportation corridors on state DEC lands.",
            queries: [
                ["All Uses", "foot <> 'H'"],
                ["Foot", "foot <> 'N'"],
                ["Horse", "horse <> 'N'"],
                ["Ski", "xc <>= 'N'"],
                ["Bike", "bike <> 'N'"],
                ["Motor Vehicle", "motorv <> 'N'"],
                ["Snowmobile", "snowmb <> 'N'"],
                ["Handicap Accessible", "accessible <> 'N'"]
            ]
        },
        {
            nodeType: "gx_layer",
            layer: trailregisters,
            extent: [-77.201, 41.854, -73.219, 44.831],
            originator: "New York State Department of Environmental Conservation",
            updated: "2012-02-06",
            metadata: "",
            abstract: "This dataset was created by querying the NYS DEC \"State Land Assets\" layer WHERE asset=\"TRAILHEAD REGISTER\"",
            queries: []
        }]
    },
    {
        text: "<b>&nbsp;Water</b>",
        expanded: false,
        singleClickExpand: true,
        children: [{
            text: "&nbsp;General Hydrography",
            expanded: true,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: usgsNHD,
                extent: [-76.084, 42.599, -73.197, 45.022]
            },
            {
                nodeType: "gx_layer",
                layer: streamflow,
                extent: [-76.084, 42.599, -73.197, 45.022],
                originator: "U.S. Geological Survey",
                updated: "Hourly",
                metadata: "http://waterwatch.usgs.gov/index.php",
                abstract: "U.S. Geological Survey (USGS) current streamflow conditions for the NY State. The real-time information generally is updated on an hourly basis.",
                queries: []
            },
            {
                nodeType: "gx_layer",
                layer: watershedshuc8,
                extent: [-76.084, 42.599, -73.197, 45.022],
                originator: "U.S. Federal and State Agencies",
                updated: "2011-03-18",
                metadata: "http://www.geodata.gov/E-FW/DiscoveryServlet?uuid={4BDFFA3C-710B-4A27-B8F5-A82C0F32FEE8}&xmltransform=metadata_details.xsl",
                abstract: "The Watershed Boundary Dataset (WBD) is a complete digital hydrologic unit national boundary layer that is at the Subwatershed (12-digit) level. It is composed of the watershed boundaries delineated by state agencies at the 1:24,000 scale. Please refer to the individual state metadata as the primary reference source. To access state specific metadata, go to the following link to view documentation created by agencies that performed the watershed delineation. ftp://ftp.ftw.nrcs.usda.gov/pub/wbd/hu/metadata.",
                queries: []
            },
            {
                nodeType: "gx_layer",
                layer: streams,
                extent: [-79.997, 40.386, -71.45, 45.136],
                originator: "NY State Department of Environmental Conservation,Division of Fish, Wildlife Marine Resources",
                updated: "2011-06-28",
                metadata: "http://www.dec.ny.gov/outdoor/7927.html",
                abstract: "Division of Fish, Wildlife Marine Resources added state-wide Fisheries Identification Numbers (FINs) to the National Hydrography Dataset (NHD) flowline layer.",
                cql_filter: "",
                queries: []
            },
            {
                nodeType: "gx_layer",
                layer: wetlands,
                extent: [-75.3611878, 42.8823122, -73.2398122, 45.003687799999994],
                originator: "New York State Adirondack Park Agency",
                updated: "2010-12-01",
                metadata: "http://www.apa.state.ny.us/gis/shared/htmlpages/data.html#wetl",
                abstract: "Wetlands of the Adirondack Park from airphoto interpretation, including a modified Cowardin classification according to the National Wetlands Inventory conventions.",
                queries: []
            }]
        },
        {
            text: "&nbsp;Lake Data",
            expanded: true,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: lakes,
                extent: [-75.329, 43.039, -73.261, 44.877],
                originator: "SUNY-ESF Adirondack Ecological Center",
                updated: "2011-04-01",
                metadata: "http://aprgis.org/argis/metadata/AdirondackLakes.html",
                abstract: "This layer was created by intersecting three datasets: 1) the Adirondack Park Agency Land Class & Development Plan Map where lccd=15 (water) 2) DEC Fisheries FIN Lakes, containing\
     Fisheries Identification Numbers and NHD Reachcodes, and 3) Adirondack Nature Conservancy lakes data containing the POND IDs used by the Adirondack Lakes Survey Corporation and The Adirondack Invasive Plan\
    ts Program (APIPP).",
                queries: []
            },
            {
                nodeType: "gx_layer",
                layer: lakechem1984_1987,
                extent: [-75.314, 43.105, -73.413, 44.858],
                originator: "Adirondack Lakes Survey Corporation",
                updated: "2001-07-16",
                metadata: "http://www.adirondacklakessurvey.org/",
                abstract: "Reported water chemistry data are from the intensive lake chemistry survey conducted between the months of July and August from 1984 &#8211; 1987 as part of the larger Adirondack lakes survey of chemistry and fish. This intensive sampling took place in the summer when most Adirondack waters are typically stratified. When possible, the middle or deepest part of the lake was sampled to avoid the effects of littoral zones and inlet streams. Samples in isothermal waters were collected 1.5 meters below the surface or at mid-depth when the waters were less than 1.5 meters deep. In stratified waters, a sample was collected at 1.5 meters below the surface and also half way between the thermocline and the bottom. Chemical analysis was conducted at the Adirondack Lakes Survey Corporation (ALSC) laboratory in Ray Brook following EPA guidelines. Table 1 lists the chemical parameters analyzed and units of concentration reported.",
                queries: []
            },
            {
                nodeType: "gx_layer",
                layer: fishsurveylakes,
                extent: [-75.329, 43.039, -73.261, 44.877],
                originator: "NY State Department of Environmental Conservation,Division of Fish, Wildlife Marine Resources",
                updated: "2012-02-16",
                metadata: "http://www.dec.ny.gov/outdoor/7927.html",
                abstract: "Fish surveys of selected Adirondack lakes.  Disclaimer of Warranties and Accuracy of Data, NYSDEC Bureau of Fisheries Modern Statewide Fisheries Database: No warranty, expressed or implied, is made regarding the validity, accuracy, adequacy, completeness, legality, reliability or usefulness of any information provided from this database. The data are provided on an \"as is\" basis. All warranties of any kind, express or implied, including but not limited to implied warranties of merchantability, fitness for a particular purpose, freedom from contamination by computer viruses and non-infringement of proprietary rights are disclaimed. Data can also quickly become out-of-date and changes may periodically be made to the information transmitted herewith; these changes may or may not be incorporated in any new version of the database or any information provided from said database. Be aware that electronic data can be altered subsequent to original distribution. If any errors or omissions are discovered, please report them to the person who supplied you the data.",
                queries: [
                    ["All species", "specieslist LIKE '%'"],
                    ["Unknown Spp", "specieslist LIKE '%*** Unknown Spp ***%'"],
                    ["Alewife", "specieslist LIKE '%Alewife%'"],
                    ["American Eel", "specieslist LIKE '%American Eel%'"],
                    ["Atlantic Salmon", "specieslist LIKE '%Atlantic Salmon%'"],
                    ["Banded Killifish", "specieslist LIKE '%Banded Killifish%'"],
                    ["Black Bullhead", "specieslist LIKE '%Black Bullhead%'"],
                    ["Black Crappie", "specieslist LIKE '%Black Crappie%'"],
                    ["Blackchin Shiner", "specieslist LIKE '%Blackchin Shiner%'"],
                    ["Bluegill", "specieslist LIKE '%Bluegill%'"],
                    ["Bluntnose Minnow", "specieslist LIKE '%Bluntnose Minnow%'"],
                    ["Bowfin", "specieslist LIKE '%Bowfin%'"],
                    ["Brassy Minnow", "specieslist LIKE '%Brassy Minnow%'"],
                    ["Bridle Shiner", "specieslist LIKE '%Bridle Shiner%'"],
                    ["Brook Silverside", "specieslist LIKE '%Brook Silverside%'"],
                    ["Brook Stickleback", "specieslist LIKE '%Brook Stickleback%'"],
                    ["Brook Trout", "specieslist LIKE '%Brook Trout%'"],
                    ["Brown Bullhead", "specieslist LIKE '%Brown Bullhead%'"],
                    ["Brown Trout", "specieslist LIKE '%Brown Trout%'"],
                    ["Burbot", "specieslist LIKE '%Burbot%'"],
                    ["Central Mudminnow", "specieslist LIKE '%Central Mudminnow%'"],
                    ["Chain Pickerel", "specieslist LIKE '%Chain Pickerel%'"],
                    ["Channel Catfish", "specieslist LIKE '%Channel Catfish%'"],
                    ["Cisco Or Lake Herring", "specieslist LIKE '%Cisco Or Lake Herring%'"],
                    ["Common Carp", "specieslist LIKE '%Common Carp%'"],
                    ["Common Shiner", "specieslist LIKE '%Common Shiner%'"],
                    ["Creek Chub", "specieslist LIKE '%Creek Chub%'"],
                    ["Creek Chubsucker", "specieslist LIKE '%Creek Chubsucker%'"],
                    ["Cutlip Minnow", "specieslist LIKE '%Cutlip Minnow%'"],
                    ["Eastern Blacknose Dace", "specieslist LIKE '%Eastern Blacknose Dace%'"],
                    ["Eastern Sand Darter", "specieslist LIKE '%Eastern Sand Darter%'"],
                    ["Eastern Silvery Minnow", "specieslist LIKE '%Eastern Silvery Minnow%'"],
                    ["Emerald Shiner", "specieslist LIKE '%Emerald Shiner%'"],
                    ["Fallfish", "specieslist LIKE '%Fallfish%'"],
                    ["Fathead Minnow", "specieslist LIKE '%Fathead Minnow%'"],
                    ["Freshwater Drum", "specieslist LIKE '%Freshwater Drum%'"],
                    ["Gizzard Shad", "specieslist LIKE '%Gizzard Shad%'"],
                    ["Golden Shiner", "specieslist LIKE '%Golden Shiner%'"],
                    ["Goldfish", "specieslist LIKE '%Goldfish%'"],
                    ["Grass Carp", "specieslist LIKE '%Grass Carp%'"],
                    ["Grass Pickerel", "specieslist LIKE '%Grass Pickerel%'"],
                    ["Greater Redhorse", "specieslist LIKE '%Greater Redhorse%'"],
                    ["Green Sunfish", "specieslist LIKE '%Green Sunfish%'"],
                    ["Highfin Carpsucker", "specieslist LIKE '%Highfin Carpsucker%'"],
                    ["Iowa Darter", "specieslist LIKE '%Iowa Darter%'"],
                    ["Johnny Darter", "specieslist LIKE '%Johnny Darter%'"],
                    ["Lake Chub", "specieslist LIKE '%Lake Chub%'"],
                    ["Lake Sturgeon", "specieslist LIKE '%Lake Sturgeon%'"],
                    ["Lake Trout", "specieslist LIKE '%Lake Trout%'"],
                    ["Lake Whitefish", "specieslist LIKE '%Lake Whitefish%'"],
                    ["Largemouth Bass", "specieslist LIKE '%Largemouth Bass%'"],
                    ["Leatherjack", "specieslist LIKE '%Leatherjack%'"],
                    ["Lepomis Sp.", "specieslist LIKE '%Lepomis Sp.%'"],
                    ["Lepomis sp", "specieslist LIKE '%Lepomis sp%'"],
                    ["Logperch", "specieslist LIKE '%Logperch%'"],
                    ["Longear Sunfish", "specieslist LIKE '%Longear Sunfish%'"],
                    ["Longnose Gar", "specieslist LIKE '%Longnose Gar%'"],
                    ["Longnose Sucker", "specieslist LIKE '%Longnose Sucker%'"],
                    ["Margined Madtom", "specieslist LIKE '%Margined Madtom%'"],
                    ["Mimic Shiner", "specieslist LIKE '%Mimic Shiner%'"],
                    ["Minnow & Carp Family", "specieslist LIKE '%Minnow & Carp Family%'"],
                    ["Mooneye", "specieslist LIKE '%Mooneye%'"],
                    ["Moxostoma sp", "specieslist LIKE '%Moxostoma sp%'"],
                    ["Muskellunge", "specieslist LIKE '%Muskellunge%'"],
                    ["New World Silverside Family", "specieslist LIKE '%New World Silverside Family%'"],
                    ["Northern Hog Sucker", "specieslist LIKE '%Northern Hog Sucker%'"],
                    ["Northern Pike", "specieslist LIKE '%Northern Pike%'"],
                    ["Northern Redbelly Dace", "specieslist LIKE '%Northern Redbelly Dace%'"],
                    ["Pearl Dace", "specieslist LIKE '%Pearl Dace%'"],
                    ["Pike Family", "specieslist LIKE '%Pike Family%'"],
                    ["Pumpkinseed", "specieslist LIKE '%Pumpkinseed%'"],
                    ["Rainbow Smelt", "specieslist LIKE '%Rainbow Smelt%'"],
                    ["Rainbow Trout", "specieslist LIKE '%Rainbow Trout%'"],
                    ["Redbreast Sunfish", "specieslist LIKE '%Redbreast Sunfish%'"],
                    ["Redfin Pickerel", "specieslist LIKE '%Redfin Pickerel%'"],
                    ["Rock Bass", "specieslist LIKE '%Rock Bass%'"],
                    ["Rosyface Shiner", "specieslist LIKE '%Rosyface Shiner%'"],
                    ["Round Goby", "specieslist LIKE '%Round Goby%'"],
                    ["Round Whitefish", "specieslist LIKE '%Round Whitefish%'"],
                    ["Rudd", "specieslist LIKE '%Rudd%'"],
                    ["Sand Shiner", "specieslist LIKE '%Sand Shiner%'"],
                    ["Satinfin Shiner", "specieslist LIKE '%Satinfin Shiner%'"],
                    ["Sculpin Family", "specieslist LIKE '%Sculpin Family%'"],
                    ["Sea Lamprey", "specieslist LIKE '%Sea Lamprey%'"],
                    ["Shorthead Redhorse", "specieslist LIKE '%Shorthead Redhorse%'"],
                    ["Silver Lamprey", "specieslist LIKE '%Silver Lamprey%'"],
                    ["Silver Redhorse", "specieslist LIKE '%Silver Redhorse%'"],
                    ["Slimy Sculpin", "specieslist LIKE '%Slimy Sculpin%'"],
                    ["Smallmouth Bass", "specieslist LIKE '%Smallmouth Bass%'"],
                    ["Sockeye Salmon", "specieslist LIKE '%Sockeye Salmon%'"],
                    ["Splake", "specieslist LIKE '%Splake%'"],
                    ["Spotfin Shiner", "specieslist LIKE '%Spotfin Shiner%'"],
                    ["Spottail Shiner", "specieslist LIKE '%Spottail Shiner%'"],
                    ["Stonecat", "specieslist LIKE '%Stonecat%'"],
                    ["Sucker Family", "specieslist LIKE '%Sucker Family%'"],
                    ["Sunfish Family", "specieslist LIKE '%Sunfish Family%'"],
                    ["Tadpole Madtom", "specieslist LIKE '%Tadpole Madtom%'"],
                    ["Tessellated Darter", "specieslist LIKE '%Tessellated Darter%'"],
                    ["Tiger Musky", "specieslist LIKE '%Tiger Musky%'"],
                    ["Tiger Trout", "specieslist LIKE '%Tiger Trout%'"],
                    ["Trout Family", "specieslist LIKE '%Trout Family%'"],
                    ["Trout-Perch", "specieslist LIKE '%Trout-Perch%'"],
                    ["Walleye", "specieslist LIKE '%Walleye%'"],
                    ["White Bass", "specieslist LIKE '%White Bass%'"],
                    ["White Crappie", "specieslist LIKE '%White Crappie%'"],
                    ["White Perch", "specieslist LIKE '%White Perch%'"],
                    ["White Sucker", "specieslist LIKE '%White Sucker%'"],
                    ["Yellow Bullhead", "specieslist LIKE '%Yellow Bullhead%'"],
                    ["Yellow Perch", "specieslist LIKE '%Yellow Perch%'"]
                ]
            }]
        }]
    },
    {
        text: "<b>&nbsp;Invasive Species</b>",
        expanded: false,
        singleClickExpand: true,
        children: [{
            nodeType: "gx_layer",
            layer: terrestrialinvasives,
            extent: [-75.329, 43.039, -73.261, 44.877],
            originator: "Adirondack Park Invasive Plants Program",
            updated: "2012-03-19",
            metadata: "http://www.adkinvasives.com/terrestrial/Program/Program.html",
            abstract: "These data include information collected by APIPP volunteer plant monitors, as well as documentation provided by other monitoring or plant identification programs operating in the Adirondack Park. The latter include the Darrin Fresh Water Institute plant identification program, the N.Y. Dept. of Environmental Conservation's Citizens Statewide Lake Assessment Program (CSLAP), and reports from Paul Smith College's Adirondack Watershed Institute. It is important to note that methods for data collection may vary by program. Data definitions and methodological variations are documented in the APIPP meta-data summary.",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: aquaticinvasives,
            extent: [-75.741, 42.969, -73.075, 45.1],
            originator: "Adirondack Park Invasive Plants Program",
            updated: "2012-04-01",
            metadata: "http://www.adkinvasives.com/Aquatic/Maps/Maps.asp",
            abstract: "These data include information collected by APIPP volunteer plant monitors, as well as documentation provided by other monitoring or plant identification programs operating in the Adirondack Park. The latter include the Darrin Fresh Water Institute plant identification program, the N.Y. Dept. of Environmental Conservation's Citizens Statewide Lake Assessment Program (CSLAP), and reports from Paul Smith College's Adirondack Watershed Institute. It is important to note that methods for data collection may vary by program. Data definitions and methodological variations are documented in the APIPP meta-data summary.",
            queries: [
                ["All Species", "species LIKE '%'"],
                ["Curlyleaf Pondweed", "species='Curlyleaf Pondweed'"],
                ["Eurasian Watermilfoil", "species='Eurasian Watermilfoil'"],
                ["Fanwort", "species='Fanwort'"],
                ["Variable-Leaf Milfoil", "species='Variable-Leaf Milfoil'"],
                ["Water Chestnut", "species='Water Chestnut'"]
            ]
        }]
    },
    {
        text: "<b>&nbsp;Historic</b>",
        expanded: false,
        singleClickExpand: true,
        children: [{
            nodeType: "gx_layer",
            layer: firemap1916,
            extent: [-75.327, 43.039, -73.281, 44.882],
            originator: "Applied GIS/Adirondack Park Agency",
            updated: "2000-01-01",
            metadata: "http://aprgis.org/argis/metadata/ADK_1916_firemap.html",
            abstract: "This coverage contains the 1916 fire protection areas of the Adirondack Park, New York State.  The data was created for the Adirondack Park Agency (APA) as an historical reference. This resource may be used to determine the recent history of land use in the Adirondack Park and major disturbances to the landscape. Such known and documented disturbances include a series of major forest fires that occurred at the turn of the 19th Century. Data is to be used at the scale intended (1:126720). Usage Notes: The hardcopy color map from which this digital data set was created is printed on paper adhered to a cloth/linen backing and attached on the top and bottom to long wooden sticks. The different land codes are color-coded and described in the legend. Supplemental Information: Digital data was developed by for the area defined by the Oswegatchie/Black River watershed in by SUNY Plattsburg in conduction with the APA.",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: blowdown1950,
            extent: [-75.933, 43.09, -73.476, 44.71],
            originator: "New York State Department of Environmental Conservation",
            updated: "2000-01-01",
            metadata: "http://aprgis.org/argis/metadata/1950blowdown.html",
            abstract: "This coverage contains areas damaged by a 1950 windstorm in and around the Adirondack Park, New York State.The data was created for the Adirondack Park Agency (APA) as an historical reference. This resource may be used to determine the recent history of land use and major disturbances in the Adirondack Park. The hardcopy map from which this digital data set was created is a creased paper blueprint developed by the New York State Conservation Department. According to the map, the areas of storm damage were determined from aerial reconnaissance data. The blueprint was drawn by the Forest General of the New York State Conservation Department, Gerald J. Rides. The scale bar indicates the map was drawn at a 1 inch = 4 miles scale. A legend appears at the bottom of the map with the following information:25-50% Blowdown 50-100% Blowdown County lines Adirondack blueline State land The 25-50% Blowdown category polygons are indicated by a pale dashed outline. These lines are very faint in some instances. A digital Arc/Info interchange file containing a nearly complete representation of the 1950 Blowdown map was created by the staff from the Center for Remote Sensing at SUNY Plattsburg. The polygons on the original map were copied on to a cartographically correct 1:250,000 NYS DOT base map, then digitized.",
            queries: []
        },
        {
            nodeType: "gx_layer",
            layer: landclass2001,
            extent: [-75.329, 43.039, -73.26, 44.885],
            originator: "New York State Adirondack Park Agency",
            updated: "2006-10-01",
            metadata: "http://www.apa.state.ny.us/gis/shared/htmlpages/metadata/apalandclass.html",
            abstract: "Adirondack Park Land Use and Development Plan Map and State Land Map (APLUDP/SLMP), circa 2001, produced by the Adirondack Park Agency. Contains polygon data representing state and private land classifications and open water. Mapped at various scales from tax maps and 1:24000 scale base maps.",
            queries: []
        }]
    },
    {
        text: "<b>&nbsp;Weather</b>",
        expanded: false,
        singleClickExpand: true,
        children: [{
            text: "&nbsp;NOAA nowCOAST (Near Real-Time)",
            expanded: true,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: ridgeRADAR
            },
            {
                nodeType: "gx_layer",
                layer: surfaceAirTemp
            },
            {
                nodeType: "gx_layer",
                layer: surfaceWindVelocity
            },
            {
                text: "&nbsp;Precipitation Accumulation",
                expanded: false,
                singleClickExpand: true,
                children: [{
                    nodeType: "gx_layer",
                    layer: precipAccumulation1hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation3hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation6hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation12hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation24hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation48hr
                },
                {
                    nodeType: "gx_layer",
                    layer: precipAccumulation72hr
                }]
            }]
        }]
    },
    {
        text: "<b>&nbsp;Background Maps</b>",
        expanded: true,
        singleClickExpand: true,
        children: [{
            text: "&nbsp;Open Data Services",
            expanded: false,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: mapquestOSM
            },
            {
                nodeType: "gx_layer",
                layer: OSMterrain
            },
            {
                nodeType: "gx_layer",
                layer: nysdopLatest
            }]
        },
        {
            text: "&nbsp;USGS",
            expanded: false,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: USGSimagery
            },
            {
                nodeType: "gx_layer",
                layer: USGSdrg24k
            },
            {
                nodeType: "gx_layer",
                layer: USGSdrg100k
            },
            {
                nodeType: "gx_layer",
                layer: USGSdrg250k
            }]
        },
        {
            text: "&nbsp;Bing",
            expanded: true,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: bingmap
            },
            {
                nodeType: "gx_layer",
                layer: bingsat
            },
            {
                nodeType: "gx_layer",
                layer: binghyb
            }]
        },
        {
            text: "&nbsp;Google",
            expanded: false,
            singleClickExpand: true,
            children: [{
                nodeType: "gx_layer",
                layer: gmap
            },
            {
                nodeType: "gx_layer",
                layer: gsat
            },
            {
                nodeType: "gx_layer",
                layer: ghyb
            },
            {
                nodeType: "gx_layer",
                layer: gphy
            }]
        }]
    }];

    selectCtrl = new OpenLayers.Control.WMSGetFeatureInfo({
    	url: "http://208.105.130.200:8080/geoserver/argis/wms",
    	title: 'Identify features by clicking',
    	layers: [citytown],
    	queryVisible: true,
    	maxFeatures: 10,
    	infoFormat: 'application/vnd.ogc.gml'
        });
        selectCtrl.events.on({
            getfeatureinfo: function (e) {
                selectFeatures(e);
            }
    });

    function selectFeatures(e) {
    	parcelsHighlight.setVisibility(false);
        highlightLayer.destroyFeatures();
    	if (e.features && e.features.length) {
    		 highlightLayer.destroyFeatures();
    		 highlightLayer.addFeatures(e.features);
    		 highlightLayer.redraw();
    		 feature = e.features;
    	}
    };
    //map.addControl(selectCtrl);
    //selectCtrl.activate();

    infoCtrl = new OpenLayers.Control.WMSGetFeatureInfo({
        url: "http://208.105.130.200:8080/geoserver/argis/wms",
        title: "Identify features by clicking",
        layers: [fishsurveylakes, aquaticinvasives, lakechem1984_1987, wetlands, streams, lakes, watershedshuc8, streamflow, landclass2012, citytown, terrestrialinvasives, slt, trailregisters,campsite, leantos],
        queryVisible: true,
        drillDown: true
    });
    infoCtrl.events.on({
        getfeatureinfo: function (e) {
            if (e.text.length > 687) {
                createPopup(e);
            }
            if (e.text.length <= 687 && popup) {
                popup.destroy();
            }
        }
    });

    function createPopup(e) {
        if (!popup) {
            popup = new GeoExt.Popup({
                title: "Feature Info",
                //width: 400,
                //height: 250,
                //layout: 'fit',
                viewConfig: 'fit',
                margin: 5,
                padding: 5,
                autoScroll: true,
                maximizable: true,
                collapsible: true,
                panIn: false,
                map: map,
                location: map.getLonLatFromPixel(e.xy),
                html: e.text
            });
            popup.show();
            var maxWidth = parseInt(map.getSize().w - map.getSize().w/2);
            var maxHeight = parseInt(map.getSize().h - map.getSize().h/4);
            if (popup.getWidth() > maxWidth) {
                popup.setWidth(maxWidth);
                //popup.show();
            }
            if (popup.getHeight() > maxHeight) {
                popup.setHeight(maxHeight);
                //popup.show();
            }
        }
        if (popup) {
            popup.destroy();
            popup = new GeoExt.Popup({
                title: "Feature Info",
                //width: 400,
                //height: 250,
                //layout: 'fit',
                viewConfig: 'fit',
                margin: 5,
                padding: 5,
                autoScroll: true,
                maximizable: true,
                collapsible: true,
                panIn: false,
                map: map,
                location: map.getLonLatFromPixel(e.xy),
                html: e.text
            });
            popup.show();
            var maxWidth = parseInt(map.getSize().w - map.getSize().w/2);
            var maxHeight = parseInt(map.getSize().h - map.getSize().h/4);
            if (popup.getWidth() > maxWidth) {
                popup.setWidth(maxWidth);
                //popup.show();
            }
            if (popup.getHeight() > maxHeight) {
                popup.setHeight(maxHeight);
                //popup.show();
            }
        }
    }

    map.addControl(infoCtrl);

    function highlightWMS(parcel_id) {
        filter = "M7_TRACTNU = '" + parcel_id + "'";
        var filterParams = {
            cql_filter: filter
        };
        parcelsHighlight.mergeNewParams(filterParams);
        parcelsHighlight.setVisibility(true);
    }

    // Map Navigation control in the 'navigation' toggleGroup
    var panZoom = new GeoExt.Action({
        tooltip: "Pan around the map. Hold control and drag a box to rubber-band zoom.",
        iconCls: "icon-pan",
        toggleGroup: "navigation",
        pressed: true,
        allowDepress: true,
        control: new OpenLayers.Control.Navigation(),
        map: map,
        handler: function () {
            Ext.getCmp("map").body.applyStyles("cursor:default");
            var element = document.getElementById("output");
            element.innerHTML = "";
            layerRuler.removeFeatures(layerRuler.features);
        }
    });
    // Indetify control in the 'navigation' toggleGroup
    var identify = new GeoExt.Action({
        id: "identifyButton",
        tooltip: "Identify Features",
        iconCls: "icon-identify",
        toggleGroup: "navigation",
        pressed: true,
        allowDepress: true,
        control: infoCtrl,
        map: map,
        handler: function () {
            if (Ext.getCmp("identifyButton").pressed === true) {
                //selectCtrl.activate();
				Ext.getCmp("map").body.applyStyles("cursor:help");
				var element = document.getElementById("output");
                element.innerHTML = "";
                layerRuler.removeFeatures(layerRuler.features);
			}
			else if (Ext.getCmp("identifyButton").pressed === false) {
				//selectCtrl.deactivate();
				Ext.getCmp("map").body.applyStyles("cursor:default");
				var element = document.getElementById("output");
                element.innerHTML = "";
                layerRuler.removeFeatures(layerRuler.features);
				if (popup) {
                popup.close();
				}
				//parcelsHighlight.setVisibility(false);
				highlightLayer.destroyFeatures();
            }
        }
    });
    // Clear Selection control in the 'navigation' toggleGroup
    var clearSelect = new Ext.Button({
        tooltip: "Clear Map Graphics",
        iconCls: "icon-clearselect",
        handler: function () {
            if (popup) {
                popup.close();
            }
            //parcelsHighlight.setVisibility(false);
            var element = document.getElementById("output");
            element.innerHTML = "";
            layerRuler.removeFeatures(layerRuler.features);
            length.cancel();
            area.cancel();
            highlightLayer.destroyFeatures();
        }
    });
    // Zoom In control in the 'navigation' toggleGroup
    var zoomIn = new GeoExt.Action({
        id: "zoominButton",
        tooltip: "Zoom In",
        iconCls: "icon-zoomin",
        toggleGroup: "navigation",
        pressed: false,
        allowDepress: true,
        control: new OpenLayers.Control.ZoomBox({
            alwaysZoom: true
        }),
        map: map,
        handler: function () {
            if (Ext.getCmp("zoominButton").pressed === true) {
                Ext.getCmp("map").body.applyStyles("cursor:crosshair");
                var element = document.getElementById("output");
                element.innerHTML = "";
                layerRuler.removeFeatures(layerRuler.features);
            }
            else if (Ext.getCmp("zoominButton").pressed === false) {
                Ext.getCmp("map").body.applyStyles("cursor:default");
            }
        }
    });
    // Zoom Out control
    var zoomOut = new Ext.Button({
        tooltip: "Zoom Out",
        iconCls: "icon-zoomout",
        handler: function () {
            map.zoomOut();
        }
    });
    // Navigation history - two "button" controls
    var navHistoryCtrl = new OpenLayers.Control.NavigationHistory();
    map.addControl(navHistoryCtrl);
    var zoomPrevious = new GeoExt.Action({
        tooltip: "Zoom to Previous Extent",
        iconCls: "icon-zoomprevious",
        control: navHistoryCtrl.previous,
        disabled: true
    });
    var zoomNext = new GeoExt.Action({
        tooltip: "Zoom to Next Extent",
        iconCls: "icon-zoomnext",
        control: navHistoryCtrl.next,
        disabled: true
    });
    // Zoom Extent control
    var zoomExtentBtn = new Ext.Button({
        tooltip: "Zoom to Initial Extent",
        iconCls: "icon-zoomextent",
        handler: function () {
            map.setCenter(mapPanel.initialConfig.center, mapPanel.initialConfig.zoom);
        }
    });
    var linemeasureStyles = {
        "Point": {
            pointRadius: 4,
            graphicName: "square",
            fillColor: "white",
            fillOpacity: 1,
            strokeWidth: 1,
            strokeOpacity: 1,
            strokeColor: "#333333"
        },
        "Line": {
            strokeColor: "#FF0000",
            strokeOpacity: 0.3,
            strokeWidth: 3,
            strokeLinecap: "square"
        }
    };
    var lineStyle = new OpenLayers.Style();
    lineStyle.addRules([
    new OpenLayers.Rule({
        symbolizer: linemeasureStyles
    })]);
    var linemeasureStyleMap = new OpenLayers.StyleMap({
        "default": lineStyle
    });
    var length = new OpenLayers.Control.Measure(OpenLayers.Handler.Path, {
        displaySystem: "english",
        geodesic: true,
        persist: true,
        handlerOptions: {
            layerOptions: {
                styleMap: linemeasureStyleMap
            }
        },
        textNodes: null,
        callbacks: {
            create: function () {
                this.textNodes = [];
                layerRuler.removeFeatures(layerRuler.features);
                mouseMovements = 0;
            },
            modify: function (point, line) {
                if (mouseMovements++ < 5) {
                    return;
                }
                var len = line.geometry.components.length;
                var from = line.geometry.components[len - 2];
                var to = line.geometry.components[len - 1];
                var ls = new OpenLayers.Geometry.LineString([from, to]);
                var dist = this.getBestLength(ls);
                if (!dist[0]) {
                    return;
                }
                var total = this.getBestLength(line.geometry);
                var label = dist[0].toFixed(2) + " " + dist[1];
                var textNode = this.textNodes[len - 2] || null;
                if (textNode && !textNode.layer) {
                    this.textNodes.pop();
                    textNode = null;
                }
                if (!textNode) {
                    var c = ls.getCentroid();
                    textNode = new OpenLayers.Feature.Vector(
                    new OpenLayers.Geometry.Point(c.x, c.y), {}, {
                        label: '',
                        fontColor: "#FF0000",
                        fontSize: "14px",
                        fontFamily: "Arial",
                        fontWeight: "bold",
                        labelAlign: "cm"
                    });
                    this.textNodes.push(textNode);
                    layerRuler.addFeatures([textNode]);
                }
                textNode.geometry.x = (from.x + to.x) / 2;
                textNode.geometry.y = (from.y + to.y) / 2;
                textNode.style.label = label;
                textNode.layer.drawFeature(textNode);
                this.events.triggerEvent("measuredynamic", {
                    measure: dist[0],
                    total: total[0],
                    units: dist[1],
                    order: 1,
                    geometry: ls
                });
            }
        }
    });

    function handleMeasurements(event) {
        var geometry = event.geometry;
        var units = event.units;
        var order = event.order;
        var measure = event.measure;
        var element = document.getElementById("output");
        var acres;
        var out = "";
        if (order === 1) {
            out += measure.toFixed(2) + " " + units;
        } else if (order === 2 && units === "ft" && measure >= 43560) {
            acres = measure / 43560;
            out += acres.toFixed(2) + " acres";
        } else {
            out += measure.toFixed(2) + " " + units + "<sup>2</" + "sup>";
        }
        element.innerHTML = "&nbsp;&nbsp;" + out;
    }
    length.events.on({
        "measure": handleMeasurements,
        "measurepartial": handleMeasurements
    });
    var areameasureStyles = {
        "Point": {
            pointRadius: 4,
            graphicName: "square",
            fillColor: "white",
            fillOpacity: 1,
            strokeWidth: 1,
            strokeOpacity: 1,
            strokeColor: "#333333"
        },
        "Polygon": {
            strokeWidth: 3,
            strokeOpacity: 1,
            strokeColor: "red",
            fillColor: "red",
            fillOpacity: 0.3
        }
    };
    var areaStyle = new OpenLayers.Style();
    areaStyle.addRules([
    new OpenLayers.Rule({
        symbolizer: areameasureStyles
    })]);
    var areaStyleMap = new OpenLayers.StyleMap({
        "default": areaStyle
    });
    var area = new OpenLayers.Control.Measure(OpenLayers.Handler.Polygon, {
        displaySystem: "english",
        geodesic: true,
        persist: true,
        handlerOptions: {
            layerOptions: {
                styleMap: areaStyleMap
            }
        }
    });
    area.events.on({
        "measure": handleMeasurements,
        "measurepartial": handleMeasurements
    });
    map.addControl(length);
    map.addControl(area);
    /*var measureLength = new GeoExt.Action({
        id: "measurelengthButton",
        tooltip: "Measure Length",
        iconCls: "icon-measure-length",
        toggleGroup: "navigation",
        pressed: false,
        allowDepress: true,
        control: length,
        group: "measure",
        map: map,
        handler: function () {
            if (Ext.getCmp("measurelengthButton").pressed === true) {
                Ext.getCmp("map").body.applyStyles("cursor:crosshair");
                var element = document.getElementById("output");
                element.innerHTML = "";
            }
            else if (Ext.getCmp("measurelengthButton").pressed === false) {
                Ext.getCmp("map").body.applyStyles("cursor:default");
            }
        }
    });
    var measureArea = new GeoExt.Action({
        id: "measureareaButton",
        tooltip: "Measure Area",
        iconCls: "icon-measure-area",
        toggleGroup: "navigation",
        pressed: false,
        allowDepress: true,
        control: area,
        group: "measure",
        map: map,
        handler: function () {
            if (Ext.getCmp("measureareaButton").pressed === true) {
                Ext.getCmp("map").body.applyStyles("cursor:crosshair");
                var element = document.getElementById("output");
                element.innerHTML = "";
                layerRuler.removeFeatures(layerRuler.features);
            }
            else if (Ext.getCmp("measureareaButton").pressed === false) {
                Ext.getCmp("map").body.applyStyles("cursor:default");
            }
        }
    });*/
    this.activeIndex = 0;
    var measureButton = new Ext.SplitButton({
        id: "measureButton",
        tooltip: "Measure Tools",
        iconCls: "icon-measure-length",
        enableToggle: true,
        toggleGroup: "navigation",
        allowDepress: true,
        handler: function (button, event) {
            if (button.pressed) {
                button.menu.items.itemAt(this.activeIndex).setChecked(true);
            }
            else {
                 Ext.getCmp("map").body.applyStyles("cursor:default");
                 //Ext.getCmp("measureButton").setIconClass("icon-measure-length");
            }
        },
        scope: this,
        listeners: {
            toggle: function(button, pressed) {
                // toggleGroup should handle this
                if(!pressed) {
                    button.menu.items.each(function(i) {
                        i.setChecked(false);
                    });
                }
            },
            render: function(button) {
                // toggleGroup should handle this
                Ext.ButtonToggleMgr.register(button);
            }
        },
        menu: new Ext.menu.Menu({
            items: [
                new Ext.menu.CheckItem(
                    new GeoExt.Action({
                        text: "Measure Length",
                        iconCls: "icon-measure-length",
                        toggleGroup: "navigation",
                        group: "measure",
                        listeners: {
                            checkchange: function(item, checked) {
                                this.activeIndex = 0;
                                measureButton.toggle(checked);
                                if (checked) {
                                    measureButton.setIconClass(item.iconCls);
                                    Ext.getCmp("map").body.applyStyles("cursor:crosshair");
                                    var element = document.getElementById("output");
                                    element.innerHTML = "";
                                    layerRuler.removeFeatures(layerRuler.features);
                                }
                            },
                            scope: this
                        },
                        map: map,
                        control: length
                    })
                ),
                new Ext.menu.CheckItem(
                    new GeoExt.Action({
                        text: "Measure Area",
                        iconCls: "icon-measure-area",
                        toggleGroup: "navigation",
                        group: "measure",
                        allowDepress: false,
                        listeners: {
                            checkchange: function(item, checked) {
                                this.activeIndex = 1;
                                measureButton.toggle(checked);
                                if (checked) {
                                    measureButton.setIconClass(item.iconCls);
                                    Ext.getCmp("map").body.applyStyles("cursor:crosshair");
                                    var element = document.getElementById("output");
                                    element.innerHTML = "";
                                    layerRuler.removeFeatures(layerRuler.features);
                                }
                            },
                            scope: this
                        },
                        map: map,
                        control: area
                    })
                )
            ]
        })
    });
    var printButton = new Ext.Button({
        tooltip: "Printer Friendly",
        iconCls: "icon-print",
        handler: function () {
            var a = window.open("", "printwindow");
            a.document.open("text/html");
            a.document.write("<html><head><link rel='stylesheet' type='text/css' href='resources/OpenLayers-2.12.rc4/theme/default/style.css'><link rel='stylesheet' type='text/css' href='css/default.css'><style type='text/css'>.olControlPanPanel {display:none;} .olControlZoomPanel {display:none;}</style></head><body><div class='header'><p style='float:left; padding-left: 25px; padding-top: 1px;'><img style='width: 540px; height: 35px;' alt='Adirondack Regional GIS' src='img/logo3.gif' align='middle'></p></div><div style='clear: both;'></div><table border='0' width='"+mapPanel.map.getSize().w+"' height='"+mapPanel.map.getSize().h+"'><tr><td>"+ document.getElementById(mapDiv).innerHTML +"</td></tr></table><p>http://aprgis.org/argis/</body></html>");
            a.document.close();
            //a.print();
        }
    });

    var downloadButton = new Ext.Button({
        text: "Download Data",
        iconCls: "icon-download",
        handler: function () {
            alert("Coming Soon!");
        }
    });

    var townshipStore = new Ext.data.SimpleStore({
        fields: [{
            name: 'xmin',
            type: 'float'
        }, {
            name: 'ymin',
            type: 'float'
        }, {
            name: 'xmax',
            type: 'float'
        }, {
            name: 'ymax',
            type: 'float'
        }, {
            name: 'label',
            type: 'string'
        }],
        data: [
            ['-74.5586206715527', '44.0991174662061', '-74.3847847166364', '44.3947497407402', 'Altamont'],
            ['-73.7873577657831', '44.7773539475943', '-73.6720173227491', '44.8779090445315', 'Altona'],
            ['-74.7053834685658', '43.2280784832705', '-74.457698269462', '43.9334498001393', 'Arietta'],
            ['-73.6430091522366', '44.4545066227895', '-73.3386155766729', '44.5636113641291', 'Ausable'],
            ['-74.2174537219411', '44.6203489600911', '-73.9547643124327', '44.859734326431', 'Bellmont'],
            ['-74.4722002109117', '43.2146755159259', '-74.2141739758317', '43.3477858387131', 'Benson'],
            ['-73.9473275543822', '44.4300586243362', '-73.6252142450985', '44.6062033989361', 'Black Brook'],
            ['-74.457698269462', '43.1067923521131', '-74.3075465227355', '43.2417096148085', 'Bleecker'],
            ['-73.7510107703459', '43.4747128996248', '-73.5199000854432', '43.6758075170246', 'Bolton'],
            ['-75.2246284221437', '43.5252765073659', '-75.219542361633', '43.5571982682411', 'Boonville'],
            ['-74.3702403007151', '44.6917036233476', '-74.3501134564025', '44.6930973700539', 'Brandon'],
            ['-74.3309985993339', '44.4042870872191', '-74.1421068279779', '44.559764529995', 'Brighton'],
            ['-74.2126810745614', '43.0610641512084', '-74.1139624543592', '43.1437279753554', 'Broadalbin'],
            ['-74.5606731607327', '43.0748263335032', '-74.4398587298145', '43.2327808683612', 'Caroga'],
            ['-74.057620546262', '43.5714553670966', '-73.7447405305831', '43.7711399398906', 'Chester'],
            ['-73.6683621058761', '44.332988209433', '-73.3028013940716', '44.5469058849891', 'Chesterfield'],
            ['-75.083235864833', '44.2547778481367', '-74.9157227907972', '44.4653488272305', 'Clare'],
            ['-75.0488153786113', '44.0668622931773', '-74.7680763962178', '44.3327795777621', 'Clifton'],
            ['-74.9428271478789', '44.0775623093294', '-74.6482773530648', '44.5567202001977', 'Colton'],
            ['-73.9951712149178', '43.1807863354289', '-73.8187970068072', '43.2789492323321', 'Corinth'],
            ['-75.2553170015934', '43.9069327335818', '-75.1552105155539', '44.0491007135332', 'Croghan'],
            ['-73.6489596965604', '43.8808604434716', '-73.4045689896323', '44.0370379241024', 'Crown Point'],
            ['-73.9932809032382', '44.6972248210227', '-73.6585939103471', '44.7840448711955', 'Dannemora'],
            ['-74.1601115149371', '43.262046497217', '-73.9266784902804', '43.3871487798096', 'Day'],
            ['-75.298259071037', '44.0457219315803', '-75.1640060238785', '44.143862379434', 'Diana'],
            ['-74.6075117959634', '44.6765311038366', '-74.4873691339738', '44.7039835259523', 'Dickinson'],
            ['-73.5840510240748', '43.5334767794085', '-73.3936033603783', '43.699503469635', 'Dresden'],
            ['-74.3501134564025', '44.5484104427716', '-74.1697932493257', '44.7036867706588', 'Duane'],
            ['-74.1501920226491', '43.1540583003811', '-73.9774154638046', '43.3231532837046', 'Edinburg'],
            ['-75.1991967406779', '44.2463328715366', '-75.1947294525166', '44.2879961462783', 'Edwards'],
            ['-73.7126180418225', '44.0954517402033', '-73.5090650771086', '44.2552179271677', 'Elizabethtown'],
            ['-74.0065148196231', '44.7577425289799', '-73.7651983598662', '44.8779089661625', 'Ellenburg'],
            ['-74.6107533708275', '43.0523537194591', '-74.5011523828663', '43.1109332843737', 'Ephratah'],
            ['-73.4820089454964', '44.2423413199486', '-73.3112226340083', '44.3252650149282', 'Essex'],
            ['-75.2613038782484', '44.0503679258202', '-74.8893120514559', '44.3061371135772', 'Fine'],
            ['-75.225379052129', '43.4305366157841', '-75.0887622736368', '43.6157127963369', 'Forestport'],
            ['-73.6367646051436', '43.3837455306329', '-73.4577145450284', '43.5728911793043', 'Fort Ann'],
            ['-74.1879991666191', '44.4073703947792', '-73.9096791367741', '44.6500360506584', 'Franklin'],
            ['-73.9799157751149', '43.1599452216621', '-73.9300377594413', '43.1843678061805', 'Greenfield'],
            ['-75.3111286074735', '43.6475763526719', '-75.1142447215564', '43.7764998722101', 'Greig'],
            ['-74.0008243469041', '43.2714988147103', '-73.8243943061472', '43.3977037492187', 'Hadley'],
            ['-73.6442450311368', '43.6372263441244', '-73.4366515937484', '43.8037289608536', 'Hague'],
            ['-74.4092541030009', '44.1116754029688', '-74.0935222551733', '44.4134150725373', 'Harrietstown'],
            ['-74.3104783240943', '43.2490608171402', '-74.1400237150297', '43.3594515402219', 'Hope'],
            ['-74.780889112949', '44.3428581950172', '-74.5539670519118', '44.6989490044317', 'Hopkinton'],
            ['-73.8143888328569', '43.6239188525592', '-73.6282010132564', '43.7842190169314', 'Horicon'],
            ['-74.5593510771477', '43.6196603742609', '-74.0460987469922', '43.9527764711421', 'Indian Lake'],
            ['-74.8200091738076', '43.6508508122285', '-74.6605892525844', '43.8195087077915', 'Inlet'],
            ['-73.8066262384648', '44.2512492778096', '-73.6035891767893', '44.4586141620936', 'Jay'],
            ['-74.2144346775535', '43.5208345507274', '-73.8599798561952', '43.7447413745144', 'Johnsburg'],
            ['-74.5026325513416', '43.0648186942216', '-74.3052803585207', '43.1134055649749', 'Johnstown'],
            ['-73.9759760613887', '44.0671840846313', '-73.6879871848367', '44.3130512389229', 'Keene'],
            ['-73.6096743956344', '43.3837107499094', '-73.5445601814125', '43.3904954116999', 'Kingsbury'],
            ['-73.7712384208988', '43.3735390967777', '-73.6636343137984', '43.5100910450807', 'Lake George'],
            ['-73.8832755531586', '43.2457056531351', '-73.7499882629076', '43.4086101621587', 'Lake Luzerne'],
            ['-74.5382711758199', '43.3805054743952', '-74.3080553441649', '43.8221092200068', 'Lake Pleasant'],
            ['-74.6664869114975', '44.6861806756474', '-74.6045537271857', '44.7039817118244', 'Lawrence'],
            ['-73.6825493783208', '44.2261442209667', '-73.471361709369', '44.3967283056663', 'Lewis'],
            ['-74.8541172171107', '43.7309788006324', '-74.2556681539866', '44.1203658073878', 'Long Lake'],
            ['-75.3196785716762', '43.5630732249831', '-75.1092708479467', '43.6550725987976', 'Lyonsdale'],
            ['-74.3501188317817', '44.693059143569', '-74.1981579466903', '44.8435129921394', 'Malone'],
            ['-74.3223637574174', '43.0581023040577', '-74.2063025678855', '43.2213791998269', 'Mayfield'],
            ['-74.3385390708961', '43.744669421732', '-73.8591221025496', '43.9699913037653', 'Minerva'],
            ['-74.8677042409489', '43.2864923307402', '-74.6274857902229', '43.673824707514', 'Morehouse'],
            ['-73.6353485459822', '43.9905206687481', '-73.4118130165736', '44.1149618053836', 'Moriah'],
            ['-75.2961685345383', '43.8288536187214', '-75.2808817705818', '43.846331375709', 'New Bremen'],
            ['-74.2818689980021', '43.8345587395183', '-73.9508616203459', '44.1408977822977', 'Newcomb'],
            ['-74.1270336075571', '44.1304334457752', '-73.8665916044884', '44.3483988630205', 'North Elba'],
            ['-74.0231159673559', '43.8808020612983', '-73.6182692105044', '44.1007309031145', 'North Hudson'],
            ['-74.2242532805412', '43.1295733089258', '-74.122681228326', '43.2536877533877', 'Northampton'],
            ['-75.010620832924', '43.2437176546415', '-74.8872097366277', '43.2848411571151', 'Norway'],
            ['-75.1024725586779', '43.241385257122', '-74.7757073107201', '43.7315671331918', 'Ohio'],
            ['-74.7357946222131', '43.0877629413368', '-74.6097193735707', '43.1484399133568', 'Oppenheim'],
            ['-74.8373335831123', '44.4510330942373', '-74.7027340627938', '44.6122385741245', 'Parishville'],
            ['-73.7312696002442', '44.5260762406992', '-73.361629568157', '44.633248495038', 'Peru'],
            ['-74.685181282433', '44.0887412738139', '-74.5251783602027', '44.3542030935341', 'Piercefield'],
            ['-74.9849954586047', '44.4634509634985', '-74.9422334483222', '44.4663178284521', 'Pierrepont'],
            ['-75.2812619997954', '44.1362031207123', '-75.1798308397993', '44.2396047749945', 'Pitcairn'],
            ['-73.4407074927955', '44.6275046632807', '-73.3864124238061', '44.6368370140805', 'Plattsburgh'],
            ['-74.1243431750457', '43.0880993074246', '-73.9765777235618', '43.1669504456519', 'Providence'],
            ['-73.4842356755693', '43.6905604224654', '-73.3506463877739', '43.808446436615', 'Putnam'],
            ['-73.7648193624327', '43.2948316959621', '-73.6096683140943', '43.4863466193522', 'Queensbury'],
            ['-75.1023563375467', '43.3199227425691', '-75.0776084941014', '43.400054685393', 'Remsen'],
            ['-75.0674069567431', '44.3515809339601', '-75.0670263978079', '44.3538306184937', 'Russell'],
            ['-75.1041228881886', '43.2847948162281', '-75.0106120469441', '43.4166394065575', 'Russia'],
            ['-74.8802583529485', '43.1442235852301', '-74.6946820783781', '43.3366063989493', 'Salisbury'],
            ['-74.4718711485241', '44.2527271209679', '-74.2671995274528', '44.693059143569', 'Santa Clara'],
            ['-73.9644772113297', '44.583465852978', '-73.660142666732', '44.7251511530087', 'Saranac'],
            ['-73.9338281749001', '43.7629673288918', '-73.6181011290193', '43.9338270090653', 'Schroon'],
            ['-74.1410094499515', '44.3302446342237', '-73.9016918480376', '44.4300586243362', 'St Armand'],
            ['-74.1724396229297', '43.3725818777405', '-73.8512904535998', '43.4875772388569', 'Stony Creek'],
            ['-74.7197257205096', '43.0894221566819', '-74.5345396845962', '43.2895962524248', 'Stratford'],
            ['-74.1824692256263', '43.4457866466541', '-73.8054610299742', '43.5840181956428', 'Thurman'],
            ['-73.6329517704559', '43.786960317999', '-73.3724050145498', '43.9237497048755', 'Ticonderoga'],
            ['-73.8853899795613', '43.3977037492187', '-73.7268241078997', '43.6328476951786', 'Warrensburg'],
            ['-75.3171487312907', '43.7477107155618', '-75.1296249218327', '43.988695831675', 'Watson'],
            ['-74.6029536153246', '44.3852352701066', '-74.432816668763', '44.6847017374943', 'Waverly'],
            ['-75.1701535177517', '43.549678013993', '-74.8083658746872', '44.0969414800579', 'Webb'],
            ['-74.4782635141536', '43.3365121466945', '-74.1580816992712', '43.6776943435793', 'Wells'],
            ['-73.5336211736606', '44.1062267016047', '-73.3167727509352', '44.2579118405996', 'Westport'],
            ['-73.4706784157903', '43.4716542690475', '-73.4267055048088', '43.5829766984799', 'Whitehall'],
            ['-73.498065486257', '44.3099957832243', '-73.2952836816802', '44.4828350434151', 'Willsboro'],
            ['-73.9534661610488', '44.2981829573651', '-73.7462281517406', '44.4426378791829', 'Wilmington']
        ],
        autoLoad: false
    });
    var townshipZoom = new Ext.form.ComboBox({
        tpl: '<tpl for="."><div ext:qtip="{label}" class="x-combo-list-item">{label}</div></tpl>',
        store: townshipStore,
        displayField: "label",
        typeAhead: true,
        mode: "local",
        forceSelection: true,
        triggerAction: 'all',
        width: 115,
        emptyText: "Township Zoom",
        selectOnFocus: true,
        listeners: {
            "select": function (combo, record) {
                //map.setCenter(new OpenLayers.LonLat(record.data.lon, record.data.lat), record.data.zoom);
                //map.setCenter(new OpenLayers.LonLat(record.data.lon, record.data.lat).transform(map.displayProjection, map.projection), record.data.zoom);
                map.zoomToExtent(new OpenLayers.Bounds(record.data.xmin, record.data.ymin, record.data.xmax, record.data.ymax).transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913")));
            }
        }
    });

    var unitStore = new Ext.data.SimpleStore({
        fields: [{
            name: 'xmin',
            type: 'float'
        }, {
            name: 'ymin',
            type: 'float'
        }, {
            name: 'xmax',
            type: 'float'
        }, {
            name: 'ymax',
            type: 'float'
        }, {
            name: 'label',
            type: 'string'
        }],
        data: [
            ['-74.2934951782227', '44.3514671325684', '-74.2810897827148', '44.3586463928223', 'ADIRONDACK FISH HATCHERY'],
            ['-75.1529998779297', '43.9605941772461', '-75.1223983764648', '43.9701194763184', 'ALDER CREEK PRIMITIVE CORRIDOR'],
            ['-75.2838363647461', '44.0674285888672', '-75.0271682739258', '44.2141571044922', 'ALDRICH POND WILD FOREST'],
            ['-74.878173828125', '43.7414207458496', '-74.8709945678711', '43.746883392334', 'ALGER ISLAND CAMPGROUND'],
            ['-75.0174865722656', '44.1274795532227', '-74.9618148803711', '44.143310546875', 'ALICE BROOK PRIMITVE AREA'],
            ['-74.3171691894531', '44.1915740966797', '-74.2563858032227', '44.2097969055176', 'AMPERSAND PRIMITIVE AREA'],
            ['-73.4496078491211', '44.5522766113281', '-73.4222793579102', '44.5784454345703', 'AUSABLE MARSH WMA'],
            ['-73.4453277587891', '44.5663642883301', '-73.4206619262695', '44.5812683105469', 'AUSABLE POINT CAMPGROUND'],
            ['-73.5385894775391', '43.8106384277344', '-73.5049743652344', '43.8241500854492', 'BALD LEDGE PRIMITIVE AREA'],
            ['-75.070182800293', '43.9689712524414', '-75.0204772949219', '43.9950523376465', 'BEAR POND PRIMITIVE CORRIDOR'],
            ['-74.9379196166992', '43.2597236633301', '-74.9345245361328', '43.2608795166016', 'BLACK CREEK STATE FOREST'],
            ['-75.2782821655273', '43.3516502380371', '-74.7584762573242', '43.6953010559082', 'BLACK RIVER WILD FOREST'],
            ['-74.4522933959961', '43.7587242126465', '-74.1495819091797', '43.9916801452637', 'BLUE MTN WILD FOREST'],
            ['-74.6387100219727', '43.7097778320312', '-74.3006286621094', '43.8703155517578', 'BLUE RIDGE WILDERNESS'],
            ['-73.6736831665039', '44.1139869689941', '-73.6597747802734', '44.1232223510742', 'BOQUET RIVER PRIMTIVE AREA'],
            ['-74.1749725341797', '43.1032867431641', '-74.171875', '43.1065788269043', 'BROADALBIN BOAT LAUNCH'],
            ['-74.7081527709961', '43.8007392883301', '-74.6942291259766', '43.8162841796875', 'BROWN TRACT POND CAMPGROUND'],
            ['-74.1223983764648', '44.4979400634766', '-74.1047592163086', '44.5148735046387', 'BUCK POND CAMPGROUND'],
            ['-75.029914855957', '44.066593170166', '-74.9966049194336', '44.1420516967773', 'BUCK POND PRIMITIVE CORRIDOR'],
            ['-73.7329025268555', '44.5969734191895', '-73.6874237060547', '44.6333999633789', 'BURNT HILL STATE FOREST'],
            ['-74.19091796875', '44.4330444335938', '-74.1806259155273', '44.4430084228516', 'CAMP GABRIELS'],
            ['-74.1675415039062', '43.9713478088379', '-74.1279983520508', '44.0146293640137', 'CAMP SANTANONI'],
            ['-74.4752655029297', '43.1223487854004', '-74.4672470092773', '43.1256561279297', 'CAROGA LAKE CAMPGROUND'],
            ['-74.2909240722656', '43.2728843688965', '-74.2801208496094', '43.2813758850098', 'CATHEAD MTN. PRIMITIVE AREA'],
            ['-74.7419052124023', '44.6151275634766', '-74.7191925048828', '44.6171417236328', 'CATHERINEVILLE STATE FOREST'],
            ['-73.840446472168', '44.7219047546387', '-73.733642578125', '44.7680702209473', 'CHAZY HIGHLAND STATE FOREST'],
            ['-74.303466796875', '44.530445098877', '-73.7536315917969', '44.8466300964355', 'CHAZY HIGHLANDS WILD FOREST'],
            ['-73.4217300415039', '44.1403846740723', '-73.4208450317383', '44.1412734985352', 'COLE ISLAND'],
            ['-74.8473434448242', '44.2200241088867', '-74.8449325561523', '44.2208633422852', 'CRANBERRY LAKE BOAT LAUNCH'],
            ['-74.8327941894531', '44.1828193664551', '-74.8201904296875', '44.2068862915039', 'CRANBERRY LAKE CAMPGROUND'],
            ['-74.9828033447266', '44.1135330200195', '-74.6657333374023', '44.2770919799805', 'CRANBERRY LAKE WILD FOREST'],
            ['-73.4258651733398', '44.0234069824219', '-73.4164733886719', '44.0300941467285', 'CROWN POINT CAMPGROUND'],
            ['-73.4432830810547', '44.0135269165039', '-73.4240417480469', '44.0322303771973', 'CROWN POINT HISTORIC AREA'],
            ['-74.6571884155273', '44.2185897827148', '-74.592643737793', '44.2418556213379', 'DEAD CREEK PRIMITIVE AREA'],
            ['-74.6252288818359', '44.4114074707031', '-74.0249404907227', '44.6864891052246', 'DEBAR MTN WILD FOREST'],
            ['-75.0724487304688', '44.3783569335938', '-75.0703353881836', '44.3875389099121', 'DEGRASSE STATE FOREST'],
            ['-73.8872299194336', '43.9519271850586', '-73.6637191772461', '44.1495399475098', 'DIX MTN. WILDERNESS'],
            ['-74.3539276123047', '43.6094779968262', '-74.3418045043945', '43.6167945861816', 'DUG MT PRIMITIVE AREA'],
            ['-73.6998748779297', '44.7278747558594', '-73.6602401733398', '44.7841453552246', 'DUNKINS RESERVE STATE FOREST'],
            ['-75.1972274780273', '43.9027671813965', '-75.194091796875', '43.9062309265137', 'EAGLE FALLS CANYON'],
            ['-73.80078125', '43.7448959350586', '-73.7896118164062', '43.7528915405273', 'EAGLE POINT CAMPGROUND'],
            ['-74.7185897827148', '43.7579231262207', '-74.7014007568359', '43.7723350524902', 'EIGHTH LAKE CAMPGROUND'],
            ['-75.0110244750977', '43.0689659118652', '-74.4933166503906', '43.4346122741699', 'FERRIS LAKE WILD FOREST'],
            ['-73.6725082397461', '43.7386474609375', '-73.6498413085938', '43.7476463317871', 'FIRST BROTHER PRIMITIVE AREA'],
            ['-74.3838119506836', '44.293327331543', '-74.354850769043', '44.3110656738281', 'FISH CREEK POND CAMPGROUND'],
            ['-75.1677017211914', '43.8976821899414', '-74.7260894775391', '44.1633224487305', 'FIVE PONDS WILDERNESS'],
            ['-74.5486145019531', '43.9014739990234', '-74.5214309692383', '43.9173622131348', 'FORKED LAKE CAMPGROUND'],
            ['-74.2608108520508', '43.4674530029297', '-74.2246780395508', '43.4806442260742', 'FORKS MOUNTAIN PRIMITIVE CORRIDOR'],
            ['-74.3708114624023', '44.6916275024414', '-74.3603897094727', '44.6923866271973', 'FRANKLIN COUNTY DETACHED PARCEL'],
            ['-74.9775924682617', '43.7064208984375', '-74.8091888427734', '43.8522834777832', 'FULTON CHAIN WILD FOREST'],
            ['-73.4106903076172', '44.6030807495117', '-73.4101791381836', '44.6036949157715', 'GARDEN ISLAND'],
            ['-73.7839736938477', '44.1014060974121', '-73.6145324707031', '44.2133827209473', 'GIANT MTN. WILDERNESS'],
            ['-74.6098327636719', '43.8052673339844', '-74.5934906005859', '43.8209419250488', 'GOLDEN BEACH CAMPGROUND'],
            ['-73.5992050170898', '43.8665504455566', '-73.5981292724609', '43.867073059082', 'GOOSENECK POND PRIMITIVE AREA'],
            ['-74.0530090332031', '43.6500053405762', '-73.9889984130859', '43.6960144042969', 'GORE MTN. SKI CENTER'],
            ['-75.1304321289062', '44.2370262145996', '-74.7193298339844', '44.4450912475586', 'GRASSE RIVER WILD FOREST'],
            ['-74.8552093505859', '44.2194900512695', '-74.852424621582', '44.221549987793', 'GRASSE RIVER WILD FOREST'],
            ['-74.0563354492188', '43.2709121704102', '-74.0543365478516', '43.2724609375', 'GREAT SACANDAGA LAKE BOAT LAUNCH'],
            ['-73.6470794677734', '43.5616836547852', '-73.6466598510742', '43.5620765686035', 'GREEN ISLAND FISHING ACCESS'],
            ['-73.6470794677734', '43.5612335205078', '-73.6459197998047', '43.5622940063477', 'GREENE ISLAND MAINTENANCE FACILITY'],
            ['-75.2058029174805', '44.1868171691895', '-75.1941909790039', '44.1880149841309', 'GREENWOOD CREEK STATE FOREST'],
            ['-75.1817398071289', '43.6587257385254', '-74.9841842651367', '43.7820205688477', 'HA-DE-RON-DAH WILDERNESS'],
            ['-73.5687637329102', '43.7748107910156', '-73.5527877807617', '43.7861709594727', 'HAGUE BROOK PRIMITIVE AREA'],
            ['-73.8251953125', '43.8177261352539', '-73.46630859375', '44.245906829834', 'HAMMOND POND WILD FOREST'],
            ['-73.7026443481445', '43.4509010314941', '-73.6917495727539', '43.4578666687012', 'HEARTHSTONE POINT CAMPGROUND'],
            ['-74.3993377685547', '43.9859848022461', '-73.7831039428711', '44.2873039245605', 'HIGH PEAKS WILDERNESS'],
            ['-75.3166656494141', '43.6373519897461', '-75.3151702880859', '43.6466178894043', 'HIGH TOWERS STATE FOREST'],
            ['-75.0680389404297', '43.3195114135742', '-75.045036315918', '43.3416290283203', 'HINCKELY RESERVOIR DAY USE AREA'],
            ['-75.0673446655273', '43.3036346435547', '-75.064208984375', '43.304801940918', 'HINCKLEY STATE FOREST'],
            ['-74.6798706054688', '44.0889854431152', '-74.6250610351562', '44.1343193054199', 'HITCHINS POND PRIMITIVE'],
            ['-73.965461730957', '43.8430099487305', '-73.7424240112305', '43.9533882141113', 'HOFFMAN NOTCH WILDERNESS'],
            ['-74.6796875', '44.0973434448242', '-74.4646987915039', '44.2325973510742', 'HORSESHOE LAKE WILD FOREST'],
            ['-74.213493347168', '43.7633857727051', '-74.0480575561523', '43.8666000366211', 'HUDSON GORGE PRIMITIVE AREA'],
            ['-73.8262710571289', '43.2867622375488', '-73.8261032104492', '43.2876815795898', 'HUDSON RIVER BOAT LAUNCH'],
            ['-73.7910995483398', '44.2102584838867', '-73.6311492919922', '44.2930145263672', 'HURRICANE MTN. WILDERNESS'],
            ['-75.3123626708984', '43.7378578186035', '-75.3078918457031', '43.7595596313477', 'INDEPENDENCE RIVER STATE FOREST'],
            ['-75.3165130615234', '43.6185073852539', '-74.8730850219727', '43.9380111694336', 'INDEPENDENCE RIVER WILD FOREST'],
            ['-74.3955917358398', '43.6351661682129', '-74.3840408325195', '43.6523742675781', 'INDIAN LAKE ISLANDS CAMPGROUND'],
            ['-74.7943954467773', '43.7540702819824', '-74.7921371459961', '43.755256652832', 'INLET STATE BOAT LAUNCH'],
            ['-73.7210693359375', '44.2787132263184', '-73.6130065917969', '44.3382797241211', 'JAY MTN. WILDERNESS'],
            ['-74.5598449707031', '43.3572616577148', '-74.2096862792969', '43.7912406921387', 'JESSUP RIVER WILD FOREST'],
            ['-73.9777679443359', '44.2504806518555', '-73.9668426513672', '44.2563095092773', 'JOHN BROWNS FARM HISTORIC SITE'],
            ['-73.8489532470703', '44.1645202636719', '-73.8211288452148', '44.1831703186035', 'JOHNS BROOK PRIMITIVE AREA'],
            ['-74.165657043457', '44.3404579162598', '-74.1646499633789', '44.3418846130371', 'LAKE COLBY ENVIRONMENTAL EDUCATIONAL CAMP'],
            ['-74.4097442626953', '43.8340835571289', '-74.3803558349609', '43.8412971496582', 'LAKE DURANT CAMPGROUND'],
            ['-74.4709167480469', '43.9801177978516', '-74.453010559082', '43.990550994873', 'LAKE EATON CAMPGROUND'],
            ['-74.1263885498047', '44.3227424621582', '-74.1250152587891', '44.3238868713379', 'LAKE FLOWER BOAT LAUNCH'],
            ['-73.7098159790039', '43.4110336303711', '-73.69970703125', '43.4198455810547', 'LAKE GEORGE BATTLEFIELD DAY USE AREA'],
            ['-73.7122192382812', '43.4134216308594', '-73.7063217163086', '43.4191360473633', 'LAKE GEORGE BATTLEGROUND CAMPGROUND'],
            ['-73.4313049316406', '43.83642578125', '-73.4284591674805', '43.8388900756836', 'LAKE GEORGE FOREST PRESERVE'],
            ['-73.6844482421875', '43.4563331604004', '-73.4580154418945', '43.763355255127', 'LAKE GEORGE ISLANDS CAMPGROUND'],
            ['-73.8849411010742', '43.2944641113281', '-73.3615036010742', '43.8539657592773', 'LAKE GEORGE WILD FOREST'],
            ['-74.1450119018555', '43.974479675293', '-74.121696472168', '43.9822044372559', 'LAKE HARRIS CAMPGROUND'],
            ['-74.632209777832', '43.0823249816895', '-74.5936584472656', '43.0956230163574', 'LASSELLSVILLE STATE FOREST'],
            ['-74.3964920043945', '43.6490287780762', '-74.3768692016602', '43.6609573364258', 'LEWEY LAKE CAMPGROUND'],
            ['-75.3015899658203', '43.6383819580078', '-75.3007507324219', '43.6384506225586', 'LEWIS COUNTY DETACHED PARCEL'],
            ['-73.7401809692383', '44.8029747009277', '-73.7055969238281', '44.8409156799316', 'LEWIS PRESERVE WMA'],
            ['-74.8171310424805', '43.7114067077637', '-74.7925186157227', '43.7223930358887', 'LIMEKILN LAKE CAMPGROUND'],
            ['-73.5834579467773', '44.1343574523926', '-73.5741348266602', '44.148120880127', 'LINCOLN POND CAMPGROUND'],
            ['-74.5596618652344', '43.4101409912109', '-74.5504531860352', '43.4194984436035', 'LITTLE SAND POINT CAMPGROUND'],
            ['-74.418830871582', '43.9747924804688', '-74.4146194458008', '43.9787635803223', 'LONG LAKE STATE BOAT LAUNCH'],
            ['-74.0119400024414', '44.8058891296387', '-74.0104370117188', '44.8073463439941', 'LOWER CHATEAUGAY LAKE BOAT LAUNCH'],
            ['-74.1594848632812', '44.3255653381348', '-74.1543731689453', '44.328556060791', 'LOWER SARANAC LAKE BOAT LAUNCH'],
            ['-74.7783584594727', '44.0896949768066', '-74.6464614868164', '44.158748626709', 'LOWS LAKE PRIMITIVE'],
            ['-73.8305206298828', '43.3469429016113', '-73.8028259277344', '43.3685455322266', 'LUZERNE CAMPGROUND'],
            ['-74.4612808227539', '44.4940299987793', '-74.3235015869141', '44.6909713745117', 'MADAWASKA FLOW - QUEBEC BROOK PRIMITIVE AREA'],
            ['-74.1187896728516', '44.2877883911133', '-73.8717193603516', '44.4246444702148', 'MCKENZIE MTN. WILDERNESS'],
            ['-74.2996063232422', '44.5538482666016', '-74.2724304199219', '44.5817031860352', 'MEACHAM LAKE CAMPGROUND'],
            ['-74.0882873535156', '44.2918357849121', '-74.0741500854492', '44.2997932434082', 'MEADOWBROOK CAMPGROUND'],
            ['-74.2695693969727', '44.2430801391602', '-74.2671585083008', '44.2451438903809', 'MIDDLE SARANAC LAKE BOAT LAUNCH'],
            ['-73.9411392211914', '43.6317672729492', '-73.9387969970703', '43.6324234008789', 'MILL CREEK FISHING ACCESS'],
            ['-73.9784469604492', '43.5826034545898', '-73.9779663085938', '43.582950592041', 'MILL CREEK PARKING AREA'],
            ['-73.9790191650391', '44.2959899902344', '-73.9774932861328', '44.2980041503906', 'MIRROR LAKE BOAT LAUNCH'],
            ['-74.4259414672852', '43.4870834350586', '-74.3948593139648', '43.4997978210449', 'MOFFITT BEACH CAMPGROUND'],
            ['-73.8585510253906', '44.845890045166', '-73.8295211791992', '44.8731460571289', 'MOON POND STATE FOREST'],
            ['-74.8666534423828', '43.6150093078613', '-74.4543304443359', '43.8487510681152', 'MOOSE RIVER PLAINS WILD FOREST'],
            ['-73.4291076660156', '43.8200454711914', '-73.4245147705078', '43.8229522705078', 'MOSSY POINT STATE BOAT LAUNCH'],
            ['-73.9384002685547', '44.2019004821777', '-73.8863220214844', '44.2338562011719', 'MT. VAN HOEVENBURG SPORTS FACILITY'],
            ['-74.7836990356445', '43.9865646362305', '-74.7258605957031', '44.0206489562988', 'NEHASANE PRIMITIVE CORRIDOR'],
            ['-74.9931716918945', '43.6727180480957', '-74.9659881591797', '43.6944923400879', 'NICKS LAKE CAMPGROUND'],
            ['-74.7250900268555', '43.1305618286133', '-74.7229232788086', '43.1313438415527', 'NORTH CREEK FISHING ACCESS'],
            ['-74.0023880004883', '43.6389198303223', '-73.9949417114258', '43.6522636413574', 'NORTH CREEK PARKING FISHING ACCESS'],
            ['-74.1852951049805', '43.1799430847168', '-74.167854309082', '43.1913146972656', 'NORTHAMPTON BEACH CAMPGROUND'],
            ['-74.1668930053711', '43.2152824401855', '-74.1643981933594', '43.217212677002', 'NORTHVILLE-SUB-OFFICE'],
            ['-73.4356307983398', '44.1880569458008', '-73.4337158203125', '44.1893424987793', 'NORTHWEST BAY STATE BOAT LAUNCH'],
            ['-75.0625686645508', '44.309513092041', '-75.0587692260742', '44.3279151916504', 'ORE BED CREEK STATE FOREST'],
            ['-75.3077545166016', '43.7051391601562', '-75.3069915771484', '43.7187156677246', 'OTTER CREEK STATE FOREST'],
            ['-73.6918258666992', '43.879322052002', '-73.6710052490234', '43.892505645752', 'PARADOX LAKE CAMPGROUND'],
            ['-74.7957077026367', '44.078929901123', '-74.7696990966797', '44.0874633789062', 'PARKERS ISLAND PRIMITVE CORRIDOR'],
            ['-74.8215866088867', '43.9977226257324', '-74.7758636474609', '44.008903503418', 'PARTLOW LAKE PRIMITIVE CORRIDOR'],
            ['-73.5828475952148', '44.2185249328613', '-73.5742797851562', '44.2244262695312', 'PAULINE MURDOCK WMA'],
            ['-74.4640808105469', '43.0831298828125', '-74.4075088500977', '43.0930786132812', 'PECK HILL STATE FOREST'],
            ['-75.1523895263672', '43.8736915588379', '-75.0336456298828', '43.9644966125488', 'PEPPERBOX WILDERNESS'],
            ['-73.4463348388672', '44.6181755065918', '-73.4434509277344', '44.6199836730957', 'PERU DOCK BOAT LAUNCH'],
            ['-73.7712707519531', '43.7333068847656', '-73.4879150390625', '43.8873519897461', 'PHARAOH LAKE WILDERNESS'],
            ['-74.9336166381836', '43.7749557495117', '-74.6455612182617', '43.9317092895508', 'PIGEON LAKE WILDERNESS'],
            ['-74.5828247070312', '43.3989295959473', '-74.5691452026367', '43.4039154052734', 'POINT COMFORT CAMPGROUND'],
            ['-73.5120162963867', '44.3928985595703', '-73.5004119873047', '44.408821105957', 'POKE-O-MOONSHINE CAMPGROUND'],
            ['-74.5444869995117', '43.4260482788086', '-74.5384750366211', '43.4316940307617', 'POPLAR POINT CAMPGROUND'],
            ['-75.2225875854492', '43.525276184082', '-75.2197265625', '43.5438842773438', 'POPPLE POND STATE FOREST'],
            ['-73.4549179077148', '44.0514450073242', '-73.4517974853516', '44.0530700683594', 'PORT HENRY BOAT LAUNCH'],
            ['-75.1372909545898', '43.8730545043945', '-73.7976684570312', '44.3122901916504', 'PRIMITIVE AREA'],
            ['-75.0412673950195', '44.1330757141113', '-75.026725769043', '44.1363220214844', 'PRIMITVE AREA'],
            ['-75.0560684204102', '43.896656036377', '-75.0336685180664', '43.9253082275391', 'PRIMTIVE AREA'],
            ['-73.7507171630859', '43.4056396484375', '-73.7180938720703', '43.4378547668457', 'PROSPECT MOUNTAIN'],
            ['-73.4574661254883', '43.9506416320801', '-73.449348449707', '43.9562759399414', 'PUTNAM CREEK ACCESS'],
            ['-73.5889282226562', '43.8249015808105', '-73.5581436157227', '43.8553276062012', 'PUTNAM POND CAMPGROUND'],
            ['-73.4241943359375', '43.9540977478027', '-73.4100112915039', '43.9624481201172', 'PUTTS CREEK WMA'],
            ['-74.3895111083984', '44.2387351989746', '-74.3859558105469', '44.2401580810547', 'RAQUETTE RIVER BOAT LAUNCH'],
            ['-74.7769470214844', '44.3106117248535', '-74.6747665405273', '44.4514503479004', 'RAQUETTE RIVER WILD FOREST'],
            ['-74.7196044921875', '44.232364654541', '-74.5534439086914', '44.390079498291', 'RAQUETTE-JORDAN BOREAL PRIMITIVE AREA'],
            ['-74.1000061035156', '44.2931747436523', '-74.0840682983398', '44.2999687194824', 'RAY BROOK STATE OFFICE COMPLEX'],
            ['-73.4870834350586', '43.7908668518066', '-73.4638748168945', '43.8042602539062', 'ROGERS ROCK CAMPGROUND'],
            ['-74.4140243530273', '44.3013458251953', '-74.3941116333008', '44.3246688842773', 'ROLLINS POND CAMPGROUND'],
            ['-74.6983184814453', '44.0517196655273', '-74.5532760620117', '44.1177177429199', 'ROUND LAKE WILDERNESS'],
            ['-74.2968673706055', '43.3523368835449', '-74.2720489501953', '43.3629608154297', 'SACANDAGA CAMPGROUND'],
            ['-74.415168762207', '43.3610763549805', '-74.3499908447266', '43.3690719604492', 'SACANDAGA PRIMITIVE AREA'],
            ['-74.1898727416992', '43.2237358093262', '-74.1827774047852', '43.2347297668457', 'SACANDAGA RIVER STATE BOAT LAUNCH'],
            ['-74.4333343505859', '44.3391380310059', '-74.2716445922852', '44.4394111633301', 'SAINT REGIS CANOE AREA'],
            ['-75.2543716430664', '43.5699501037598', '-75.2446746826172', '43.5707015991211', 'SAND FLATS STATE FOREST'],
            ['-74.3218154907227', '44.3436012268066', '-74.3196792602539', '44.3451232910156', 'SARANAC LAKE BOAT LAUNCH'],
            ['-74.1886138916016', '44.2863616943359', '-74.1844940185547', '44.2884483337402', 'SARANAC LAKE ISLANDS CAMPGROUND'],
            ['-74.4738922119141', '44.1869010925293', '-73.9045028686523', '44.4374008178711', 'SARANAC LAKES WILD FOREST'],
            ['-74.8709030151367', '43.8151893615723', '-74.3178787231445', '44.1155204772949', 'SARGENT PONDS WILD FOREST'],
            ['-73.7936248779297', '43.7556648254395', '-73.7824554443359', '43.7796516418457', 'SCAROON MANOR DAY USE AREA'],
            ['-73.810676574707', '43.724063873291', '-73.8053894042969', '43.7267379760742', 'SCHROON LAKE WATERWAY ACCESS'],
            ['-73.7306060791016', '43.6072235107422', '-73.7300872802734', '43.6076240539551', 'SCHROON RIVER FISHING ACCESS'],
            ['-73.3808822631836', '44.493766784668', '-73.3707122802734', '44.5057640075684', 'SCHUYLER ISLAND PRIMITIVE AREA'],
            ['-73.9360427856445', '44.2189064025879', '-73.7739486694336', '44.3537483215332', 'SENTINEL RANGE WILDERNESS'],
            ['-74.5308227539062', '43.0831756591797', '-74.184326171875', '43.2717475891113', 'SHAKER MTN. WILD FOREST'],
            ['-73.6784133911133', '44.0339317321777', '-73.6654815673828', '44.0486755371094', 'SHARP BRIDGE CAMPGROUND'],
            ['-73.4140853881836', '43.9376792907715', '-73.413330078125', '43.9380760192871', 'SHEEPSHEAD ISLAND'],
            ['-74.3914489746094', '43.4452323913574', '-74.0236206054688', '43.7775382995605', 'SIAMESE PONDS WILDERNESS'],
            ['-73.411003112793', '43.9225845336914', '-73.4106521606445', '43.9227485656738', 'SIGNAL BUOY ISLAND'],
            ['-74.5538177490234', '43.2294921875', '-74.2379302978516', '43.4875068664551', 'SILVER LAKE WILDERNESS'],
            ['-74.8254547119141', '44.4870185852051', '-74.8240051269531', '44.4906997680664', 'SNOW BOWL STATE FOREST'],
            ['-73.4356994628906', '43.5731582641602', '-73.4321517944336', '43.5759048461914', 'SOUTH BAY STATE BOAT LAUNCH'],
            ['-73.3999786376953', '44.2102355957031', '-73.3281784057617', '44.2619018554688', 'SPLIT ROCK WILD FOREST'],
            ['-73.4043579101562', '44.6276435852051', '-73.4035110473633', '44.6294441223145', 'SPOON ISLAND PRIMITIVE AREA'],
            ['-73.7755889892578', '44.7935981750488', '-73.7035598754883', '44.8773612976074', 'SPRING BROOK STATE FOREST'],
            ['-75.0374526977539', '43.8892059326172', '-75.0346527099609', '43.8906784057617', 'STILLWATER BOAT LAUNCH'],
            ['-75.1266250610352', '44.1772270202637', '-75.1125793457031', '44.182430267334', 'SUCKER LAKE WATER ACCESS'],
            ['-73.8603820800781', '44.3912620544434', '-73.8560104370117', '44.3961181640625', 'SUNY ATMOSPHERIC SCIENCES RESEARCH CENTER'],
            ['-75.0224761962891', '44.4609451293945', '-75.0224380493164', '44.4609527587891', 'TAYLOR CREEK STATE FOREST'],
            ['-73.8397598266602', '44.4903717041016', '-73.8173980712891', '44.4968910217285', 'TAYLOR POND CAMPGROUND'],
            ['-74.0753631591797', '44.1494445800781', '-73.4840316772461', '44.6071014404297', 'TAYLOR POND WILD FOREST'],
            ['-73.7336730957031', '44.5416831970215', '-73.625244140625', '44.5981025695801', 'TERRY MOUNTAIN STATE FOREST'],
            ['-75.0914840698242', '43.9638938903809', '-75.069709777832', '43.979248046875', 'TIED LAKE PRIMITIVE CORRIDOR'],
            ['-74.6349945068359', '43.8397026062012', '-74.6272430419922', '43.8459701538086', 'TIOGA POINT CAMPGROUND'],
            ['-74.3015518188477', '44.6914138793945', '-74.1527481079102', '44.7036895751953', 'TITUSVILLE MOUNTAIN STATE FOREST'],
            ['-74.7686386108398', '44.0610733032227', '-74.6982574462891', '44.0817222595215', 'TOMAR POND PRIMITIVE AREA'],
            ['-74.4847793579102', '44.1951560974121', '-74.4829025268555', '44.1965179443359', 'TUPPER LAKE BOAT LAUNCH'],
            ['-75.1204299926758', '43.3904457092285', '-73.7364730834961', '44.8307647705078', 'UNCLASSIFIED'],
            ['-73.980339050293', '44.772891998291', '-73.9792098999023', '44.7744598388672', 'UPPER CHATEAUGAY LAKE BOAT LAUNCH'],
            ['-73.4319381713867', '44.607494354248', '-73.4025573730469', '44.6368103027344', 'VALCOUR ISLAND PRIMITIVE AREA'],
            ['-74.1576461791992', '43.7375946044922', '-73.9099655151367', '43.9929084777832', 'VANDERWHACKER MTN. WILD FOREST'],
            ['-74.1796722412109', '43.6596298217773', '-73.7898635864258', '44.022590637207', 'VANDERWHACKER MTN. WILD FOREST'],
            ['-74.5250854492188', '43.7305870056152', '-74.5027008056641', '43.7397003173828', 'WAKELY MOUNTAIN PRIMITIVE AREA'],
            ['-74.9270858764648', '44.1197509765625', '-74.9226608276367', '44.1310386657715', 'WANAKENA PRIMITVE CORRIDOR'],
            ['-75.2359848022461', '43.9633941650391', '-75.0607223510742', '44.0749397277832', 'WATSONS EAST TRIANGLE WILD FOREST'],
            ['-74.8923034667969', '43.396312713623', '-74.3339614868164', '43.7732276916504', 'WEST CANADA LAKE WILDERNESS'],
            ['-74.421989440918', '43.7252426147461', '-74.3974609375', '43.7676086425781', 'WEST CANADA LAKE WILDERNESS'],
            ['-74.7586669921875', '43.4916343688965', '-74.6991958618164', '43.5388259887695', 'WEST CANADA MTN. PRIMITIVE AREA'],
            ['-74.832893371582', '44.4537353515625', '-74.7332992553711', '44.5971488952637', 'WHITE HILL WILD FOREST'],
            ['-73.9150161743164', '44.3460655212402', '-73.8436813354492', '44.4035186767578', 'WHITEFACE MTN. SKI CENTER'],
            ['-73.4418869018555', '44.5193557739258', '-73.4145278930664', '44.5418930053711', 'WICKHAM MARSH WMA'],
            ['-74.2938766479492', '43.0753440856934', '-73.8182678222656', '43.6617584228516', 'WILCOX LAKE WILD FOREST'],
            ['-74.7877883911133', '43.9648818969727', '-74.5477523803711', '44.0613250732422', 'WILLIAM C. WHITNEY WILDERNESS'],
            ['-73.8727264404297', '44.3464813232422', '-73.8533172607422', '44.3557243347168', 'WILMINGTON NOTCH CAMPGROUND'],
            ['-73.9191360473633', '44.2990608215332', '-73.7100219726562', '44.481990814209', 'WILMINGTON WILD FOREST'],
            ['-74.7251205444336', '43.4063262939453', '-74.7211380004883', '43.4202346801758', 'WILMURT CLUB ROAD']
        ],
        autoLoad: false
    });
    var unitZoom = new Ext.form.ComboBox({
        tpl: '<tpl for="."><div ext:qtip="{label}" class="x-combo-list-item">{label}</div></tpl>',
        store: unitStore,
        displayField: "label",
        typeAhead: true,
        mode: "local",
        forceSelection: true,
        triggerAction: 'all',
        width: 225,
        emptyText: "Park Unit Zoom",
        selectOnFocus: true,
        listeners: {
            "select": function (combo, record) {
                //map.setCenter(new OpenLayers.LonLat(record.data.lon, record.data.lat), record.data.zoom);
                //map.setCenter(new OpenLayers.LonLat(record.data.lon, record.data.lat).transform(map.displayProjection, map.projection), record.data.zoom);
                map.zoomToExtent(new OpenLayers.Bounds(record.data.xmin, record.data.ymin, record.data.xmax, record.data.ymax).transform(new OpenLayers.Projection("EPSG:4326"), new OpenLayers.Projection("EPSG:900913")));
            }
        }
    });
    var parcelSearch = new Ext.form.ComboBox({
        queryParam: "query",
        store: parcelSearchStore,
        displayField: "parcel_id",
        typeAhead: false,
        loadingText: "Searching...",
        width: 200,
        emptyText: "Tract ID",
        hideTrigger: true,
        tpl: '<tpl for="."><div class="search-item"><b>Tract ID:</b> {parcel_id}</div><hr/></tpl>',
        itemSelector: "div.search-item",
        listeners: {
            "select": function (combo, record) {
                //LayerTree.getNodeById("taxassessment").expand();
                parcels.setVisibility(true);
                highlightWMS(record.data.parcel_id);
                map.zoomToExtent(new OpenLayers.Bounds(record.data.bbox[0], record.data.bbox[1], record.data.bbox[2], record.data.bbox[3]));
            },
            "focus": function () {
                keyboardnav.deactivate();
            },
            "blur": function () {
                keyboardnav.activate();
            }
        }
    });

    opacitySlider = new GeoExt.LayerOpacitySlider({
        aggressive: true,
        width: 150,
        isFormField: true,
        inverse: true,
        plugins: new GeoExt.LayerOpacitySliderTip({
            template: "<div>Transparency: {opacity}%</div>"
        })
    });

    var mapLinkButton = new Ext.Button({
        text: "<b>Get Map Link</b>",
        iconCls: "icon-link",
        iconAlign: "right",
        handler: function () {
            Ext.MessageBox.alert('Map Link', '<a class="black" href="' + mapLink + '" target="_blank">Right click to copy map link</a>');
        }
    });
    var srsSelector = new Ext.Button({
        menu: {
            items: [{
                text: "Decimal Degrees",
                checked: true,
                group: "srs",
                handler: function () {
                    document.getElementById('ddcoords').style.display = 'inline';
                    document.getElementById('dmscoords').style.display = 'none';
                    document.getElementById('spcoords').style.display = 'none';
                    document.getElementById('utmcoords').style.display = 'none';
                    srsSelector.setText("<b>Decimal Degrees</b>");
                }
            }, {
                text: "Degrees Minutes Seconds",
                checked: false,
                group: "srs",
                handler: function () {
                    document.getElementById('ddcoords').style.display = 'none';
                    document.getElementById('dmscoords').style.display = 'inline';
                    document.getElementById('spcoords').style.display = 'none';
                    document.getElementById('utmcoords').style.display = 'none';
                    srsSelector.setText("<b>Degrees Minutes Seconds</b>");
                }
            }, {
                text: "State Plane Feet",
                checked: false,
                group: "srs",
                handler: function () {
                    document.getElementById('ddcoords').style.display = 'none';
                    document.getElementById('dmscoords').style.display = 'none';
                    document.getElementById('spcoords').style.display = 'inline';
                    document.getElementById('utmcoords').style.display = 'none';
                    srsSelector.setText("<b>State Plane Feet</b>");
                }
            }, {
                text: "UTM Meters",
                checked: false,
                group: "srs",
                handler: function () {
                    document.getElementById('ddcoords').style.display = 'none';
                    document.getElementById('dmscoords').style.display = 'none';
                    document.getElementById('spcoords').style.display = 'none';
                    document.getElementById('utmcoords').style.display = 'inline';
                    srsSelector.setText("<b>UTM Meters</b>");
                }
            }]
        },
        //tooltip: "Select a Coordinate System",
        text: "<b>Decimal Degrees</b>"
    });
    var fullScreenButton = new Ext.Button({
        id: "fullScreenButton",
        text: "<b>Full Screen</b>",
        iconCls: "icon-fullscreen",
        iconAlign: "right",
        enableToggle: true,
        handler: function () {
            if (Ext.getCmp("fullScreenButton").pressed == true) {
                Ext.getCmp("northPanel").collapse();
                Ext.getCmp("westPanel").collapse();
            }
            if (Ext.getCmp("fullScreenButton").pressed == false) {
                Ext.getCmp("northPanel").expand();
                Ext.getCmp("westPanel").expand();
            }
        }
    });

    <?php if (($_SESSION['covington_user'] == 'covington')) { ?>
        var loginoutButton = new Ext.Button({
            id: "loginoutButton",
            text: "<b>Logout</b>",
            iconCls: "icon-logout",
            iconAlign: "right",
            handler: function () {
                window.location = "http://chamaps.com/covington/logout.php";
            }
        });
    <?php } else { ?>
        var loginoutButton = new Ext.Button({
            id: "loginoutButton",
            text: "<b>Login</b>",
            iconCls: "icon-login",
            iconAlign: "right",
            handler: function () {
                window.location = "http://chamaps.com/covingtonlogin.php";
            }
        });
    <?php } ?>

    var toolBar = [/*panZoom, */zoomIn, zoomOut, zoomPrevious, zoomNext, zoomExtentBtn, "-", identify, clearSelect, "-", /*measureLength, measureArea*/ measureButton, "-", printButton, "-", downloadButton, '<div id="output" class="measureoutput">&nbsp;&nbsp;&nbsp;&nbsp;</div>', '->', townshipZoom, '-', unitZoom];
    var bottomBar = [mapLinkButton, "-", '<div class="scaleoutput">Map Scale:&nbsp;</div>', '<div id="scale"></div>', "-", srsSelector, " ", '<div id="ddcoords"></div><div id="dmscoords" style="display:none;"></div><div id="spcoords" style="display:none;"></div><div id="utmcoords" style="display:none;"></div>', "->", fullScreenButton];

    var zoomSlider = new GeoExt.ZoomSlider({
        map: map,
        aggressive: true,
        vertical: true,
        height: 100,
        plugins: new GeoExt.ZoomSliderTip({
            template: "<div>Zoom Level: {zoom}</div><div>Scale: 1 : {scale}</div>"
        })
    });
    mapPanel = new GeoExt.MapPanel({
        id: "map",
        title: "Interactive Map",
        iconCls: "icon-interactivemap",
        region: "center",
        map: map,
        tbar: toolBar,
        bbar: bottomBar,
        center: [-8271427.7830066, 5460746.3281636],
        zoom: 8,
        items: [zoomSlider]
    });
    var propertiesButton = new Ext.Button({
        id: "layerpropertiesButton",
        text: "Selected Layer Properties",
        iconCls: "icon-layerproperties",
        handler: function() {
            layerProperties();
        }
    });
    layerTree = new Ext.tree.TreePanel({
        title: "Layers",
        iconCls: "icon-maplayers",
        autoScroll: true,
        tbar: ["->",propertiesButton],
        loader: new Ext.tree.TreeLoader({
            applyLoader: false
        }),
        border: false,
        rootVisible: false,
        enableDD: false,
        root: {
            nodeType: "async",
            children: treeConfig
        },
        listeners: {
            contextmenu: function(node, e) {
                if (node && node.layer) {
                    node.select();
                    var c = node.getOwnerTree().contextMenu;
                    c.contextNode = node;
                    c.showAt(e.getXY())
                }
            },
            click: function (node, e) {
                    opacitySlider.setLayer(node.layer);
            },
            scope: this
        },
        contextMenu: new Ext.menu.Menu({
            items: [{
                text: "Zoom to Layer Extent",
                iconCls: "icon-layerextent",
                handler: function() {
                    var node = layerTree.getSelectionModel().getSelectedNode();
                    if (node && node.layer) {
                        mapPanel.map.zoomToExtent(new OpenLayers.Bounds(node.attributes.extent[0], node.attributes.extent[1], node.attributes.extent[2], node.attributes.extent[3]).transform(map.displayProjection, map.projection));
                    }
                }
            }, {
                text: "Layer Properties",
                iconCls: "icon-layerproperties",
                handler: function() {
                    layerProperties();
                }
            }]
        })
        /*root: new GeoExt.tree.LayerContainer({
            text: 'Map Layers',
            layerStore: mapPanel.layers,
            leaf: false,
            expanded: true
        })*/
    });
    var header = new Ext.BoxComponent({
        height: 60,
        autoEl: {
            tag: "div",
            cls: "header",
            html: '<a href="http://www.aprgis.org" target="_blank"><img style="width: 541px; height: 34px; position:absolute; left:10px; top:5px;" alt="Adirondack Regional GIS" src="img/logo3.gif" align="middle"></a><a href="http://bryanmcbride.com" target="_blank"><img style="width: 161px; height: 39px; position:absolute; right:50px; top:10px;" alt="Powered by OpenGeo Suite" src="img/pbOGSv-161x39.png" align="middle"></a>'
        }
    });
    northPanel = new Ext.Panel({
        region: "north",
        id: "northPanel",
        height: 60,
        split: true,
        items: [header],
        collapseMode: "mini",
        margins: "0 0 0 0"
    });
    var southPanel = new Ext.Panel({
        region: "south",
        title: "Data Table",
        html: "south",
        iconCls: "icon-table",
        split: true,
        height: 200,
        minSize: 100,
        maxSize: 250,
        collapseMode: "mini",
        collapsed: true,
        margins: "0 0 0 0"
    });
    var toolsPanel1 = new Ext.Panel({
        autoScroll: true,
        border: false,
        title: "Tools",
        iconCls: "icon-tools"//,
        //items: [tree],
        //bbar: ["Transparency:&nbsp;&nbsp;", opacitySlider]
    });
    var layertoolPanel = new Ext.TabPanel({
        region: "center",
        plain: true,
        deferredRender: false,
        activeTab: 0,
        items: [layerTree/*, toolsPanel1*/],
        bbar: ["Transparency:&nbsp;&nbsp;", opacitySlider]
    });
    legendPanel = new GeoExt.LegendPanel({
        title: "Legend",
        iconCls: "icon-maplegend",
        border: false,
        autoScroll: true,
        ascending: false,
        map: map,
        defaults: {
            cls: "legend-item",
            baseParams: {
                FORMAT: 'image/png'
            }
        },
        items: [],
        bbar: ["<b>Managed by <a style='color:black;' href='http://www.esf.edu/aec/' target='_blank'>SUNY-ESF</a></b>"/*, "->", loginoutButton*/]
    });
    var toolsPanel2 = new Ext.Panel({
        autoScroll: true,
        border: false,
        title: "Tools",
        iconCls: "icon-tools"
    });
    var legendtoolPanel = new Ext.TabPanel({
        region: "south",
        height: 250,
        collapseMode: "mini",
        split: true,
        plain: true,
        deferredRender: false,
        activeTab: 0,
        items: [legendPanel/*, toolsPanel2*/]
    });
    westPanel = new Ext.Panel({
        id: "westPanel",
        border: false,
        layout: "border",
        region: "west",
        width: 275,
        split: true,
        collapseMode: "mini",
        items: [layertoolPanel, legendtoolPanel]
    });
    var downloadTab = new Ext.Panel({
        id: "downloadcenter",
        title: "Download Center",
        iconCls: "icon-downloadcenter",
        closable: false,
        autoScroll: true,
        layout:"fit",
        border: false,
        html: '<iframe id="downloads" name="downloads" src="http://aprgis.org/argis/config/dataportal.php" width="100%" height="100%" frameborder="0" scrolling="auto">/iframe>'
    });
	var helpTab = new Ext.Panel({
        id: "help",
        title: "Help & Tutorials",
        iconCls: "icon-help",
        closable: false,
        autoScroll: true,
        layout:"fit",
        border: false,
        html: '<iframe id="help" name="help" src="http://aprgis.org/argis/config/help.php" width="100%" height="100%" frameborder="0" scrolling="auto">/iframe>'
    });
    var aboutTab = new Ext.Panel({
        id: "about",
        title: "About ARGIS",
        iconCls: "icon-about",
        closable: false,
        autoScroll: true,
        layout:"fit",
        border: false,
        html: '<iframe id="about" name="about" src="http://aprgis.org/argis/config/about.php" width="100%" height="100%" frameborder="0" scrolling="auto">/iframe>'
    });
    centerPanel = new Ext.TabPanel({
        region: "center",
        plain: true,
        deferredRender: false,
        activeTab: 0,
        items: [mapPanel, downloadTab, helpTab, aboutTab]
    });
    var viewport = new Ext.Viewport({
        layout: "border",
        items: [northPanel, westPanel, centerPanel]
    });
    map.addControl(new OpenLayers.Control.Scale(document.getElementById("scale")));
	Ext.getCmp("map").body.applyStyles("cursor:help");
    mapDiv = mapPanel.map.div.id;

    function formatLonlats(lonLat) {
        var lat = lonLat.lat;
        var lng = lonLat.lon;
        var ns = OpenLayers.Util.getFormattedLonLat(lat);
        var ew = OpenLayers.Util.getFormattedLonLat(lng, 'lon');
        return ns + ', ' + ew;
    }
    map.addControl(new OpenLayers.Control.MousePosition({
        "div": OpenLayers.Util.getElement("ddcoords"),
        displayProjection: new OpenLayers.Projection("EPSG:4326")
    }));
    map.addControl(new OpenLayers.Control.MousePosition({
        "div": OpenLayers.Util.getElement("dmscoords"),
        displayProjection: new OpenLayers.Projection("EPSG:4326"),
        formatOutput: formatLonlats
    }));
    map.addControl(new OpenLayers.Control.MousePosition({
        "div": OpenLayers.Util.getElement("spcoords"),
        displayProjection: new OpenLayers.Projection("EPSG:2260"),
        numDigits: 0
    }));
    map.addControl(new OpenLayers.Control.MousePosition({
        "div": OpenLayers.Util.getElement("utmcoords"),
        displayProjection: new OpenLayers.Projection("EPSG:26918"),
        numDigits: 0
    }));

    infoCtrl.activate();

    mapPanel.map.div.oncontextmenu = function(e){
        e = e?e:window.event;
        if (e.preventDefault) e.preventDefault(); // For non-IE browsers.
        else return false; // For IE browsers.
    };

    /*var coordClickCtrl = new OpenLayers.Control.Click({
        eventMethods: {
            'rightclick': function (e) {
                if (coordinatePopup) {
                    coordinatePopup.destroy();
                }
                e.xy = map.events.getMousePosition(e);
                e.geoxy = map.getLonLatFromPixel(e.xy).transform("EPSG:900913", "EPSG:4326");
                //Ext.MessageBox.alert('Coordinates', e.geoxy.lat.toFixed(5)+", "+e.geoxy.lon.toFixed(5));
                var coordinatePopup = new GeoExt.Popup({
                    title: "Coordinates",
                    width: 200,
                    maximizable: true,
                    collapsible: true,
                    map: mapPanel.map,
                    location: mapPanel.map.getLonLatFromViewPortPx(e.xy),
                    anchored: true,
                    html: e.geoxy.lat.toFixed(5)+", "+e.geoxy.lon.toFixed(5)
                });
                coordinatePopup.show();
            }
        }
    });
    map.addControl(coordClickCtrl);
    coordClickCtrl.activate();*/

    // update link when state chnages
    var onStatechange = function (provider) {
            mapLink = document.location.href + "?map_x=" + map.getCenter().lon + "&map_y=" + map.getCenter().lat + "&map_zoom=" + map.getZoom();
            //mapLink = provider.getLink();
            //Ext.get("permalink").update("<a href=" + l + ">" + l + "</a>");
        };
    permalinkProvider.on({
        statechange: onStatechange
    });
});

OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
    defaultHandlerOptions: {
        'single': true,
        'double': true,
        'pixelTolerance': 0,
        'stopSingle': false,
        'stopDouble': false
    },
    handleRightClicks: true,
    initialize: function (options) {
        this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
        OpenLayers.Control.prototype.initialize.apply(
        this, arguments);
        this.handler = new OpenLayers.Handler.Click(
        this, this.eventMethods, this.handlerOptions);
    },
    CLASS_NAME: "OpenLayers.Control.Click"
});
