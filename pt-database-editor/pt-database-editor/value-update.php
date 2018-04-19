<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function value_update() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contaminant_values";

    $id             = $_GET["id"];
    $site_id        = $_GET['site_id'];
    $source_id      = $_POST['source_id'];
    $contaminant_id = $_POST['contaminant_id'];
    $value          = $_POST['not_detected'] === '1' ? '0' : $_POST['value'];
    $not_detected   = $_POST['not_detected'];

//update
    if (isset($_POST['update'])) {
        $wpdb->update(
                $table_name, //table
                array('source_id' => $source_id, 'contaminant_id' => $contaminant_id, 'value' => $value, 'not_detected' => $not_detected), //data
                array('ID' => $id), //where
                array('%s', '%s', '%s', '%s'), //data format
                array('%s') //where format
        );
        PollutionTracker::updateContaminantRankings();
    }
//delete
    else if (isset($_POST['delete'])) {
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %s", $id));
        PollutionTracker::updateContaminantRankings();
    } else {//selecting value to update
        $values = $wpdb->get_results($wpdb->prepare("SELECT source_id,contaminant_id,value,rank,not_detected from $table_name where id=%s", $id));
        foreach ($values as $v) {
            $source_id = $v->source_id;
            $contaminant_id = $v->contaminant_id;
            $value = $v->value;
            $not_detected = $v->not_detected;
        }
    }
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Update Contaminant Value</h2>

        <?php if ($_POST['delete']) { ?>
            <div class="updated"><p>Contaminant deleted</p></div>
            <a href="<?php echo admin_url('admin.php?page=value_list') ?>">&laquo; Back to contaminant value list</a>

        <?php } else if ($_POST['update']) { ?>
            <div class="updated"><p>Contaminant updated</p></div>
            <a href="<?php echo admin_url('admin.php?page=value_list') ?>">&laquo; Back to contaminant value list</a>

        <?php } else { ?>
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="updateForm">
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
                      <td><input type="text" name="value" required value="<?php echo $value; ?>"/></td>
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
                <input type='submit' name="update" value='Save' class='button'> &nbsp;&nbsp;
                <input type='submit' name="delete" value='Delete' class='button' onclick="return confirm('Are you sure you want to delete this contaminant value?')">
            </form>
            <br />
            <a href="<?php echo admin_url('admin.php?page=value_list') ?>">&laquo; Back to contaminant value list</a>
        <?php } ?>

    </div>
    <?php
}
