<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function contaminant_update() {
    // Load the WordPress database global
    global $wpdb;
    $table_name = $wpdb->prefix . "contaminants";

    // Initialize variables
    $id                         = $_GET["id"];
    $name                       = $_POST['name'];
    $parent_id                  = $_POST['parent_id'] === '0' ? NULL : $_POST['parent_id'];
    $slug                       = sanitize_title_with_dashes($_POST['name']);
    $units_sediment             = $_POST['units_sediment'];
    $units_mussels              = $_POST['units_mussels'];
    $sediment_quality_guideline = $_POST['sediment_quality_guideline'] === '' ? NULL : $_POST['sediment_quality_guideline'];
    $probable_effects_level     = $_POST['probable_effects_level'] === '' ? NULL : $_POST['probable_effects_level'];
    $tissue_residue_guideline   = $_POST['tissue_residue_guideline'] === '' ? NULL : $_POST['tissue_residue_guideline'];

    // Do the update if we have received the 'update' POST request
    if (isset($_POST['update'])) {
        $wpdb->update(
                $table_name, //table
                array('name' => $name, 'parent_id' => $parent_id, 'slug' => $slug, 'units_sediment' => $units_sediment, 'units_mussels' => $units_mussels, 'sediment_quality_guideline' => $sediment_quality_guideline, 'probable_effects_level' => $probable_effects_level, 'tissue_residue_guideline' => $tissue_residue_guideline), //data
                array('ID' => $id), //where
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'), //data format
                array('%s') //where format
        );

        // Run the rank update
        PollutionTracker::updateContaminantRankings();
    }
    // Do a delete on the item if we received the 'delete' POST request
    else if (isset($_POST['delete'])) {
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %s", $id));
        // Run the rank update
        PollutionTracker::updateContaminantRankings();
    } else {
        // Select the value to update
        $contaminants = $wpdb->get_results($wpdb->prepare("SELECT id,name,parent_id,units_sediment,units_mussels,sediment_quality_guideline,probable_effects_level,tissue_residue_guideline from $table_name where id=%s", $id));
        foreach ($contaminants as $c) {
            // Set the default values for the forms with the current values in the database
            $name = $c->name;
            $parent_id = $c->parent_id;
            $units_sediment = $c->units_sediment;
            $units_mussels = $c->units_mussels;
            $sediment_quality_guideline = $c->sediment_quality_guideline;
            $probable_effects_level = $c->probable_effects_level;
            $tissue_residue_guideline = $c->tissue_residue_guideline;
        }
    }

    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Contaminant Values</h2>

        <?php if ($_POST['delete']) { ?>
            <div class="updated"><p>Contaminant deleted</p></div>
            <a href="<?php echo admin_url('admin.php?page=contaminant_list') ?>">&laquo; Back to contaminant list</a>

        <?php } else if ($_POST['update']) { ?>
            <div class="updated"><p>Contaminant updated</p></div>
            <a href="<?php echo admin_url('admin.php?page=contaminant_list') ?>">&laquo; Back to contaminant list</a>

        <?php } else { ?>
            <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="updateForm">
                <table class='wp-list-table widefat fixed'>
                    <tr>
                      <th>Name</th>
                      <td>
                        <input type="text" name="name" value="<?php echo $name; ?>"/>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Parent ID</th>
                      <td>
                        <select id="parent_id" name="parent_id">
                          <option value="0">No parent</option>
                          <option value="37">Current use pesticides</option>
                          <option value="38">Metals</option>
                          <option value="39">Legacy pesticides</option>
                          <option value="41">Pesticides</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Units Sediment</th>
                      <td>
                        <select id="units_sediment" name="units_sediment">
                          <option value="µg/kg">µg/kg</option>
                          <option value="ngTEQ<sub>fish</sub>/kg">ngTEQ<sub>fish</sub>/kg</option>
                          <option value="ngTEQ<sub>mamm</sub>/kg">ngTEQ<sub>mamm</sub>/kg</option>
                          <option value="µg TEQ/kg">µg TEQ/kg</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Units Mussels</th>
                      <td>
                        <select id="units_mussels" name="units_mussels">
                          <option value="µg/kg">µg/kg</option>
                          <option value="ngTEQ<sub>fish</sub>/kg">ngTEQ<sub>fish</sub>/kg</option>
                          <option value="ngTEQ<sub>mamm</sub>/kg">ngTEQ<sub>mamm</sub>/kg</option>
                          <option value="µg TEQ/kg">µg TEQ/kg</option>
                        </select>
                      </td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Sediment Quality Guideline</th>
                      <td><input type="text" name="sediment_quality_guideline" value="<?php echo $sediment_quality_guideline; ?>" class="ss-field-width" /></td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Probable Effects Level</th>
                      <td><input type="text" name="probable_effects_level" value="<?php echo $probable_effects_level; ?>" class="ss-field-width" /></td>
                    </tr>
                    <tr>
                      <th class="ss-th-width">Tissue Residue Guideline</th>
                      <td><input type="text" name="tissue_residue_guideline" value="<?php echo $tissue_residue_guideline; ?>" class="ss-field-width" /></td>
                    </tr>
                </table>
                <input type='submit' name="update" value='Save' class='button'> &nbsp;&nbsp;
                <input type='submit' name="delete" value='Delete' class='button' onclick="return confirm('Are you sure you want to delete this contaminant?')">
            </form>
            <br />
            <a href="<?php echo admin_url('admin.php?page=contaminant_list') ?>">&laquo; Back to contaminant list</a>
        <?php } ?>

    </div>
    <?php
}
