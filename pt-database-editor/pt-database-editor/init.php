<?php
/*
Plugin Name: Pollution Tracker Data Editor
Description: A plugin to add and edit contaminants, sites, and their values for the Pollution Tracker site
Version: 1.0
Author: Ethan Dinnen
Author URI: http://edinnen.github.io
*/

//menu items
add_action('admin_menu','pt_database_editor_menu');
function pt_database_editor_menu() {

	//this is the main item for the menu
	add_menu_page('Contaminants/Sites Editor', //page title
	'Contaminants/Sites Editor', //menu title
	'manage_options', //capabilities
	'contaminant_site_editor', //menu slug
	'contaminant_site_editor' //function
	);

  //this is a subpage
  add_submenu_page('contaminant_site_editor',
  'Edit Contaminants',
  'Edit Contaminants',
  'manage_options',
  'contaminant_list',
  'contaminant_list');

	add_submenu_page('contaminant_site_editor', //parent slug
	'Add New Contaminant', //page title
	'Add New Contaminant', //menu title
	'manage_options', //capability
	'contaminant_create', //menu slug
	'contaminant_create'); //function

  add_submenu_page('contaminant_site_editor',
  'Edit Contaminant Values',
  'Edit Contaminant Values',
  'manage_options',
  'value_list',
  'value_list');

	add_submenu_page(null,
	'Add New Value',
	'Add New Value',
	'manage_options',
	'value_create',
	'value_create');

  add_submenu_page('contaminant_site_editor',
  'Edit Sites',
  'Edit Sites',
  'manage_options',
  'site_list',
  'site_list');

  add_submenu_page('contaminant_site_editor',
  'Add New Site',
  'Add New Site',
  'manage_options',
  'site_create',
  'site_create');

	//this submenu is HIDDEN, however, we need to add it anyways
	add_submenu_page(null, //parent slug
	'Update Contaminant', //page title
	'Update Contaminant', //menu title
	'manage_options', //capability
	'contaminant_update', //menu slug
	'contaminant_update'); //function

  add_submenu_page(null,
  'Update Site',
  'Update Site',
  'manage_options',
  'site_update',
  'site_update');

  add_submenu_page(null,
  'Update Values',
  'Update Values',
  'manage_options',
  'value_update',
  'value_update');

}
define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'contaminant-site-editor.php');
require_once(ROOTDIR . 'contaminant-list.php');
require_once(ROOTDIR . 'contaminant-create.php');
require_once(ROOTDIR . 'contaminant-update.php');
require_once(ROOTDIR . 'site-list.php');
require_once(ROOTDIR . 'site-create.php');
require_once(ROOTDIR . 'site-update.php');
require_once(ROOTDIR . 'value-create.php');
require_once(ROOTDIR . 'value-list.php');
require_once(ROOTDIR . 'value-update.php');
