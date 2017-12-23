<?php

/**
 *
 */

class PollutionTracker{

    public static function init(){
        global $wpdb;

        add_shortcode('PTMap', array('PollutionTracker', 'mapShortcode'));
        add_shortcode('PTContaminantMenu', array('PollutionTracker', 'contaminantMenuShortcode'));


        add_action( 'add_meta_boxes', array('PollutionTracker','addContaminantMetaBox') );
        add_action( 'save_post', array('PollutionTracker', 'savePost' ));

        add_action( 'wp_ajax_nopriv_get_contaminant_details', array('PollutionTracker', 'getContaminantDetails' ));
        add_action( 'wp_ajax_get_contaminant_details', array('PollutionTracker', 'getContaminantDetails' ));

        // Request http://domain.org?updateRankings to update contaminant rankings
        if (isset($_GET['updateRankings'])) {
            self::updateContaminantRankings();
        }
    }

    // Same handler function...
    public static function getContaminantDetails() {
        global $wpdb;
        $contaminant_id = intval( $_GET['contaminant_id'] );
        $site_id = intval( $_GET['site_id'] );
        $source_id = intval( $_GET['source_id'] );
        $values = PollutionTracker::getChildContaminantValues(array('site_id'=>$site_id, 'contaminant_id'=>$contaminant_id, 'source_id'=>$source_id));
        //error_log('Contaminant ajax:' . $contaminant_id);
        //echo json_encode($values);

        $str_html = "<table class='child-contaminants'>";
        foreach ($values AS $contaminant){
            $value = $contaminant->value;
            if ($contaminant->not_detected) $value = "Not detected";
            $str_html.="<tr><td>{$contaminant->name}</td><td>{$value}</td>";
        }
        $str_html.= "</table>";
        echo $str_html;
        wp_die();
    }

    public static function enqueueScripts(){

        // Using Openmaptiles.org, leaflet, and mapbox gl
        // Can't just use mapbox since it can't custer html markers/popups
        // Edit map style here: http://editor.openmaptiles.org

        // Wanted this for array.find, but didn't seem to work for MS Edge
        //wp_enqueue_script( 'polyfill', 'https://cdn.polyfill.io/v2/polyfill.min.js', array(), '20151215', false );


        wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.2.0/dist/leaflet.js', array(), '20151215', false );
        wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.2.0/dist/leaflet.css' );

        wp_enqueue_script( 'leaflet-clusterer', 'https://unpkg.com/leaflet.markercluster@1.2.0/dist/leaflet.markercluster.js', array(), '20151215', false );
        wp_enqueue_style( 'leaflet-clusterer', plugin_dir_url(__FILE__) . 'css/MarkerCluster.css', false, filemtime(plugin_dir_path( __FILE__ ) . '/css/MarkerCluster.css') );

        wp_enqueue_script( 'mapbox', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js', array(), '20151215', false );
        wp_enqueue_style( 'mapbox', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css' );

        wp_enqueue_script( 'pt-map', plugin_dir_url(__FILE__) . '/js/pt-map.js', array('jquery'), filemtime(plugin_dir_path( __FILE__ ) . '/js/pt-map.js'), false );


        wp_enqueue_script( 'leaflet-mapbox', 'http://rawgit.com/mapbox/mapbox-gl-leaflet/master/leaflet-mapbox-gl.js', array(), '20151215', false );
        //wp_enqueue_script( 'leaflet-esri', 'https://unpkg.com/esri-leaflet@2.1.1', array(), '20151215', false );
    }

    public static function mapShortcode($args){
        $html = '<div class="map-wrap"><div class="close-bar">Click here to close this map</div><div id="map" class="map"></div><div class="panel"></div></div>';
        $script = "<script type=\"text/javascript\">\n";


        $site_data = self::getPointersData();
        $script .= "var geojson = {
            type: 'FeatureCollection',
            counts:{sediment:" . $site_data['sediment_contaminant_count'] . ",mussels:" . $site_data['mussels_contaminant_count'] . "},
            features: [
        ";

        foreach($site_data['sites'] as $site){
            $script .= "{
                type: 'Feature',
                geometry: {
                    type: 'Point',
                    coordinates: [" . $site->longitude . "," . $site->latitude . "]
                },
                properties: {
                    title: '" . $site->name . "',
                    site_id: '" . $site->site_id . "',
                    html: '<em>HTML will go here</em>',
                    sampling_date: '" . (($site->sampling_date)?date('F j, Y', strtotime($site->sampling_date)):'') . "',
                    sediment_rank: " . (($site->sediment_rank)?$site->sediment_rank:'null') . ",
                    mussels_rank: " . (($site->mussels_rank)?$site->mussels_rank:'null') . ",
                    contaminants: " . json_encode($site->contaminants) . "
                }},\n";
        }
        $script .= ']};';


        //$script .= "PollutionTracker.buildMap({id:'map',geojson:geojson, style:'/wp-content/plugins/pollution-tracker/map-style.json" . "'});";

        $script .= "</script>";
        return $html . $script;
    }

    public static function contaminantMenuShortcode($args){
        global $wpdb;
        $contaminants = $wpdb->get_results("SELECT * FROM wp_contaminants ORDER BY name;");
        $walker = new PTWalker();
        return $walker->walk($contaminants, 3);
    }

    public static function updateContaminantRankings(){
        global $wpdb;

        error_log('Updading contaminant rankings');

        /* Kelsey says: (11/03/2017)
         * Once all individual contaminants are sorted and ranked in the database,
         * we’d need to take the average of the rankings for all of the individual ‘legacy pesticides’,
         * and the same for ‘current use pesticides’ (lists of chemicals to be provided).
         * This is because for the top 5 ranked contaminants/contaminant classes in the pop-ups,
         * we’ll show ‘legacy pesticides’ and ‘current use pesticides’ rather than the individual chemicals.
         * However, for the overall average site ranking, we will calculate the average based on
         * all of the individual chemical rankings. Hopefully that makes sense.
         *
         * 1.	Looking at the data spreadsheets, there are four categories of sites:
                o	Those with measured contaminant concentrations
                o	Those that weren’t analyzed for a given contaminant (NA)
                o	Those that were analyzed, but the contaminant wasn’t detected in the sample (ND), meaning that the concentration could be anywhere from 0 to the lowest concentration that the lab’s instruments can detect
                o	Those for which a contaminant was detected, but once blank-corrected, the concentration was zero. The lab runs a blank that theoretically should not contain the contaminant; however, if & when the contaminant is detected in the blank, we subtracted this blank concentration from the sample concentration (and sometimes this resulted in a sample concentration equal to or less than zero). These are shown as ‘0’ in the spreadsheet.

         *
         * Data:
            0: 0
            NA (not analyzed): null
            ND (not detected): -1



        source_ids
            1: Sediment
            2: mussels
         */

        // Apply rankings to all contaminants
        $contaminants = $wpdb->get_results('SELECT * FROM wp_contaminants WHERE is_group IS NULL');
        $source_ids = $wpdb->get_col('SELECT DISTINCT source_id FROM wp_contaminant_values;');

        //error_log('Update rankings: ' . print_r($contaminants,true));

        $wpdb->query("UPDATE wp_contaminant_values SET rank=NULL;");


        foreach($source_ids as $source_id) {
            foreach ($contaminants as $contaminant) {
                //error_log('Update ' . $source_id . ':' . print_r($contaminant,true));
                $wpdb->query("SET @rank:=0;");
                $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET rank=@rank:=@rank+1 WHERE contaminant_id=%d AND source_id=%d AND value >0 ORDER BY `value` DESC;", $contaminant->id, $source_id);
                $result = $wpdb->get_results($sql);


                // Count how many items there are to rank
                $sql = $wpdb->prepare('SELECT COUNT(*) AS item_count FROM wp_contaminant_values WHERE contaminant_id=%d AND source_id=%d AND value IS NOT NULL AND not_detected=0;', $contaminant->id, $source_id);
                //error_log($sql);
                $result = $wpdb->get_results($sql);
                $item_count = $result[0]->item_count;

                // Get what the max rank should be
                $sql = $wpdb->prepare('SELECT COUNT(*) AS max_rank FROM wp_contaminant_values WHERE value > 0 AND contaminant_id=%d AND source_id=%d;', $contaminant->id, $source_id);
                $result = $wpdb->get_results($sql);
                $max_rank = $result[0]->max_rank;
                //echo "{$contaminant->name} : $max_rank\n";

                // Set "0" values to max rank+1
                $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET rank=%d WHERE value >=0 AND not_detected=0 AND contaminant_id=%d AND source_id=%d AND rank IS NULL;", $max_rank+1, $contaminant->id, $source_id);
                $result = $wpdb->get_results($sql);

                // Set non-detects to last place
                $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET rank=%d WHERE value >=0 AND not_detected=1 AND contaminant_id=%d AND source_id=%d AND rank IS NULL;", $item_count+1, $contaminant->id, $source_id);
                $result = $wpdb->get_results($sql);

                // When values are tied, set rank to the same for both (the lower number)
                if ($contaminant->id == 25) {
                    $sql = $wpdb->prepare("SELECT v.value, COUNT(*) AS tie_count from wp_contaminant_values v where v.contaminant_id=%d and v.source_id=%d GROUP BY v.value;", $contaminant->id, $source_id);
                    $result = $wpdb->get_results($sql);
                    foreach ($result as $row) {
                        if ($row->tie_count > 1) {
                            // Get rank of first item it tie set
                            $sql = $wpdb->prepare("SELECT v.rank from wp_contaminant_values v WHERE v.contaminant_id=%d and source_id=%d AND CAST(v.value AS DECIMAL(10,8))=%f ORDER BY RANK LIMIT 1;", $contaminant->id, $source_id, $row->value);
                            $result = $wpdb->get_results($sql);
                            $rank = $result[0]->rank;
                            $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET rank=%d WHERE contaminant_id=%d and source_id=%d AND CAST(value AS DECIMAL(10,8))=%f;", $rank, $contaminant->id, $source_id, $row->value);
                            $result = $wpdb->get_results($sql);
                        }
                    }
                }


            }
        }

        // Apply rankings to contaminant groupings

        /*Once all individual contaminants are sorted and ranked in the database,
         * we’d need to take the average of the rankings for all of the individual ‘legacy pesticides’,
         * and the same for ‘current use pesticides’ (lists of chemicals to be provided).
         * This is because for the top 5 ranked contaminants/contaminant classes in the pop-ups,
         * we’ll show ‘legacy pesticides’ and ‘current use pesticides’ rather than the individual chemicals.
        */
        $sites = $wpdb->get_results('SELECT * FROM wp_sites');
        foreach($sites as $site){
            $groups = $wpdb->get_results('SELECT * FROM wp_contaminants WHERE aggregate=1');
            foreach($groups as $group){
                $arr_contaminant_ids = $wpdb->get_col("SELECT id FROM wp_contaminants WHERE parent_id=" . $group->id);

                foreach($source_ids as $source_id) {
                    $sql = "SELECT AVG(rank) as rank FROM wp_contaminant_values WHERE contaminant_id IN(" . implode(',', $arr_contaminant_ids) . ") AND site_id=" . $site->id . " AND source_id=" . $source_id . " AND value IS NOT NULL;";
                    $average_rank = $wpdb->get_col($sql);

                    $average_rank = $average_rank[0];
                    if (is_null($average_rank)) $average_rank = 'NULL';

                    // Remove old aggregate values
                    $sql = $wpdb->prepare("DELETE FROM wp_contaminant_values WHERE site_id=%d AND contaminant_id=%d AND source_id=%d;", $site->id, $group->id, $source_id);
                    $result = $wpdb->get_results($sql);

                    $sql = "SELECT * FROM wp_contaminant_values WHERE contaminant_id IN(" . implode(',', $arr_contaminant_ids) . ") AND site_id=" . $site->id . " AND source_id=" . $source_id;
                    $result = $wpdb->get_results($sql);
                    $child_values_count = count($result);
                    $not_detected = 1;
                    foreach($result as $row) if ($row->not_detected==0) $not_detected = 0;

                    if ($child_values_count) {

                        // Upsert value
                        $sql = "INSERT INTO wp_contaminant_values (site_id, contaminant_id, source_id, rank, not_detected) VALUES ({$site->id}, {$group->id}, {$source_id}, {$average_rank}, {$not_detected}) ON DUPLICATE KEY UPDATE rank={$average_rank};";
                        //error_log($sql);
                        $result = $wpdb->get_results($sql);

                        // Update aggregate values
                        $sql = "SELECT SUM(value) AS value FROM wp_contaminant_values WHERE contaminant_id IN(" . implode(',', $arr_contaminant_ids) . ") AND site_id=" . $site->id . " AND source_id=" . $source_id;
                        $value = $wpdb->get_col($sql);
                        $value = $value[0];

                        if (is_null($value)) $value = "NULL";

                        $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET value=" . $value . " WHERE contaminant_id=%d AND site_id=%d AND source_id=%d", $group->id, $site->id, $source_id);
                        $wpdb->get_results($sql);
                    }
                }
            }
        }


        // Out of all 51 sites, average the individual contaminant rankings
        /* However, for the overall average site ranking, we will calculate the average based on
        * all of the individual chemical rankings. Hopefully that makes sense.*/

        // Problem: some contaminants won't have a ranking at every site.

        // Clear old ranking data
        $wpdb->query("UPDATE wp_sites SET sediment_rank=NULL, mussels_rank=NULL, sediment_average_rank=null, mussels_average_rank=null;");

        // Get average rank for each item as a float
        foreach($source_ids as $source_id) {
            foreach ($sites as $site) {
                $sql = "SELECT AVG(rank) as rank
                    FROM wp_contaminant_values v
                    JOIN wp_contaminants c ON v.contaminant_id = c.id
                    WHERE
                    site_id=" . $site->id . " AND
                    source_id=" . $source_id . " AND
                    c.is_group IS NULL AND
                    value IS NOT NULL;"; // NA values are NULL, and will not be considered in our average
                $average_rank = $wpdb->get_col($sql);
                $average_rank = $average_rank[0];

                $column = null;
                if ($source_id == 1) $column = 'sediment';
                if ($source_id == 2) $column = 'mussels';

                if ($column) {
                    $sql = $wpdb->prepare("
                    UPDATE wp_sites s
                    SET " . $column . "_average_rank=%f
                    WHERE 
                        id=%d", $average_rank, $site->id);
                    $wpdb->query($sql);
                }
            }
        }

        // Then update sorted rank for sites
        foreach($source_ids as $source_id) {
            $column = null;
            if ($source_id == 1) $column = 'sediment';
            if ($source_id == 2) $column = 'mussels';

            if ($column) {
                $wpdb->query("SET @rank:=0;");
                $sql = $wpdb->prepare("UPDATE wp_sites SET " . $column . '_rank=@rank:=@rank+1 WHERE ' . $column . '_average_rank>0 ORDER BY ' . $column . '_average_rank ASC;', $contaminant->id, $source_id);
                $wpdb->query($sql);
            }
        }

    }


    public static function getPointersData(){
        global $wpdb;

        $data = ['sites'=>[]];
        $sites = $wpdb->get_results('SELECT * FROM wp_sites');
        $arr_contaminants = [];
        $arr_sediments = [];
        $arr_mussels = [];

        //echo "Sites:" . count($sites);

        foreach ($sites as $site){
            $contaminants = $wpdb->get_results('SELECT 
                    wp_contaminants.id AS id,
                    wp_contaminants.name AS name,
                    wp_contaminants.slug AS slug,
                    wp_contaminant_values.source_id AS source_id,
                    wp_contaminant_values.value AS value,
                    wp_contaminant_values.rank AS rank,
                    wp_contaminant_values.not_detected AS not_detected
                FROM wp_contaminant_values 
                JOIN wp_contaminants ON wp_contaminant_values.contaminant_id = wp_contaminants.id 
                WHERE
                    (wp_contaminants.parent_id IS NULL OR wp_contaminants.parent_id NOT IN(37,39)) AND
                    wp_contaminant_values.site_id = ' . $site->id . ' AND
                    wp_contaminant_values.rank IS NOT NULL
                ORDER BY wp_contaminant_values.rank ASC');


            $arr_contaminants = [];
            foreach ($contaminants as $contaminant){
                if (!$contaminant->not_detected && $contaminant->rank!==null) { // Don't include Not-analysed (null value) items
                    $arr_contaminants[$contaminant->name]['name'] = $contaminant->name;
                    $arr_contaminants[$contaminant->name]['slug'] = $contaminant->slug;
                    if ($contaminant->source_id == 1) {
                        $arr_contaminants[$contaminant->name]['sediment']['value'] = $contaminant->value;
                        $arr_contaminants[$contaminant->name]['sediment']['rank'] = $contaminant->rank;
                        if (!in_array($site->id, $arr_sediments)) array_push($arr_sediments, $site->id);
                    } else {
                        $arr_contaminants[$contaminant->name]['mussels']['value'] = $contaminant->value;
                        $arr_contaminants[$contaminant->name]['mussels']['rank'] = $contaminant->rank;
                        if (!in_array($site->id, $arr_mussels)) array_push($arr_mussels, $site->id);
                    }
                }
            }

            $arr_contaminants_array = [];
            foreach ($arr_contaminants as $contaminant){
                $arr_contaminants_array[] = $contaminant;
            }

            //error_log(print_r($arr_contaminants_array,true));
            //echo print_r($arr_contaminants_array,true);

            $site_data = new stdClass();
            $site_data->name = $site->name;
            $site_data->site_id = preg_replace('/\s/','',$site->site_id);
            $site_data->longitude = $site->longitude;
            $site_data->latitude = $site->latitude;
            $site_data->sampling_date = $site->sampling_date;
            $site_data->sediment_rank = $site->sediment_rank;
            $site_data->mussels_rank = $site->mussels_rank;
            $site_data->contaminants = $arr_contaminants_array;
            array_push($data['sites'], $site_data);
        }

        $data['sediment_contaminant_count'] = count($arr_sediments);
        $data['mussels_contaminant_count'] = count($arr_mussels);
        return $data;
    }

    public static function getContaminantValues($args){
        global $wpdb;


        $sql = $wpdb->prepare('
        SELECT
            wp_sites.*,
            sediment.id AS sediment_id,
            mussels.id AS mussels_id,
            sediment.value AS sediment_value,
            mussels.value AS mussels_value,
            sediment.not_detected AS sediment_not_detected,
            mussels.not_detected AS mussels_not_detected
            FROM wp_sites 
            LEFT OUTER JOIN wp_contaminant_values sediment ON sediment.site_id = wp_sites.id AND sediment.source_id=1 AND sediment.contaminant_id=%d
            LEFT OUTER JOIN wp_contaminant_values mussels ON mussels.site_id = wp_sites.id AND mussels.source_id=2 AND mussels.contaminant_id=%1$d
            ORDER BY sort;
            ', $args['contaminant_id']);
        //echo $sql;
        $result = $wpdb->get_results($sql);
        //error_log($sql);
        //error_log(print_r($result,true));;

        return $result;

    }

    public static function getChildContaminantValues($args){
        global $wpdb;

        $sql = $wpdb->prepare('SELECT id FROM wp_contaminants WHERE parent_id=%d', $args['contaminant_id']);
        $contaminants = $wpdb->get_col($sql);
        $str_contaminant_ids = implode(', ', $contaminants);

        $sql = $wpdb->prepare('
        SELECT
            wp_contaminants.*,
            v.value AS value,
            v.not_detected AS not_detected
            FROM wp_contaminants
            LEFT JOIN wp_contaminant_values v ON v.contaminant_id = wp_contaminants.id AND v.source_id=%d AND v.contaminant_id IN (' . $str_contaminant_ids . ')
            WHERE v.site_id=%d AND
            v.value IS NOT NULL
            ORDER BY value DESC;', $args['source_id'],$args['site_id']);
        //error_log( $sql);
        $result = array();
        $result = $wpdb->get_results($sql);

        //error_log($sql);
        //error_log(print_r($result,true));
        //error_log("Length: " . count($result));

        return $result;

    }

    /**
     * Adds the meta box to the page screen
     */
    public static function addContaminantMetaBox(){
        if (get_page_template_slug() == 'page-contaminant.php') {
            add_meta_box(
                'contaminant-meta-box', // id, used as the html id att
                __('Contaminant'), // meta box title, like "Page Attributes"
                array('PollutionTracker', 'metaBoxCB'), // callback function, spits out the content
                'page', // post type or page. We'll add this to pages only
                'side', // context (where on the screen
                'low' // priority, where should this go in the context?
            );
        }
    }

    /**
     * Callback function for our meta box.  Echos out the content
     */
    function metaBoxCB( $post )
    {
        global $post, $wpdb;

        $values = get_post_custom( $post->ID );
        $contaminant = isset( $values['contaminant_id'] ) ? array_pop($values['contaminant_id']) : '';
        $contaminants = $wpdb->get_results("SELECT * FROM wp_contaminants ORDER BY name");

        wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );

        echo '<select name="contaminant_id">';
        echo "<option value=''>Select contaminant</option>";
        foreach($contaminants as $item){
            echo "<option value='{$item->id}'" . (($item->id==$contaminant)?' selected':'') . ">{$item->name}</option>";
        }
        echo '</select>';

    }

    public static function savePost( $post_id ){
        global $wpdb;

        $contaminants = $wpdb->get_results("SELECT * FROM wp_contaminants ORDER BY name");

        // Bail if we're doing an auto save
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // if our nonce isn't there, or we can't verify it, bail
        if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;

        // if our current user can't edit this post, bail
        if( !current_user_can( 'edit_post' ) ) return;

        $contaminant_id = null;
        foreach ($contaminants as $contaminant) {
            if ($contaminant->id == $_POST['contaminant_id']) $contaminant_id = $contaminant->id;
        }

        //error_log("Save contaminant: " . $contaminant_id);

        if( $contaminant_id ) {
            update_post_meta($post_id, 'contaminant_id', $contaminant_id);
            $slug = get_post_field( 'post_name', $post_id );
            //error_log("Set contaminant_id to {$contaminant_id} for page: {$slug}");
            if ($slug) {
                $wpdb->query("UPDATE wp_contaminants SET slug='" . $slug . "' WHERE id=$contaminant_id;");
            }
        }
    }

}


class PTWalker extends Walker{

    var $tree_type = array('post_type', 'taxonomy', 'custom');

    // Tell Walker where to inherit it's parent and id values
    var $db_fields = array(
        //'parent' => 'parent_id',
        'id'     => 'id'
    );

    private $curItem;

    /**
     * At the start of each element, output a <li> and <a> tag structure.
     *
     * Note: Menu objects include url and title properties, so we will use those.
     */
    function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $this->curItem = $item;
        //print_r($item);
        //if ($item->name == 'Pesticides') return false;
        $classes = [];
        if ($item->object_id === get_the_ID()) $classes[]='current';
        if ($item->is_group) $classes[]='is_group';
        //$url = ($item->is_group)?'#':"/contaminants/" . $item->slug;
        $url = "/contaminants/" . $item->slug;
        $output .= sprintf( "\n<li class='%s'><a href='%s'>%s</a></li>\n",
            implode(' ', $classes),
            $url,
            $item->name
        );
    }

    function start_lvl(&$output, $depth=0, $args=array()) {
        //echo 'dump: ' . print_r($this->curItem,true);
        //if ($this->curItem->name == 'Pesticides') return false;
        $output .= "\n<ul class='" . $this->curItem->slug . "''>\n";
    }

    // Displays end of a level. E.g '</ul>'
    // @see Walker::end_lvl()
    function end_lvl(&$output, $depth=0, $args=array()) {
        $output .= "</ul>\n";
    }
}