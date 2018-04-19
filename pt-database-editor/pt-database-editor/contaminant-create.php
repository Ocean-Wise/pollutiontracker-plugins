<?php
require_once(ABSPATH . '/wp-content/plugins/pollution-tracker/PollutionTracker.class.php');

function contaminant_create() {

    // Initialize variables

    $name                       = $_POST['name'];
    $parent_id                  = $_POST['parent_id'] === '0' ? NULL : $_POST['parent_id'];
    $slug                       = sanitize_title_with_dashes($_POST['name']);
    $units_sediment             = $_POST['units_sediment'];
    $units_mussels              = $_POST['units_mussels'];
    $sediment_quality_guideline = $_POST['sediment_quality_guideline'] === '' ? NULL : $_POST['sediment_quality_guideline'];
    $probable_effects_level     = $_POST['probable_effects_level'] === '' ? NULL : $_POST['probable_effects_level'];
    $tissue_residue_guideline   = $_POST['tissue_residue_guideline'] === '' ? NULL : $_POST['tissue_residue_guideline'];

    // Do the insert if we have the 'insert' POST request

    if (isset($_POST['insert'])) {
      // Load the WordPress database global
      global $wpdb;
      $table_name = $wpdb->prefix . "contaminants";

      // Insert the data from the form into the database
      $wpdb->insert(
        $table_name,
        array('name' => $name, 'parent_id' => $parent_id, 'slug' => $slug, 'units_sediment' => $units_sediment, 'units_mussels' => $units_mussels, 'sediment_quality_guideline' => $sediment_quality_guideline, 'probable_effects_level' => $probable_effects_level, 'tissue_residue_guideline' => $tissue_residue_guideline),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
      );

      // Run the ranking update
      PollutionTracker::updateContaminantRankings();
      $message.="Contaminant inserted successfully";
    }
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Add New Contaminant</h2>
        <?php if (isset($message)): ?><div class="updated"><p><?php echo $message; ?></p></div><?php endif; ?>
        <?php if (!isset($message)) { ?>
          <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
              <table class='wp-list-table widefat fixed'>
                  <tr>
                    <th class="ss-th-width">Name (required)</th>
                    <td><input required type="text" name="name" value="<?php echo $name; ?>" class="ss-field-width" /></td>
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
              <input type='submit' name="insert" value='Save' class='button'>
          </form>
        <?php } else { ?>
          <a href="<?php echo admin_url('admin.php?page=contaminant_create'); ?>" class='button'>Add another</a>
        <?php } ?>
    </div>
    <?php
}
