//var PollutionTracker = PollutionTracker || {}
var PollutionTracker = (function($){
    var map;

    $(document).on('click', '.showMap', function(e){
        e.preventDefault();
        e.stopPropagation();
        $('body, .map-wrap').addClass('show-map');

        _this.showMap();
    });


    $(document).on('click', '.map-wrap .close-bar', function(e){
        e.preventDefault();
        $('body, .map-wrap').removeClass('show-map');
        window.location.hash = '';
    });

    $(document).on('click', '.map-preview', function(e){
        $('a.showMap').trigger('click');
    });

    $(document).on('click', '.panel .tabs li', function(e){
        e.preventDefault();
        var tab = $(this).attr('data-tab');
        _this.currentTab = tab;
        _this.showPanel(_this.currentFeature);
        $('.panel .tabs li[data-tab=' + tab + ']').addClass('active');

    });

    $(document).on('click', '.map-wrap .panel .close', function (e) {
        e.preventDefault();
        $('.map-wrap').removeClass('show-panel');
    });

    $(document).on('click', '.map-wrap .contaminants-graph .contaminant', function(e){
        var slug = $(this).data('slug');
        var contaminantURL = '/contaminants/' + slug;
        window.location.href=contaminantURL;
    });

    $(document).on('click', '.showLeftNav', function(e){
        e.preventDefault();
        $('body').addClass('show-left-nav');
    });

    $(document).on('click', '#left-nav .close', function(e){
        e.preventDefault();
        $('body').removeClass('show-left-nav');
        return false;
    });

    $(document).on('click', '.histogram .border', function(){
        if (!_this.map || _this.map.getZoom() < 12) _this.currentZoom = 12;
    });

    $(window).on('ready hashchange', function(e){
        if (window.location.hash){
            var arrHash = document.location.hash.split('|');
            if (arrHash[0] == '#Map') {
                e.preventDefault();
                _this.showMap();
            }
            if (arrHash[1]){
                var feature = _this.getFeatureByID(arrHash[1]);
                var tab = arrHash[2];
                var zoom = arrHash[3];
                if (tab) _this.currentTab = tab;
                //if (zoom && zoom > _this.currentZoom) _this.currentZoom = zoom;
                if (feature){
                    _this.drawPanel(feature);
                }
            }
        }
    });

    var _this = {
        currentTab: 'sediment',
        currentFeature: null,
        currentZoom: 6,

        showMap: function(){
            $('body, .map-wrap').addClass('show-map');
            if (!PollutionTracker.map) {
                PollutionTracker.buildMap({
                    id: 'map',
                    geojson: geojson,
                    style: '/wp-content/plugins/pollution-tracker/map-style.json'
                });
            }
        },

        buildMap: function (args) {
            _this.map = L.map(args.id, {
                //center: L.latLng(49.5,-123.5),
                maxZoom: 14,
                minZoom: 4,
                //zoomSnap: 0.5,
                zoomDelta: 1,
                maxBounds: L.latLngBounds(L.latLng(60, -150), L.latLng(45, -100)),
                //scrollWheelZoom: false
            }).setView([51.5,-128], _this.currentZoom);


            _this.gl = L.mapboxGL({
                accessToken: 'not required',
                style: args.style,
            }).addTo(_this.map);

            /*map.on('viewlevelchange', function(e){
                console.log(e);
            });*/

            //map.setView(L.latLng(49.5,-123.5), 4 );


            _this.markers = L.markerClusterGroup();
            var geoJsonLayer = L.geoJson(args.geojson, {
                onEachFeature: function (feature, layer) {
                    layer.on({
                        click: function(e) {
                            // Handle marker click
                            if (_this.map.getZoom() < 12){
                                _this.currentZoom = 12;
                            }else{
                                _this.currentZoom = _this.map.getZoom();
                            }
                            _this.showPanel(e.target.feature);
                        }
                    })
                }
            });
            _this.markers.addLayer(geoJsonLayer);
            _this.map.addLayer(_this.markers);
            //map.fitBounds(markers.getBounds());

            /*$(document).on('resize', '.panel', function(e){
                _this.updateGridlines()
            });*/

            return _this.map;
        },

        showPanel: function(feature){
            var hash = 'Map|' + feature.properties.site_id + '|' + _this.currentTab;//  + '|' + _this.currentZoom;
            window.location.hash = hash;
        },

        drawPanel: function(feature){

            var _this = this;
            _this.currentFeature = feature;
            $('.map-wrap .panel').html(_this.getPopupHTML({feature: _this.currentFeature, sortby: _this.currentTab}));
            $('.panel .tabs li[data-tab=' + _this.currentTab + ']').addClass('active');
            $('.map-wrap').addClass('show-panel');

            _this.map.setView(L.latLng(feature.geometry.coordinates[1],feature.geometry.coordinates[0]), _this.currentZoom, {zoomAnimation: true, zoomAnimationThreshold:12});
            _this.markers.refreshClusters()
        },

        getFeatureByID: function(featureID){
            var arrFound =  $.grep(geojson.features, function(feature){
                if (feature.properties.site_id == featureID) return true;
            });
            return arrFound[0];
        },

        getPopupHTML: function (args) {
            var _this = this;
            var feature = args.feature;
            var sortby = args.sortby || 'sediment';

            var strHTML;

            var contaminants = safeRead(feature.properties, 'contaminants');

            var siteRank = safeRead(feature.properties, sortby + '_rank');
            var siteRankPercent = 100 - ((siteRank-1)/(geojson.counts[sortby]-1))*100;
            var maxValue = 0;


            strHTML = '<div class="header"><div class="close">&times;</div><h2 class="title">' + feature.properties.title + '</h2>';
            strHTML += '<div class="subhead">Data collected on ' + feature.properties.sampling_date + '</div>';
            strHTML += '<ul class="tabs"><li data-tab="sediment">Sediment</li><li data-tab="mussels">Mussels</li></ul>';
            strHTML += '</div>';
            strHTML += '<div class="site-ranking"><div class="content">';
            strHTML += '<h3>How this site compares</h3>';
            if (sortby == 'sediment') strHTML += '<p>We collected ocean floor sediment from ' + geojson.counts[sortby] + ' coastal B.C. locations to survey sea bed contaminants.<br><br></p>';
            if (sortby == 'mussels') strHTML += '<p>We collected samples from filter feeding mussels in ' + geojson.counts[sortby] + ' coastal B.C. locations to survey contaminants in the water column.</p>';
            strHTML += '<div class="rank"><div class="graph"><div class="pointerContainer"><div class="pointer" style="left: ' + siteRankPercent + '%; background-color:#' + _this.getColorAtPosition('C60000', 'FFCB00', siteRankPercent/100) + ';"><div class="pointer-label" style="color:#' + _this.getColorAtPosition('C60000', 'FFCB00', siteRankPercent/100) + ';">' +  siteRank + '</div></div></div><div class="label">Better</div><div class="label">Worse</div></div>';
            strHTML += '<div class="lower-labels"><div class="label">' + geojson.counts[sortby] + '</div><div class="label">1</div></div>';
            strHTML += '</div></div>';

            if (contaminants) {
                _this.sort(sortby, contaminants);
                strHTML += "<div class='content'><h3>Most prevalent contaminants</h3>" +
                    "<p>We found the highest concentrations of these contaminants at this site. (The graphs indicate how they compare to other sites we surveyed.)</p>";
                    //"<table><tr><th>Name</th><th>SV</th><th>SR</th><th>MV</th><th>MR</th></tr>";

                strHTML += '<div class="contaminants-graph"><div class="gridlines"><div class="gridline" data-value="' + geojson.counts[sortby] + '" data-label="Better"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline" data-value="1" data-label="Worse"></div></div>';
                contaminants.forEach(function (item) {
                    var rank = safeRead(item, sortby ,'rank');
                    if (rank) {
                        var percent = 100 - (((rank-1) / (geojson.counts[sortby]-1)) * 100);
                        //strHTML += "<tr><td>" + item.name + "</td><td class='sediment values'>" + (safeRead(item,'sediment', 'value') || '--') + "</td><td class='sediment rank'>" + (safeRead(item,'sediment','rank')||'--') + "</td><td class='mussels value'>" + (safeRead(item,'mussels','value')||'--') + "</td><td class='mussels rank'>" + (safeRead(item,'mussels','rank')||'--') + "</td></tr>";
                        strHTML += '<div class="contaminant" data-id="' + item.id + '" data-rank="' + rank + '" data-slug="' + item.slug + '"><div class="name">' + item.name + '</div><div class="graph"><div class="bar"></div><div class="indicator" style="left:' + percent + '%; background-position: ' + percent + '%">' + rank +'</div></div></div>';
                        if (safeRead(item,sortby,'value') > maxValue) maxValue = item[sortby].value;
                    }
                });
                //strHTML += '</table></div>';
                strHTML += '</div></div>';
            } else {
                strHTML += '<strong>No data</strong>';
            }

            return strHTML;
        },



        sort: function (prop, arr) {
            prop = prop.split('.');
            var len = prop.length;
            var v1,v2;

            arr.sort(function (a, b) {

                v1 = parseFloat(safeRead(a,prop,'rank')) || 0;
                v2 = parseFloat(safeRead(b,prop,'rank')) || 0;


                if (v1 < v2) {
                    return -1;
                } else if (v1 > v2) {
                    return 1;
                } else {
                    // Then sort by name
                    var n1 = safeRead(a,'name');
                    var n2 = safeRead(b,'name');
                    if (n1 > n2){
                        return 1
                    }else if(n1 < n2){
                        return -1;
                    }else {
                        return 0;
                    }
                }
            });
            return arr;
        },


        getColorAtPosition: function(color1, color2, ratio){

            var hex = function (x) {
                x = x.toString(16);
                return (x.length == 1) ? '0' + x : x;
            };

            var r = Math.ceil(parseInt(color1.substring(0, 2), 16) * ratio + parseInt(color2.substring(0, 2), 16) * (1 - ratio));
            var g = Math.ceil(parseInt(color1.substring(2, 4), 16) * ratio + parseInt(color2.substring(2, 4), 16) * (1 - ratio));
            var b = Math.ceil(parseInt(color1.substring(4, 6), 16) * ratio + parseInt(color2.substring(4, 6), 16) * (1 - ratio));

            return hex(r) + hex(g) + hex(b);
        }
    };
    return _this;
})(jQuery);


function GridLines(args){
    $ = jQuery;

    // args{graph, min, max, direction, decimalPlaces}

    this.getNiceMax = function (max) {
        return Number((parseInt(Number(max).toExponential().split('.')[0]) + 1) + 'e' + Number(max).toExponential().split('e')[1]);
    };

    var _this = this;
    this.graph = args.graph;
    this.preferredSteps = [10,8,5,4,2];
    this.dataMin = args.min;
    // Make the max a nicer number
    //this.dataMax = max;
    this.gridMax = this.getNiceMax(args.max);
    //this.decimalPlaces = args.decimalPlaces | GridLines.getDecimalPlaces(args.max);


    this.direction = args.direction;

    $(window).on('resize', function(){
        _this.updateGridlines();
    });
    this.updateGridlines();
}


/*
GridLines.getDecimalPlaces = function (num) {
    var decimals = Number(Number(num).toExponential().split('e')[1]);
    decimals = (decimals < 0) ? Math.abs(decimals) + 1 : 0;
    return decimals;
};
*/

/*
GridLines = {

    // args{graph, min, max, direction, decimalPlaces}

    getNiceMax: function (max) {
        return Number((parseInt(Number(max).toExponential().split('.')[0]) + 1) + 'e' + Number(max).toExponential().split('e')[1]);
    },

    getDecimalPlaces: function (num) {
        var decimals = Number(Number(num).toExponential().split('e')[1]);
        decimals = (decimals < 0) ? Math.abs(decimals) + 1 : 0;
        return decimals;
    }
};
*/

GridLines.prototype = {

    updateGridlines: function(){
        var _this = this;
        var height = _this.graph.closest('table').height();
        _this.graph.height(height);
        console.log(height);
        _this.graph.find('.gridline').remove();
        var graphWidth = _this.graph.width();
        var range = _this.gridMax - _this.dataMin;
        var minSpace = 50;
        var divisions = [];
        var result = [{percent:0,value:_this.dataMin}];

        _this.preferredSteps.some(function(divisor){
            var percent = 1/(range/(range/divisor));
            if (percent*graphWidth > minSpace){
                for(var x=1;x<divisor;x++){
                    result.push({percent:x*percent*100,value:_this.gridMax*x*percent});
                }
                return result;
            }
        });
        result.push({percent:100,value:_this.gridMax});

        for(var x=0;x<result.length;x++){
            var percent = result[x].percent;
            if (_this.direction == -1) percent = 100-percent;
            var value = result[x].value;

            var strValue = Number(String(value.toPrecision(8))).toString();//.toFixed(_this.decimalPlaces);
            var line = $('<div class="gridline bottom-label"></div>');
            line.css({left: percent + '%'});
            line.attr('data-value', strValue);
            _this.graph.append(line);
        }
        console.log(_this.dataMin,_this.gridMax,result);
        return result;
    },
};
