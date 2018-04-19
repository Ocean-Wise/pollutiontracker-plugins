<?php

function value_list() {
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Contaminant Values</h2>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . "contaminant_values";
        $sites = $wpdb->get_results("SELECT id,name from wp_sites");
        if (isset($_GET['site'])) {
          $rows = $wpdb->get_results("SELECT id,site_id,source_id,contaminant_id,value,rank,not_detected from $table_name where site_id=" . $_GET['site'] );
        } else {
          $rows = [];
        }
        ?>
        <h3>Please select a site</h3>
        <select name="siteSelector" id="siteSelector" onchange="selectSite()" style="margin-bottom: 15px;">
          <?php if (!isset($_GET['contaminant'])) { ?>
            <option value="0">Select a site</option>
          <?php } ?>
          <?php foreach ($sites as $site) {
            if ($_GET['site'] == $site->id) {
          ?>
            <option value="<?php echo $site->id; ?>" selected><?php echo $site->name; ?></option>
          <?php } else { ?>
            <option value="<?php echo $site->id; ?>"><?php echo $site->name; ?></option>
          <?php }
          }
          ?>
        </select>
        <?php if (isset($_GET['site']) && $_GET['site'] !== '0') { ?>
          <br /><a href="<?php echo admin_url('admin.php?page=value_create&id=' . $_GET['site']); ?>">Add New Values</a>
          <table class='wp-list-table widefat fixed striped posts' style="margin-top: 15px;">
              <tr>
                  <th class="manage-column ss-list-width">ID</th>
                  <th class="manage-column ss-list-width">Site ID</th>
                  <th class="manage-column ss-list-width">Source Id</th>
                  <th class="manage-column ss-list-width">Contaminant Name</th>
                  <th class="manage-column ss-list-width">Value</th>
                  <th class="manage-column ss-list-width">Rank</th>
                  <th class="manage-column ss-list-width">Detected</th>

                  <th>&nbsp;</th>
              </tr>
              <?php foreach ($rows as $row) {
                // Format special cases to be human readable
                if ($row->value === NULL) {
                  $row->value = 'Not Analyzed';
                }
              ?>
                  <tr>
                      <td class="manage-column ss-list-width"><?php echo $row->id; ?></td>
                      <td class="manage-column ss-list-width"><?php echo $row->site_id; ?></td>
                      <td class="manage-column ss-list-width"><?php echo ($row->source_id == '1') ? 'Sediment' : 'Mussels'; ?></td>
                      <td class="manage-column ss-list-width"><?php echo $wpdb->get_results("SELECT name from wp_contaminants where id=$row->contaminant_id")[0]->name; ?></td> <?php // Gets the first value in the array ?>
                      <td class="manage-column ss-list-width"><?php echo $row->value; ?></td>
                      <td class="manage-column ss-list-width"><?php echo $row->rank; ?></td>
                      <td class="manage-column ss-list-width"><?php echo boolval($row->not_detected) ? 'False' : 'True' ?></td>
                      <td><a href="<?php echo admin_url('admin.php?page=value_update&id=' . $row->id . '&site_id=' . $site->id); ?>">Update</a></td>
                  </tr>
              <?php } ?>
          </table>
        <?php } ?>
    </div>
    <script>
      function selectSite() {
        const siteID = document.getElementById('siteSelector').value;
        window.location = window.location.href + '&site=' + siteID;
      }
    </script>
    <?php
}
