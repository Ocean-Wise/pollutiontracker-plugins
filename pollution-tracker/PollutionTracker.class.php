<?php

/**
 *
 */

class PollutionTracker{

    public static function init(){
        global $wpdb;

        add_shortcode('PTMap', array('PollutionTracker', 'mapShortcode'));

        add_action( 'add_meta_boxes', array('PollutionTracker','addContaminantMetaBox') );
        add_action( 'save_post', array('PollutionTracker', 'savePost' ));



        // Request http://domain.org?updateRankings to update contaminant rankings
        if (isset($_GET['updateRankings'])) {
            self::updateContaminantRankings();
        }
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

        wp_enqueue_script( 'pt-map', plugin_dir_url(__FILE__) . '/js/pt-map.js', array('jquery'), filemtime(plugin_dir_path( __FILE__ ) . '/js/pt-map.js'), false );


        wp_enqueue_script( 'leaflet-mapbox', 'http://rawgit.com/mapbox/mapbox-gl-leaflet/master/leaflet-mapbox-gl.js', array(), '20151215', false );
    }

    public static function mapShortcode($args){
        $html = '<div class="map-wrap"><div class="close-bar">Close map</div><div id="map" class="map"></div><div class="panel"></div></div>';
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
                    html: '<em>HTML will go here</em>',
                    contaminants: " . json_encode($site->contaminants) . "
                }},\n";
        }
        $script .= ']};';


        $script .= "PollutionTracker.buildMap({id:'map',geojson:geojson, style:'" . plugin_dir_url(__FILE__) . "/map-style.json" . "'});";

        $script .= "</script>";
        return $html . $script;
    }

    public static function updateContaminantRankings(){
        global $wpdb;

        error_log('Updading contaminate rankings');

        /* Kelsey says: (11/03/2017)
         * Once all individual contaminants are sorted and ranked in the database,
         * we’d need to take the average of the rankings for all of the individual ‘legacy pesticides’,
         * and the same for ‘current use pesticides’ (lists of chemicals to be provided).
         * This is because for the top 5 ranked contaminants/contaminant classes in the pop-ups,
         * we’ll show ‘legacy pesticides’ and ‘current use pesticides’ rather than the individual chemicals.
         * However, for the overall average site ranking, we will calculate the average based on
         * all of the individual chemical rankings. Hopefully that makes sense.
         */

        // Apply rankings to all contaminants
        $contaminants = $wpdb->get_results('SELECT * FROM wp_contaminants WHERE is_group IS NULL');
        $source_ids = $wpdb->get_col('SELECT DISTINCT source_id FROM wp_contaminant_values;');

        //error_log('Update rankings: ' . print_r($contaminants,true));
        foreach($source_ids as $source_id) {
            foreach ($contaminants as $contaminant) {
                //error_log('Update ' . $source_id . ':' . print_r($contaminant,true));
                $sql = $wpdb->query("SET @rank:=0;");
                $sql = $wpdb->prepare("UPDATE wp_contaminant_values SET rank=@rank:=@rank+1 WHERE contaminant_id=%d AND source_id=%d ORDER BY `value` DESC;", $contaminant->id, $source_id);
                $result = $wpdb->get_results($sql);
            }
        }

        // Apply rankings to contaminant groupings
        $sites = $wpdb->get_results('SELECT * FROM wp_sites');
        foreach($sites as $site){
            $groups = $wpdb->get_results('SELECT * FROM wp_contaminants WHERE aggregate=1');
            foreach($groups as $group){
                $arr_contaminant_ids = $wpdb->get_col("SELECT * FROM wp_contaminants WHERE parent_id=" . $group->id);

                foreach($source_ids as $source_id) {
                    $sql = "SELECT AVG(rank) as rank FROM wp_contaminant_values WHERE contaminant_id IN(" . implode(',', $arr_contaminant_ids) . ") AND site_id=" . $site->id . " AND source_id=" . $source_id . " AND value IS NOT NULL;";
                    $average_rank = $wpdb->get_col($sql);

                    $average_rank = $average_rank[0];
                    if (is_null($average_rank)) $average_rank = 'NULL';

                    // Upsert value
                    $sql = "INSERT INTO wp_contaminant_values (site_id, contaminant_id, source_id, rank) VALUES ({$site->id}, {$group->id}, {$source_id}, {$average_rank}) ON DUPLICATE KEY UPDATE rank={$average_rank};";
                    //error_log($sql);
                    $result = $wpdb->get_results($sql);
                }
            }
        }

    }


    public static function getPointersData(){
        global $wpdb;

        $data = [];
        $sites = $wpdb->get_results('SELECT * FROM wp_sites');
        $arr_contaminants = [];

        foreach ($sites as $site){
            $contaminants = $wpdb->get_results('SELECT wp_contaminants.name AS name, wp_contaminant_values.source_id AS source_id, wp_contaminant_values.value AS value, wp_contaminant_values.rank AS rank FROM wp_contaminant_values JOIN wp_contaminants ON wp_contaminant_values.contaminant_id = wp_contaminants.id WHERE wp_contaminants.parent_id NOT IN(37,39) AND wp_contaminant_values.site_id = ' . $site->id . ' ORDER BY wp_contaminant_values.rank ASC');

            foreach ($contaminants as $contaminant){
                $arr_contaminants[$contaminant->name]['name'] = $contaminant->name;
                if ($contaminant->source_id==1){
                    $arr_contaminants[$contaminant->name]['sediment']['value'] = $contaminant->value;
                    $arr_contaminants[$contaminant->name]['sediment']['rank'] = $contaminant->rank;
                }else{
                    $arr_contaminants[$contaminant->name]['muscles']['value'] = $contaminant->value;
                    $arr_contaminants[$contaminant->name]['muscles']['rank'] = $contaminant->rank;
                }
            }

            $arr_contaminants_array = [];
            foreach ($arr_contaminants as $contaminant){
                $arr_contaminants_array[] = $contaminant;
            }

            //error_log(print_r($arr_contaminants_array,true));

            $site_data = new stdClass();
            $site_data->name = $site->name;
            $site_data->longitude = $site->longitude;
            $site_data->latitude = $site->latitude;
            $site_data->contaminants = $arr_contaminants_array;
            array_push($data, $site_data);
        }

        return $data;
    }

    public static function getContaminantValues($args){
        global $wpdb;

        $sites = $wpdb->get_results('SELECT * FROM wp_sites ORDER BY latitude;');

        $sql = $wpdb->prepare("SELECT s.name, s.id, v.value, v.rank FROM wp_contaminant_values v JOIN wp_sites s ON v.site_id = s.id WHERE v.id=%d", $args['contaminant_id']);
        $result = $wpdb->get_results($sql);

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
            error_log("Set contaminant_id to {$contaminant_id} for page: {$slug}");
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
        'parent' => 'parent_id',
        'id'     => 'id'
    );

    /**
     * At the start of each element, output a <li> and <a> tag structure.
     *
     * Note: Menu objects include url and title properties, so we will use those.
     */
    function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        //print_r($item);
        $output .= sprintf( "\n<li><a href='/contaminants/%s'%s>%s</a></li>\n",
            $item->slug,
            ( $item->object_id === get_the_ID() ) ? ' class="current"' : '',
            $item->name
        );
    }

    function start_lvl(&$output, $depth=0, $args=array()) {
        $output .= "\n<ul>\n";
    }

    // Displays end of a level. E.g '</ul>'
    // @see Walker::end_lvl()
    function end_lvl(&$output, $depth=0, $args=array()) {
        $output .= "</ul>\n";
    }
}