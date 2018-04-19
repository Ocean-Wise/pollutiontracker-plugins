<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function site_create() {
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

    //insert
    if (isset($_POST['insert'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . "sites";

        $wpdb->insert(
                $table_name, //table
                array('name' => $name, 'site_id' => $site_id, 'longitude' => $longitude, 'latitude' => $latitude, 'has_mussels' => $has_mussels, 'has_sediment' => $has_sediment, 'updated' => $updated, 'sort' => $sort, 'sampling_date' => $sampling_date, 'enabled' => $enabled),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') //data format
        );

        PollutionTracker::updateContaminantRankings();

        $message.="Site inserted successfully";
    }
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Add New Site</h2>
        <?php if (isset($message)): ?><div class="updated"><p><?php echo $message; ?></p></div><?php endif; ?>
        <?php if (!isset($message)) { ?>
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
                        <option value="1">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <th class="ss-th-width">Has Sediment</th>
                    <td>
                      <select id="has_sediment" name="has_sediment">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
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
                        <option value="1">Yes</option>
                      </select>
                    </td>
                  </tr>
              </table>
              <input type='submit' name="insert" value='Save' class='button'>
          </form>
        <?php } else { ?>
          <a href="<?php echo admin_url('admin.php?page=contaminant_create'); ?>" class='button'>Add another</a>
        <?php } ?>
    </div>
    <?php
}
