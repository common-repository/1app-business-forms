<?php

if(!defined('WP_UNINSTALL_PLUGIN')) {
  die;
}

//disconnect user's 1app connection on plugin uninstall

$site_path = site_url();
if(isset(explode('//', $site_path)[1])) {
  $site_url = explode('//', $site_path)[1];
}
elseif(!isset(explode('//', $site_path)[1])) {
  $site_url = explode('//', $site_path)[1];
}

$responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

$bodycp = wp_remote_retrieve_body( $responsecp );


$check_res = json_decode($bodycp, true);

if($check_res['status'] == true) {
  $b_iid = $check_res['businessid'];

  $responsecp = wp_remote_get( 'https://api.1app.online/v2/disconnectplugin?path='.$site_url.'&business_id='.$b_iid );

  $bodycp = wp_remote_retrieve_body( $responsecp );

}




//delete 1app table from DB
global $wpdb;
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}oneapp_forms_payments");

//delete all 1app forms
global $post;
$allposts = get_posts( array('post_type'=>'1app_form','numberposts'=>-1) );
foreach ($allposts as $eachpost) {
  wp_delete_post( $eachpost->ID, true );
}

