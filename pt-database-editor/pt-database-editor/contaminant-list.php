<?php

function contaminant_list() {
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Contaminants</h2>
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="<?php echo admin_url('admin.php?page=contaminant_create'); ?>">Add New</a>
            </div>
            <br class="clear">
        </div>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . "contaminants";

        // Load the relevant data for display
        $rows = $wpdb->get_results("SELECT id,name,is_group,parent_id,aggregate,slug,units_sediment,units_mussels,sediment_quality_guideline,probable_effects_level,tissue_residue_guideline from $table_name");
        ?>
        <table class='wp-list-table widefat fixed striped posts'>
            <tr>
                <th class="manage-column ss-list-width">ID</th>
                <th class="manage-column ss-list-width">Name</th>
                <th class="manage-column ss-list-width">Is Group</th>
                <th class="manage-column ss-list-width">Parent ID</th>
                <th class="manage-column ss-list-width">Aggregate</th>
                <th class="manage-column ss-list-width">Slug</th>
                <th class="manage-column ss-list-width">Units Sediment</th>
                <th class="manage-column ss-list-width">Units Mussels</th>
                <th class="manage-column ss-list-width">Sediment Quality Guideline</th>
                <th class="manage-column ss-list-width">Probable Effects Level</th>
                <th class="manage-column ss-list-width">Tissue Residue Guideline</th>
                <th>&nbsp;</th>
            </tr>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td class="manage-column ss-list-width"><?php echo $row->id; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->name; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->is_group; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->parent_id; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->aggregate; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->slug; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->units_sediment; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->units_mussels; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->sediment_quality_guideline; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->probable_effects_level; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->tissue_residue_guideline; ?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=contaminant_update&id=' . $row->id); ?>">Update</a></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php
}
