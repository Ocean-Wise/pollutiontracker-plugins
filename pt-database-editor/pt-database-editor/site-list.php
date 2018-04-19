<?php

function site_list() {
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Sites</h2>
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="<?php echo admin_url('admin.php?page=site_create'); ?>">Add New</a>
            </div>
            <br class="clear">
        </div>
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . "sites";

        $rows = $wpdb->get_results("SELECT id,site_id,name,longitude,latitude,has_mussels,has_sediment,updated,sort,sampling_date,sediment_rank,mussels_rank,sediment_average_rank,mussels_average_rank,enabled from $table_name");
        ?>
        <table class='wp-list-table widefat fixed striped posts'>
            <tr>
                <th class="manage-column ss-list-width">ID</th>
                <th class="manage-column ss-list-width">Name</th>
                <th class="manage-column ss-list-width">Site ID</th>
                <th class="manage-column ss-list-width">Longitude</th>
                <th class="manage-column ss-list-width">Latitude</th>
                <th class="manage-column ss-list-width">Has Mussels</th>
                <th class="manage-column ss-list-width">Has Sediment</th>
                <th class="manage-column ss-list-width">Updated</th>
                <th class="manage-column ss-list-width">Sort</th>
                <th class="manage-column ss-list-width">Sampling Date</th>
                <th class="manage-column ss-list-width">Sediment Rank</th>
                <th class="manage-column ss-list-width">Mussels Rank</th>
                <th class="manage-column ss-list-width">Sediment Average Rank</th>
                <th class="manage-column ss-list-width">Mussels Average Rank</th>
                <th>&nbsp;</th>
            </tr>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td class="manage-column ss-list-width"><?php echo $row->id; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->name; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->site_id; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->longitude; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->latitude; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->has_mussels; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->has_sediment; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->updated; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->sort; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->sampling_date; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->sediment_rank; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->mussels_rank; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->sediment_average_rank; ?></td>
                    <td class="manage-column ss-list-width"><?php echo $row->mussels_average_rank; ?></td>
                    <td><a href="<?php echo admin_url('admin.php?page=site_update&id=' . $row->id); ?>">Update</a></td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?php
}
