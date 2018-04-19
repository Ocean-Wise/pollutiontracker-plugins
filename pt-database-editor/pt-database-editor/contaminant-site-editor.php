<?php

// Provide links to the necessary pages
function contaminant_site_editor() {
    ?>
    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pt-database-editor/style-admin.css" rel="stylesheet" />
    <div class="wrap">
        <h2>Contaminant & Site Editor</h2>
        <div class="tablenav top">
            <div class="alignleft actions" style="display:flex; flex-direction:row;">
              <div style="display:flex; flex-direction: column; margin-right: 10px;">
                <h3>Contaminants</h3>
                <a href="<?php echo admin_url('admin.php?page=contaminant_create'); ?>" style="margin-bottom: 5px;">Add New</a>
                <a href="<?php echo admin_url('admin.php?page=contaminant_list'); ?>">Edit Contaminants</a>
                <a href="<?php echo admin_url('admin.php?page=value_list'); ?>">Edit Contaminant Values</a>
              </div>
              <div style="display:flex; flex-direction: column; margin-left: 10px;">
                <h3>Sites</h3>
                <a href="<?php echo admin_url('admin.php?page=site_create'); ?>" style="margin-bottom: 5px;">Add New</a>
                <a href="<?php echo admin_url('admin.php?page=site_list'); ?>">Edit Sites</a>
              </div>
            </div>
            <br class="clear">
        </div>
    </div>
    <?php
}
