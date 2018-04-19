<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function value_create() {
    $site_id        = $_GET['id'];
    $source_id      = $_POST['source_id'];
    $contaminant_id = $_POST['contaminant_id'];
    $value          = $_POST['not_detected'] === '1' ? '0' : $_POST['value'];
    $not_detected   = $_POST['not_detected'];

    //insert
    if (isset($_POST['insert'])) {
      global $wpdb;
      $table_name = $wpdb->prefix . "contaminant_values";

      $wpdb->insert(
          $table_name,
          array('site_id' => $site_id, 'source_id' => $source_id, 'contaminant_id' => $contaminant_id, 'value' => $value, 'not_detected' => $not_detected), //data
          array('%s', '%s', '%s', '%s', '%s')
      );

      PollutionTracker::updateContaminantRankings();

      $message.="Value inserted successfully";
    }
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Add Contaminant Value</h2>
        <?php if (isset($message)): ?><div class="updated"><p><?php echo $message; ?></p></div><?php endif; ?>
        <?php if (!isset($message)) { ?>
          <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <table class='wp-list-table widefat fixed'>
                    <tr>
                      <th>Source</th>
                      <td>
                        <select id="source_id" name="source_id">
                          <option value="1">Sediment</option>
                          <option value="2" <?php echo ($source_id == '2' ? 'selected' : '') ?>>Mussels</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Contaminant</th>
                      <td>
                        <select id="contaminant_id" name="contaminant_id">
                          <?php
                          global $wpdb;
                          $contaminant_table = $wpdb->prefix . "contaminants";

                          $rows = $wpdb->get_results("SELECT id,name from $contaminant_table");
                          foreach ($rows as $row) {

                          ?>
                            <option value="<?php echo $row->id; ?>" <?php echo ($contaminant_id === $row->id ? 'selected' : '') ?>><?php echo $row->name ?></option>
                          <?php } ?>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Value</th>
                      <td><input type="text" name="value" required value="0"/></td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Detected</th>
                      <td>
                        <select id="not_detected" name="not_detected">
                          <option value="0">True</option>
                          <option value="1" <?php echo ($not_detected === '1' ? 'selected' : '') ?>>False</option>
                        </select>
                      </td>
                    </tr>
                </table>
                <input type='submit' name="insert" value='Save' class='button'>
            </form>
          <?php } else { ?>
            <a href="<?php echo admin_url('admin.php?page=value_create&id=' . $_GET['id']); ?>" class='button'>Add another</a>
          <?php } ?>
    </div>
    <?php
}
