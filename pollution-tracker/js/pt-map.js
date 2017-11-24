//var PollutionTracker = PollutionTracker || {}
var PollutionTracker = (function($){
    var _this = this;
    var map;

    $(document).on('click', '.showMap', function(e){
        e.preventDefault();
        $('body, .map-wrap').addClass('show-map');
        map.invalidateSize();
    });

    $(document).on('click', '.map-wrap .close-bar', function(e){
        $('body, .map-wrap').removeClass('show-map');
    });

    return {
        currentTab: 'sediment',
        currentFeature: null,
        buildMap: function (args) {
            var _this = this;
            map = L.map(args.id, {
                //center: [49.5,-123.5],
                zoom: 7,
                maxZoom: 14,
                minZoom: 4,
                maxBounds: L.latLngBounds(L.latLng(60, -141), L.latLng(45, -100)),
                //scrollWheelZoom: false
            });

            var gl = L.mapboxGL({
                accessToken: 'not required',
                style: args.style,
            }).addTo(map);

            var markers = L.markerClusterGroup();
            var geoJsonLayer = L.geoJson(args.geojson, {
                onEachFeature: function (feature, layer) {
                    layer.on({
                        click: function(e) {
                            // Handle marker click
                            var feature = safeRead(e, 'target', 'feature');
                            _this.currentFeature = feature;
                            $('.map-wrap .panel').html(_this.getPopupHTML({feature: _this.currentFeature, sortby: _this.currentTab}));
                            $('.panel .tabs li[data-tab=' + _this.currentTab + ']').addClass('active');
                            $('.map-wrap').addClass('show-panel');


                        }
                    })
                }
            });
            markers.addLayer(geoJsonLayer);
            map.addLayer(markers);
            map.fitBounds(markers.getBounds());

            $(document).on('click', '.panel .tabs li', function(e){
                var tab = $(this).attr('data-tab');
                _this.currentTab = tab;
                //console.log('You clicked',tab);
                $('.map-wrap .panel').html(_this.getPopupHTML({feature: _this.currentFeature, sortby: _this.currentTab}));
                $('.panel .tabs li[data-tab=' + tab + ']').addClass('active');

            });

            $(document).on('resize', '.panel', function(e){
                _this.updateGridlines()
            });

            return map;
        },

        getPopupHTML: function (args) {
            var _this = this;
            var feature = args.feature;
            var sortby = args.sortby || 'sediment';

            var strHTML;

            var contaminants = safeRead(feature.properties, 'contaminants');
            var siteRankPercent = 50;
            var maxValue = 0;


            strHTML = '<div class="header"><div class="close">&times;</div><h2 class="title">' + feature.properties.title + '</h2>';
            strHTML += '<div class="subhead">Data collected on ' + feature.properties.sampling_date + '</div>';
            strHTML += '<ul class="tabs"><li data-tab="sediment">Sediment</li><li data-tab="muscles">Muscles</li></ul>';
            strHTML += '</div>';
            strHTML += '<div class="site-ranking"><div class="content"><h3>How this site compares</h3>';
            strHTML += '<p>We surveyed ' + geojson.counts[sortby] + ' coastal B.C. locations (near areas with notable industrial activity).</p>';
            strHTML += '<div class="rank"><div class="graph"><div class="pointerContainer"><div class="pointer" style="left: ' + siteRankPercent + '%; background-color:#' + _this.getColorAtPosition('C60000', 'FFCB00', siteRankPercent/100) + ';"></div></div><div class="label">Better</div><div class="label">Worse</div></div>';
            strHTML += '<div class="lower-labels"><div class="label">' + geojson.counts[sortby] + '</div><div class="label">1</div></div>';
            strHTML += '</div></div>';

            if (contaminants) {
                _this.sort(sortby, contaminants);
                strHTML += "<div class='content'><h3>Most prevalent contaminants</h3>" +
                    "<p>We found the highest concentrations of these contaminants at this site. (The graphs indicate how they compare to other sites we surveyed.)</p>";
                    //"<table><tr><th>Name</th><th>SV</th><th>SR</th><th>MV</th><th>MR</th></tr>";

                strHTML += '<div class="contaminants-graph"><div class="gridlines"><div class="gridline" data-value="1" data-label="Better"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline"></div><div class="gridline" data-value="' + geojson.counts[sortby] + '" data-label="Worse"></div></div>';
                contaminants.forEach(function (item) {
                    var rank = safeRead(item, sortby ,'rank');
                    if (rank) {
                        var percent = 100 - (rank / geojson.counts[sortby] * 100);
                        //strHTML += "<tr><td>" + item.name + "</td><td class='sediment values'>" + (safeRead(item,'sediment', 'value') || '--') + "</td><td class='sediment rank'>" + (safeRead(item,'sediment','rank')||'--') + "</td><td class='muscles value'>" + (safeRead(item,'muscles','value')||'--') + "</td><td class='muscles rank'>" + (safeRead(item,'muscles','rank')||'--') + "</td></tr>";
                        strHTML += '<div class="contaminant" data-id="' + item.id + '" data-rank="' + rank + '"><div class="name">' + item.name + '</div><div class="graph"><div class="bar"></div><div class="indicator" style="left:' + percent + '%; background-position: ' + percent + '%">' + rank +'</div></div></div>';
                        if (safeRead(item,sortby,'value') > maxValue) maxValue = item[sortby].value;
                    }
                });
                //strHTML += '</table></div>';
                strHTML += '</div></div>';
            } else {
                strHTML += '<strong>No data</strong>';
            }

            _this.updateGridlines($('.map-wrap .panel .contaminants-graph'),0,maxValue);
            return strHTML;
        },

        updateGridlines: function(graph, min, max){
            graph.find('.gridline').remove();
            var graphWidth = graph.width();
            var range = max - min;
            var preferredSteps = [10,5,4,2];
            var minSpace = 40;
            var divisions = [];
            var result = [{percent:0,value:min}];

            preferredSteps.some(function(divisor){
                var percent = 1/(range/(range/divisor));
                if (percent*graphWidth > minSpace){
                    for(var x=0;x<divisor;x++){
                        result.push({percent:x*percent,value:max*x*percent});
                    }
                    return result;
                }
            });
            result.push({percent:100,value:max});
            console.log(result);
            return result;
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
    }
})(jQuery);
