<?php

/**
 *
 */

class PollutionTracker{

    public static function init(){



        add_shortcode('PTMap', array('PollutionTracker', 'mapShortcode'));
    }

    public static function enqueueScripts(){

        // Using Openmaptiles.org, leaflet, and mapbox gl
        // Can't just use mapbox since it can't custer html markers/popups
        // Edit map style here: http://editor.openmaptiles.org

        wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.0.3/dist/leaflet.js', array(), '20151215', false );
        wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.2.0/dist/leaflet.css' );

        wp_enqueue_script( 'leaflet-clusterer', 'https://unpkg.com/leaflet.markercluster@1.1.0/dist/leaflet.markercluster.js', array(), '20151215', false );
        wp_enqueue_style( 'leaflet-clusterer', plugin_dir_url(__FILE__) . '/css/MarkerCluster.css' );


        wp_enqueue_script( 'mapbox', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js', array(), '20151215', false );
        wp_enqueue_style( 'mapbox', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css' );



        wp_enqueue_script( 'leaflet-mapbox', 'http://rawgit.com/mapbox/mapbox-gl-leaflet/master/leaflet-mapbox-gl.js', array(), '20151215', false );
    }

    public static function mapShortcode($args){
        $html = '<div id="map" class="map"></div>';
        $script = "<script type=\"text/javascript\">\n";


        $site_data = self::getPointersData();
        $script .= "var geojson = {
            type: 'FeatureCollection',
            features: [
        ";

        foreach($site_data as $site){
            $script .= "{
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [" . $site->longitude . "," . $site->latitude . "]
                },
                properties: {
                    title: '" . $site->name . "',
                    html: '<em>HTML will go here</em>'
                }},\n";
        }
        $script .= ']}';


        $script .= "
        var map = L.map('map', {
            center: [49.5,-123.5],
            zoom: 7,
            maxZoom: 14,
            minZoom: 4,
            maxBounds: L.latLngBounds(L.latLng(60,-141),L.latLng(45,-100)),
            scrollWheelZoom: false
        });
        
        var gl = L.mapboxGL({
            accessToken: 'not required',
            style: '" . plugin_dir_url(__FILE__) . "/map-style.json',
        }).addTo(map);
        
        var markers = L.markerClusterGroup();
		var geoJsonLayer = L.geoJson(geojson, {
			onEachFeature: function (feature, layer) {
				layer.bindPopup('<div class=\"title\">' + feature.properties.title + '</div><div class=\"content\"' + feature.properties.html + '</div>');
			}
		});
		markers.addLayer(geoJsonLayer);
		map.addLayer(markers);
		map.fitBounds(markers.getBounds());
        
        // So you can scroll the page
        //map.scrollZoom.disable();
        
        // Add zoom and rotation controls to the map.
        //map.addControl(new mapboxgl.NavigationControl());
        
        ";

        $site_data = self::getPointersData();

        $script .= "var geojson = {
            type: 'FeatureCollection',
            features: [
        ";

        foreach($site_data as $site){
            $script .= "{
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [" . $site->longitude . "," . $site->latitude . "]
                },
                properties: {
                    title: '" . $site->name . "',
                    html: '<em>HTML will go here</em>'
                }},\n";
        }
        $script .= ']}';





        $script .= "</script>";
        return $html . $script;
    }


    public static function getPointersData(){
        global $wpdb;

        $data = [];
        $sites = $wpdb->get_results('SELECT * FROM wp_sites');

        foreach ($sites as $site){
            $site_data = new stdClass();
            $site_data->name = $site->name;
            $site_data->longitude = $site->longitude;
            $site_data->latitude = $site->latitude;
            $site_data->contaminants = array();

            array_push($data, $site_data);
        }

        return $data;
    }
}


/*public static function mapShortcode($args){
        $html = '<div id="map" class="map"></div>';
        $script = "<script type=\"text/javascript\">\n";

        $script .= "
        var map = new mapboxgl.Map({
            container: 'map',
            style: '" . plugin_dir_url(__FILE__) . "/map-style.json',
            center: [-123.5, 49.5],
            zoom: 7
        });

        // So you can scroll the page
        map.scrollZoom.disable();

        // Add zoom and rotation controls to the map.
        map.addControl(new mapboxgl.NavigationControl());

        ";

        $site_data = self::getPointersData();

        $script .= "var geojson = {
            type: 'FeatureCollection',
            features: [
        ";

        foreach($site_data as $site){
            $script .= "{
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [" . $site->longitude . "," . $site->latitude . "]
                },
                properties: {
                    title: '" . $site->name . "',
                    html: '<em>HTML will go here</em>'
                }},\n";
        }
        $script .= ']}';


        $script .= "
        map.on('load', function() {
            map.addSource(\"sites\", {
                type: \"geojson\",
                data: geojson,
                cluster: true,
                clusterMaxZoom: 14, // Max zoom to cluster points on
                clusterRadius: 50 // Radius of each cluster when clustering points (defaults to 50)
            });


            map.addLayer({
                id: \"clusters\",
                type: \"circle\",
                source: \"sites\",
                filter: [\"has\", \"point_count\"],
                paint: {
                    \"circle-color\": {
                        property: \"point_count\",
                        type: \"interval\",
                        stops: [
                            [0, \"#51bbd6\"],
                            [100, \"#f1f075\"],
                            [750, \"#f28cb1\"],
                        ]
                    },
                    \"circle-radius\": {
                        property: \"point_count\",
                        type: \"interval\",
                        stops: [
                            [0, 20],
                            [100, 30],
                            [750, 40]
                        ]
                    }
                }
            });

            /*map.addLayer({
                id: \"cluster-count\",
                type: \"symbol\",
                source: \"sites\",
                filter: [\"has\", \"point_count\"],
                layout: {
                    'text-field': \"{point_count_abbreviated}\",
                    //text-font: ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                    'text-size': 12
                }
            });*/

/*
map.addLayer({
                id: \"unclustered-point\",
                type: \"circle\",
                source: \"sites\",
                filter: [\"!has\", \"point_count\"],
                paint: {
                    \"circle-color\": \"#11b4da\",
                    \"circle-radius\": 4,
                    \"circle-stroke-width\": 1,
                    \"circle-stroke-color\": \"#fff\"
                }
            });
        });

        var popup = new mapboxgl.Popup({
          closeButton: false,
          closeOnClick: false
        });

        function showPopup(location, layer, fields) {
          var identifiedFeatures = map.queryRenderedFeatures(location.point, layer);
          console.log('features:',identifiedFeatures);
          popup.remove();
          if (identifiedFeatures && identifiedFeatures.length) {
            var popupContents = safeRead(identifiedFeatures, '0', 'properties', 'html');
            var popupHTML = '<div class=\"mapPopup\">' + popupContents + '</div>';
            popup.setLngLat(location.lngLat)
              .setHTML(popupHTML)
              .addTo(map);
          }
        }

        map.on('click', function(e) {
          showPopup(e, 'unclustered-point');
        });

        map.on('mouseenter', 'unclustered-point', function(e) {
            // Change the cursor style as a UI indicator.
            map.getCanvas().style.cursor = 'pointer';


        });

        map.on('mouseleave', 'unclustered-point', function() {
            map.getCanvas().style.cursor = 'default';
            popup.remove();
        });

        ";


        $script .= "</script>";
        return $html . $script;
    }
    */

