<?php
/*
Plugin Name:  Pollution Tracker
Description:  Custom functionality for Pollution Tracker website
Version:      20171031
Author:       smashLAB
Author URI:   http://smashlab.com
*/

require(dirname(__FILE__) . '/PollutionTracker.class.php');

add_action('init', array('PollutionTracker','init'));
add_action( 'wp_enqueue_scripts', array('PollutionTracker','enqueueScripts'));