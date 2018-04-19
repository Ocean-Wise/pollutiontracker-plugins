<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function site_update() {
    global $wpdb;
    $table_name = $wpdb->prefix . "sites";
    $id = $_GET["id"];

    $name                  = $_POST['name'];
    $site_id               = $_POST['site_id'];
    $longitude             = $_POST['longitude'];
    $latitude              = $_POST['latitude'];
    $has_mussels           = $_POST['has_mussels'] === '0' ? NULL : $_POST['has_mussels'];
    $has_sediment          = $_POST['has_sediment'] === '0' ? NULL : $_POST['has_sediment'];
    $updated               = date('Y-m-d H:i:s');
    $sort                  = $_POST['sort'];
    $sampling_date         = $_POST['sampling_date'];
    $enabled               = $_POST['enabled'];
//update
    if (isset($_POST['update'])) {
        $wpdb->update(
                $table_name, //table
                array('name' => $name, 'site_id' => $site_id, 'longitude' => $longitude, 'latitude' => $latitude, 'has_mussels' => $has_mussels, 'has_sediment' => $has_sediment, 'updated' => $updated, 'sort' => $sort, 'sampling_date' => $sampling_date, 'enabled' => $enabled),
                array('ID' => $id), //where
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), //data format
                array('%s') //where format
        );

        PollutionTracker::updateContaminantRankings();
    }
//delete
    else if (isset($_POST['delete'])) {
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %s", $id));
        PollutionTracker::updateContaminantRankings();
    } else {//selecting value to update
        $sites = $wpdb->get_results($wpdb->prepare("SELECT id,site_id,name,longitude,latitude,has_mussels,has_sediment,updated,sort,sampling_date,enabled from $table_name where id=%s", $id));
        foreach ($sites as $s) {
            $name = $s->name;
            $site_id = $s->site_id;
            $longitude = $s->longitude;
            $latitude = $s->latitude;
            $has_mussels = $s->has_mussels;
            $has_sediment = $s->has_sediment;
            $sort = $s->sort;
            $sampling_date = $s->sampling_date;
            $enabled = $s->enabled;
        }
    }

    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Site Values</h2>

        <?php if ($_POST['delete']) { ?>
            <div class="updated"><p>Site deleted</p></div>
            <a href="<?php echo admin_url('admin.php?page=site_list') ?>">&laquo; Back to site list</a>

        <?php } else if ($_POST['update']) { ?>
            <div class="updated"><p>Site updated</p></div>
            <a href="<?php echo admin_url('admin.php?page=site_list') ?>">&laquo; Back to site list</a>

        <?php } else { ?>
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <table class='wp-list-table widefat fixed'>
                  <tr>
                    <th class="ss-th-width">Name (required)</th>
                    <td><input required type="text" name="name" value="<?php echo $name; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Site ID (required)</th>
                    <td><input required type="text" name="site_id" value="<?php echo $site_id; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Longitude (required)</th>
                    <td><input required type="text" name="longitude" value="<?php echo $longitude; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Latitude (required)</th>
                    <td><input required type="text" name="latitude" value="<?php echo $latitude; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Has Mussels</th>
                    <td>
                      <select id="has_mussels" name="has_mussels">
                        <option value="0">No</option>
                        <option value="1" <?php echo ($has_mussels == '1' ? 'selected' : '') ?>>Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Has Sediment</th>
                    <td>
                      <select id="has_sediment" name="has_sediment">
                        <option value="0">No</option>
                        <option value="1" <?php echo ($has_sediment === '1' ? 'selected' : '') ?>>Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Sort (required; Numerical position relative to sort values in site list; allows values like ##.##)</th>
                    <td><input required type="text" name="sort" value="<?php echo $sort; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Sampling Date (required)</th>
                    <td><input required type="date" name="sampling_date" value="<?php echo $sampling_date; ?>" class="ss-field-width" /></td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Enabled</th>
                    <td>
                      <select id="enabled" name="enabled">
                        <option value="0">No</option>
                        <option value="1" <?php echo ($enabled === '1' ? 'selected' : '') ?>>Yes</option>
                      </select>
                    </td>
                  </tr>
                </table>
                <input type='submit' name="update" value='Save' class='button'> &nbsp;&nbsp;
                <input type='submit' name="delete" value='Delete' class='button' onclick="return confirm('Are you sure you want to delete this site?')">
            </form>
        <?php } ?>

    </div>
    <?php
}
