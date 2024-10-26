<?php
/**
* Plugin Name: 1app Business Forms
* Plugin URI: https://business.1app.online
* Description: 1app Business Forms plugin helps you create payment forms to bill your clients for goods and services using 1app as your gateway.
* Author: 1app Technologies, Inc
* Author URI: https://1app.online
* Version: 1.0.0
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


if(!defined('ABSPATH')) {
  die;
}

define('ONE1APP_1APPPF_1APP_TABLE', 'oneapp_forms_payments');
function one1app_connect1app() {
  //plugin activated

  //flush_rewrite_rules();

  global $wpdb;
  $version = get_option('one1app_oneapp_db_version', '1.0');
  $table_name = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
    id int(11) NOT NULL AUTO_INCREMENT,
    post_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    fullname varchar(255) DEFAULT '' NOT NULL,
    email varchar(255) DEFAULT '' NOT NULL,
    phone varchar(255) DEFAULT '' NOT NULL,
    metadata text,
    paid int(1) NOT NULL DEFAULT '0',
    plan varchar(255) DEFAULT '' NOT NULL,
    txn_code varchar(255) DEFAULT '' NOT NULL,
    txn_code_2 varchar(255) DEFAULT '' NOT NULL,
    amount varchar(255) DEFAULT '' NOT NULL,
    ip varchar(255) NOT NULL, 
    deleted_at varchar(255) DEFAULT '' NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at timestamp,
    modified timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
     UNIQUE KEY id (id),PRIMARY KEY  (id)
  ) $charset_collate;";

  include_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);


}

function one1app_disconnect1app() {

}
register_activation_hook( __FILE__, 'one1app_connect1app' );
register_deactivation_hook( __FILE__, 'one1app_disconnect1app' );




$plugin_name = plugin_basename(__FILE__);
add_filter( 'plugin_action_links_'.$plugin_name, 'one1app_mainSettingsPage');

function one1app_mainSettingsPage($links) {
  $setting_link = "<a href='".site_url()."/wp-admin/edit.php?post_type=1app_form&page=1app-page.php'>Settings</a>";
  array_push($links, $setting_link);
  return $links;
}



function one1app_oneapp_add_scripts_styles() {
  wp_enqueue_style( 'one1app-oneapp-pay-styles', plugins_url( 'css/style.css', __FILE__ ), '', rand() );
  
  wp_enqueue_script('onee1app_oneapp_jscripts', plugins_url( 'js/script.js', __FILE__ ), array(), rand(), true);
  
}
add_action( 'admin_enqueue_scripts', 'one1app_oneapp_add_scripts_styles' );



function one1app_oneapp_publicc_add_scripts_styles() {
  wp_enqueue_style( 'one1app-oneapp-publicc-pay-styles', plugins_url( 'css/style.css', __FILE__ ), '', rand() );
  
  wp_enqueue_script('onee1app_oneapp_publicc_jscripts', plugins_url( 'js/script.js', __FILE__ ), array(), rand(), true);
  
}
add_action( 'wp_enqueue_scripts', 'one1app_oneapp_publicc_add_scripts_styles' );


function one1app_settingsPage()
{
  echo "<h1>".esc_html('Settings')."</h1>
  <p>".esc_html('Connect to your 1app Business Account.')."</p>
  <p>".esc_html("Get your Secret Key")." <a href='".esc_url('https://business.1app.online/app/dev')."' target='".esc_attr('_blank')."'>".esc_html("here")."</a></p>";
  

  if(isset($_POST['connect']) && !empty($_POST['secret']) && !empty($_POST['site_url'])) {
    $secret = sanitize_text_field($_POST['secret']);

    $site_path = site_url();
    if(isset(explode('//', $site_path)[1])) {
      $site_url = explode('//', $site_path)[1];
    }
    elseif(!isset(explode('//', $site_path)[1])) {
      $site_url = site_url();
    }

    $args = array(
      'headers' => array(
        'Authorization' => 'Bearer ' .$secret 
      )
    );
    $response = wp_remote_get( 'https://api.1app.online/v2/verifyapikey', $args );

    $body = wp_remote_retrieve_body( $response );


    $newRes = json_decode($body, true);

    if($newRes['status'] == true) {
      $business_id = $newRes['business_id'];
      $basecode = '1app'.uniqid();

      $body = array(
        'secretkey'    => $secret,
        'basecode'   => $basecode,
        'path' => $site_url,
        'business_id' => $business_id,
      );

      $args = array(
        'body'        => $body,
        'timeout'     => '5',
        'redirection' => '5',
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array(),
        'cookies'     => array(),
      );

      $resp = wp_remote_post( 'https://api.1app.online/v2/connectplugin', $args );
      $bodyresp = wp_remote_retrieve_body( $response );

      $newResp = json_decode($bodyresp, true);

      if($newResp['status'] == true) {
        echo '<div class="'.esc_attr('notice notice-success is-dismissible').'">
          <p>'.esc_html('Account connected successfully!').'</p>
        </div>';
      }
      else {
        echo '<div class="'.esc_attr('notice notice-error is-dismissible').'">
          <p>'.esc_html('Could not connect! Try again.').'</p>
        </div>';
      }

    }
    else {
      echo '<div class="'.esc_attr('notice notice-error is-dismissible').'">
        <p>'.esc_html('Invalid secret key!').'</p>
      </div>';
    }

  

  }
  elseif(isset($_POST['disconnect']) && isset($_POST['path']) && !empty($_POST['path'])) {
    $path = sanitize_text_field($_POST['path']);

    $site_path = site_url();
    if(isset(explode('//', $site_path)[1])) {
      $site_url = explode('//', $site_path)[1];
    }
    elseif(!isset(explode('//', $site_path)[1])) {
      $site_url = site_url();
    }

    $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

    $bodycp = wp_remote_retrieve_body( $responsecp );


    $check_res = json_decode($bodycp, true);

    if($check_res['status'] == true) {
      $b_iid = $check_res['businessid'];

      $responsedp = wp_remote_get( 'https://api.1app.online/v2/disconnectplugin?path='.$site_url.'&business_id='.$b_iid );

      $bodydp = wp_remote_retrieve_body( $responsedp );

      
      $Resp = json_decode($bodydp, true);

      if($Resp['status'] == true) {
        echo '<div class="'.esc_attr('notice notice-success is-dismissible').'">
          <p>'.esc_html('Account successfully disconnected!').'</p>
        </div>';
      }
      else {
        echo '<div class="'.esc_attr('notice notice-error is-dismissible').'">
          <p>'.esc_html('Account could not be disconnected!').'</p>
        </div>';
      }

    }


    
  }



  $site_path = site_url();
  if(isset(explode('//', $site_path)[1])) {
    $site_url = explode('//', $site_path)[1];
  }
  elseif(!isset(explode('//', $site_path)[1])) {
    $site_url = site_url();
  }

  $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

  $bodycp = wp_remote_retrieve_body( $responsecp );

  
  $newRes = json_decode($bodycp, true);
  if($newRes['status'] == false) {
    echo "
    <div class='".esc_attr('connect-form')."'>
      <form action='' method='".esc_attr('post')."'>
        <label>".esc_html('1app Secret Key')."</label>
        <input type='".esc_attr('hidden')."' value='".esc_url($site_url)."' name='".esc_attr('site_url')."'>
        <input class='".esc_attr('connect-input')."' type='".esc_attr('text')."' name='".esc_attr('secret')."' placeholder='".esc_attr('Enter Secret Key')."' required>
        <input type='".esc_attr('submit')."' class='".esc_attr('connect-submit')."' name='".esc_attr('connect')."' value='".esc_attr('Connect')."'>
      </form>
    </div>";
  }
  else {
    $skey = $newRes['secretkey'];
    
    echo "
    <div class='".esc_attr('connect-form')."'>
      <form action='' method='".esc_attr('post')."'>
        <label>".esc_html('Your 1app Secret Key')."</label>
        <input class='".esc_attr('connect-input')."' type='".esc_attr('password')."' name='".esc_attr('secret')."' value='".$skey."' placeholder='".esc_attr('Your Secret Key')."' required readonly='".esc_attr('readonly')."'>
        <input type='".esc_attr('hidden')."' value='".site_url()."' name='".esc_attr('path')."'>
        <input type='".esc_attr('submit')."' class='".esc_attr('connect-submit')."' name='".esc_attr('disconnect')."' value='".esc_attr('Disconnect')."'>
      </form>
    </div>";
  }

}


function one1app_overviewPage() {
  echo '<h1>'.esc_html('Overview').'</h1>';

  $site_path = site_url();
  if(isset(explode('//', $site_path)[1])) {
    $site_url = explode('//', $site_path)[1];
  }
  elseif(!isset(explode('//', $site_path)[1])) {
    $site_url = site_url();
  }

  $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

  $bodycp = wp_remote_retrieve_body( $responsecp );


  $check_res = json_decode($bodycp, true);

  if($check_res['status'] == true) {
    $w_b_id = $check_res['businessid'];
    $w_skey = $check_res['secretkey'];

    $body = array(
      'business_id'    => $w_b_id,
      'secretkey'   => $w_skey,
    );

    $args = array(
      'body'        => $body,
      'timeout'     => '5',
      'redirection' => '5',
      'httpversion' => '1.0',
      'blocking'    => true,
      'headers'     => array(),
      'cookies'     => array(),
    );

    $respbi = wp_remote_post( 'https://api.1app.online/v2/businessinfo', $args );
    $bodybi = wp_remote_retrieve_body( $respbi );
    

    $n_res = json_decode($bodybi, true);

    if($n_res['status'] == true) {
      $bus_logo = $n_res['bizlogo'];
      $bus_name = $n_res['business_name'];
      $bus_avail_bal = $n_res['available_bal'];
      $bus_ledg_bal = $n_res['ledger_bal'];


      echo '<div class="wrap">';
        if(!empty($bus_logo)) {
          echo '<img src="'.esc_url($bus_logo).'" style="'.esc_attr('width:100px;').'">';
        }
        echo '<h4>'.esc_html('Business Name:').'<br> <b style="'.esc_attr('font-weight:bold;font-size:18px;').'">'.esc_html($bus_name).'</b></h4>
        <h4>'.esc_html('Available Balance:').'<br> <b style="'.esc_attr('font-weight:bold;font-size:18px;').'">'.esc_html('NGN'.number_format($bus_avail_bal)).'</b><br>'. esc_html('Money here is available for transfers and spend.').'</h4>
        <h4>'.esc_html('Ledger Balance:').'<br> <b style="'.esc_attr('font-weight:bold;font-size:18px;').'">'.esc_html('NGN'.number_format($bus_ledg_bal)).'</b><br>'. esc_html('Your ledger balance is how much you have made.').'</h4><br>
      </div>';
    }



  }
  else {
    echo '<div class="'.esc_attr('notice notice-info').'"><p>'.esc_html('Kindly go to').' <a href="'.esc_url('edit.php?post_type=1app_form&page=1app-page.php').'">'.esc_html('settings').'</a> '.esc_html('to connect to your 1app business account or create a 1app business account').' <a href="'.esc_url('https://business.1app.online/register-business').'">'.esc_html('here').'</a></p></div>';
  }

  echo '<h1 style="'.esc_attr('text-align:center;').'">'.esc_html('How To Create a Payment Form').'</h1><br>
  <iframe style="'.esc_attr('display:block;margin:auto;width:80%;height:320px;').'" src="'.esc_url('https://www.youtube.com/embed/aJ0iujPdemU?rel=0').'" title="'.esc_attr('1app Plugin Overview').'" frameborder="'.esc_attr('0').'" allow="'.esc_attr('accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture').'" allowfullscreen></iframe>';

}


function one1app_transactnPage() {
  echo '<h1>'.esc_html('Transactions').'</h1>';

  $trxlistsTable = new one1app_1apppf_1app_Transactions_List_Table();
  $data = $trxlistsTable->prepare_items(); ?>

  <div class="wrap">
    <div id="icon-users" class="icon32"></div>
    <?php $trxlistsTable->display(); ?>
  </div> <?php

}


/////////

add_action('admin_menu', 'one1app_1apppf_1app_add_settings_page');
add_action('admin_menu', 'one1app_1apppf_1app_add_overview_page');
add_action('admin_menu', 'one1app_1apppf_1app_add_trx_page');
function one1app_1apppf_1app_add_settings_page() {
  add_submenu_page('edit.php?post_type=1app_form', 'Settings', 'Settings', 'edit_posts', basename(__FILE__), 'one1app_settingsPage');
}
function one1app_1apppf_1app_add_overview_page() {
  add_submenu_page('edit.php?post_type=1app_form', 'Overview', 'Overview', 'edit_posts', 'overview', 'one1app_overviewPage', 0);
}
function one1app_1apppf_1app_add_trx_page() {
  add_submenu_page('edit.php?post_type=1app_form', 'Transactions', 'Transactions', 'edit_posts', 'transactions', 'one1app_transactnPage', 3);
}



add_action('init', 'register_one1app_1apppf_1app');
function register_one1app_1apppf_1app()
{
  $labels = array(
    'name' => _x('1app Business Forms', '1app_form'),
    'singular_name' => _x('1app Form', '1app_form'),
    'add_new' => _x('Add New', '1app_form'),
    'add_new_item' => _x('Add 1app Form', '1app_form'),
    'edit_item' => _x('Edit 1app Form', '1app_form'),
    'new_item' => _x('1app Form', '1app_form'),
    'view_item' => _x('View 1app Form', '1app_form'),
    'all_items' => _x('All Forms', '1app_form'),
    'search_items' => _x('Search 1app Forms', '1app_form'),
    'not_found' => _x('No 1app Forms found', '1app_form'),
    'not_found_in_trash' => _x('No 1app Form found in Trash', '1app_form'),
    'parent_item_colon' => _x('Parent 1app Form:', '1app_form'),
    'menu_name' => _x('1app Business', '1app_form'),
  );

  $args = array(
    'labels' => $labels,
    'hierarchical' => true,
    'description' => '1app Business Forms filterable by genre',
    'supports' => array('title'),
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_position' => 5,
    'menu_icon' => plugins_url('1app.png', __FILE__),
    'show_in_nav_menus' => true,
    'publicly_queryable' => true,
    'exclude_from_search' => false,
    'has_archive' => false,
    'query_var' => true,
    'can_export' => true,
    'rewrite' => false,
    'comments' => false,
    'capability_type' => 'post'
  );
  register_post_type('1app_form', $args);
}

function one1app_1apppf_1app_txncheck($name, $txncharge)
{
  if ($name == $txncharge) {
    $result = "selected";
  } else {
    $result = "";
  }
  return $result;
}


add_filter('user_can_richedit', 'one1app_1apppf_1app_disable_wyswyg');

function one1app_1apppf_1app_add_view_payments($actions, $post)
{
  global $post;

  if (get_post_type() === '1app_form') {
    unset($actions['view']);
    unset($actions['quick edit']);
    $url = add_query_arg(
      array(
        'post_id' => $post->ID,
        'action' => 'submissions',
      )
    );
    $actions['export'] = '<a href="' . admin_url('admin.php?page=submissions&form=' . $post->ID) . '" >View Payments</a>';
  }
  return $actions;
}
add_filter('page_row_actions', 'one1app_1apppf_1app_add_view_payments', 10, 2);


function one1app_1apppf_1app_remove_fullscreen($qtInit)
{
  $qtInit['buttons'] = 'fullscreen';
  return $qtInit;
}
function one1app_1apppf_1app_disable_wyswyg($default)
{
  global $post_type, $_wp_theme_features;


  if ($post_type == '1app_form') {
    //echo "<style>#edit-slug-box,#message p > a{display:none;}</style>";
    add_action("admin_print_footer_scripts", "one1app_1apppf_1app_shortcode_button_script");
    add_action('wp_dashboard_setup', 'one1app_1apppf_1app_remove_dashboard_widgets');
    remove_meta_box('postimagediv', 'post', 'side');
    add_filter('quicktags_settings', 'one1app_1apppf_1app_remove_fullscreen');
  }

  return $default;
}

function one1app_1apppf_1app_shortcode_button_script() {
  
}

function one1app_1apppf_1app_remove_dashboard_widgets()
{
  remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
  remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
  remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
  remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
  remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
  remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
  remove_meta_box('dashboard_primary', 'dashboard', 'side');
  remove_meta_box('dashboard_secondary', 'dashboard', 'side');
}



add_filter('manage_edit-1app_form_columns', 'one1app_1apppf_1app_edit_dashboard_header_columns');

function one1app_1apppf_1app_edit_dashboard_header_columns($columns)
{
  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => __('Name'),
    'shortcode' => __('Shortcode'),
    'payments' => __('Payments'),
    'date' => __('Date')
  );

  return $columns;
}


add_action('manage_1app_form_posts_custom_column', 'one1app_1apppf_1app_dashboard_table_data', 10, 2);

function one1app_1apppf_1app_dashboard_table_data($column, $post_id)
{
  global $post, $wpdb;

  if($column == 'shortcode') {
    echo '<span>
    <input type="'.esc_attr('text').'" class="'.esc_attr('large-text code').'" value="'.esc_attr('[pf-1app id=&quot;' . $post_id . '&quot;]').'" readonly="'.esc_attr('readonly').'" onfocus="'.esc_attr('this.select();').'"></span>';
  }
  if($column == 'payments') {
    $table = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;
    $count_query = 'select count(*) from ' . $table . ' WHERE post_id = "' . $post_id . '" AND paid = "1"';
    $num = $wpdb->get_var($count_query);

    echo '<u><a href="'.esc_url(admin_url('admin.php?page=submissions&form=' . $post_id) ) . '">'.esc_html('View (' . $num . ')').'</a></u>';
  }

}



function one1app_1apppf_1app_editor_shortcode_details($post)
{
?>
  <p class="description">
    <label for="wpcf7-shortcode">Copy this shortcode and paste it into your post or page:</label>
    <span class="shortcode wp-ui-highlight">
    <input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pf-1app id=&quot;<?php echo $post->ID; ?>&quot;]"></span>
  </p>

<?php
}


add_action('admin_menu', 'one1app_1apppf_1app_register_newpage');
function one1app_1apppf_1app_register_newpage()
{
  add_menu_page('1app', '1app', 'administrator', 'submissions', 'one1app_1apppf_1app_payment_submissions');
  remove_menu_page('submissions');
}

function one1app_1apppf_1app_payment_submissions()
{
    $id = sanitize_text_field($_GET['form']);
    $obj = get_post($id);
    if ($obj->post_type == '1app_form') {
        $amount = get_post_meta($id, '_amount', true);

        $exampleListTable = new one1app_1apppf_1app_Payments_List_Table();
        $data = $exampleListTable->prepare_items(); ?>
        <div id="welcome-panel" class="welcome-panel">
          <div class="welcome-panel-content">
            <h1 style="margin: 0px;"><?php echo $obj->post_title; ?> Payments </h1>
            <p class="about-description">All payments made for this form:</p>
            <br>
          </div>
        </div>
        <div class="wrap">
          <div id="icon-users" class="icon32"></div>
          <?php $exampleListTable->display(); ?>
        </div>
    <?php
    }
}




class one1app_pf_1app_Wp_List_Table
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_example_list_table_page'));
    }
    public function add_menu_example_list_table_page()
    {
        add_menu_page('', '', 'manage_options', 'example-list-table.php', array($this, 'list_table_page'));
    }
    public function list_table_page()
    {
        $exampleListTable = new Example_List_Table();
        $exampleListTable->prepare_items($data); ?>
        <div class="wrap">
            <div id="icon-users" class="icon32"></div>
            <?php $exampleListTable->display(); ?>
        </div>
<?php
    }
}





add_action('add_meta_boxes', 'one1app_1apppf_1app_editor_add_extra_metaboxes');
function one1app_1apppf_1app_editor_add_extra_metaboxes()
{
  if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    add_meta_box('one1app_1apppf_1app_editor_help_shortcode', 'Paste shortcode on preferred page', 'one1app_1apppf_1app_editor_shortcode_details', '1app_form', 'custom-metabox-holder');
  }
  add_meta_box('one1app_1apppf_1app_editor_add_form_data', 'Payment Details', 'one1app_1apppf_1app_editor_add_form_data', '1app_form', 'custom-metabox-holder');
}

function one1app_1apppf_1app_editor_help_metabox($post)
{
  do_meta_boxes(null, 'custom-metabox-holder', $post);
}
add_action('edit_form_after_title', 'one1app_1apppf_1app_editor_help_metabox');

function one1app_1apppf_1app_editor_add_form_data()
{
  global $post;

  // Noncename needed to verify where the data originated
  echo '<input type="'.esc_attr('hidden').'" name="'.esc_attr('eventmeta_noncename').'" id="'.esc_attr('eventmeta_noncename').'" value="'.esc_attr(wp_create_nonce(plugin_basename(__FILE__)) ).'" />';

  // Get the location data if its already been entered
  $amount = get_post_meta($post->ID, '_amount', true);
  $desc = get_post_meta($post->ID, '_desc', true);
  $b_id = get_post_meta($post->ID, '_b_id', true);
  $currency = get_post_meta($post->ID, '_currency', true);
  $hidetitle = get_post_meta($post->ID, '_hidetitle', true);

  if ($amount == "") {
    $amount = 0;
  }
  if ($currency == "") {
    $currency = 'NGN';
  }
  if ($hidetitle == "") {
    $hidetitle = 0;
  }
  // Echo out the field


  if ($hidetitle == 1) {
    echo '<label><input name="'.esc_attr('_hidetitle').'" type="'.esc_attr('checkbox').'" value="'.esc_attr('1').'" checked>'.esc_html('Hide the form title').' </label>';
  } else {
    echo '<label><input name="'.esc_attr('_hidetitle').'" type="'.esc_attr('checkbox').'" value="'.esc_attr('0').'" > '.esc_html('Hide the form title').' </label>';
  }
  echo "<br>";
  '<input type"'.esc_attr('hidden').'" name="'.esc_attr('_currency').'" value="'.esc_attr('NGN" ' . one1app_1apppf_1app_txncheck('NGN', $currency) ). '>';
  echo '<p>'.esc_html('Amount (Leave as 0 if the customer is to input this):').'</p>';
  echo '<input type="number" name="'.esc_attr('_amount').'" value="' .esc_attr( $amount ). '" class="'.esc_attr('widefat pf-number').'" />';
  echo "<br><br>";
  echo '<textarea rows="'.esc_attr('3').'"  name="'.esc_attr('_desc').'" class="'.esc_attr('widefat').'" placeholder="'.esc_attr('Payment description').'" >'.esc_textarea($desc).'</textarea>';
}


function one1app_1apppf_1app_save_data($post_id, $post)
{
  if (!wp_verify_nonce(@$_POST['eventmeta_noncename'], plugin_basename(__FILE__))) {
    return $post->ID;
  }

  // Is the user allowed to edit the post or page?
  if (!current_user_can('edit_post', $post->ID)) {
    return $post->ID;
  }

  
  if(isset($_POST['_amount']) && $_POST['_amount'] != '') {
    $form_meta['_amount'] = sanitize_text_field($_POST['_amount']);
  }
  else {
    $form_meta['_amount'] = '0';
  }

  if(isset($_POST['_hidetitle'])) {
    $form_meta['_hidetitle'] = '1';
  }
  else {
    $form_meta['_hidetitle'] = '0';
  }

  if(isset($_POST['_currency'])) {
    $form_meta['_currency'] = sanitize_text_field($_POST['_currency']);
  }
  else {
    $form_meta['_currency'] = 'NGN';
  }
  $form_meta['_desc'] = sanitize_text_field($_POST['_desc']);
  

  foreach ($form_meta as $key => $value) { // Cycle through the $form_meta array!
    if ($post->post_type == 'revision') {
      return; // Don't store custom data twice
    }
    $value = implode(',', (array) $value); // If $value is an array, make it a CSV (unlikely)
    if (get_post_meta($post->ID, $key, false)) { // If the custom field already has a value
      update_post_meta($post->ID, $key, $value);
    } else { // If the custom field doesn't have a value
      add_post_meta($post->ID, $key, $value);
    }
    if (!$value) {
      delete_post_meta($post->ID, $key); // Delete if blank
    }
  }
}
add_action('save_post', 'one1app_1apppf_1app_save_data', 1, 2);









function one1app_1apppf_1app_form_shortcode($atts)
{
  ob_start();

  // global $current_user;
  // $user_id = $current_user->ID;
  // $email = $current_user->user_email;
  // $phone = $current_user->user_phone;
  // $fname = $current_user->user_firstname;
  // $lname = $current_user->user_lastname;
  // if ($fname == '' && $lname == '') {
  //   $fullname = '';
  // } else {
  //   $fullname = $fname . ' ' . $lname;
  // }
  extract(
    shortcode_atts(
      array(
        'id' => 0,
      ),
      $atts
    )
  );
  
  //fetch secret key by first checking connection and then get the key...
  $site_path = site_url();
  if(isset(explode('//', $site_path)[1])) {
    $site_url = explode('//', $site_path)[1];
  }
  elseif(!isset(explode('//', $site_path)[1])) {
    $site_url = site_url();
  }

  $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

  $bodycp = wp_remote_retrieve_body( $responsecp );

  $res_check = json_decode($bodycp, true);
  $u_skey = $res_check['secretkey'];

  if (!$u_skey) {
    $settingslink = get_admin_url() . 'edit.php?post_type=1app_form&page=1app-page.php';
    static $firstTime = true;
    if ($firstTime) {
      echo "<div style='".esc_attr('color:#fff;background:red;padding:10px;box-shadow:3px 3px 3px #000;font-size:17px;')."'>".esc_html('You must connect to your 1App Business account first on')." <a style='".esc_attr('background:#fff;color:red;padding:3px;border-radius:2px;')."' href='".esc_url($settingslink)."'>".esc_html('settings')."</a></div>";
      $firstTime = false;
    }
    
  }
  elseif ($id != 0) {
    $obj = get_post($id);
    if (isset($obj->post_type) && $obj->post_type == '1app_form') {
      $amount = get_post_meta($id, '_amount', true);
      $desc = get_post_meta($id, '_desc', true);
      $currency = get_post_meta($id, '_currency', true);
      $hidetitle = get_post_meta($id, '_hidetitle', true);
      
      $showbtn = true;
      

      echo '<div id="connect-form">';
      if ($hidetitle != 1) {
        echo "<h1 id='".esc_attr('pf-form' . $id . ">" . $obj->post_title). "'</h1>";
      }

      if($desc != '') {
        echo "<p>".esc_html($desc)."</p>";
      }

      if(isset($_GET['err']) && !empty($_GET['err']) && isset($_GET['id']) && !empty($_GET['id']) ) {
        if($id == $_GET['id']) {
          global $wp;
          $c_pg = home_url( $wp->request );
          $n_pg = explode('?' ,$c_pg)[0];
          echo '<div style="'.esc_attr('color:#fff;background:red;padding:10px;box-shadow:3px 3px 3px #000;font-size:17px;').'">'.esc_html("Try again. Make sure you fill all details correctly.").' <a style="'.esc_attr('background:#fff;color:red;padding:3px;border-radius:2px;float:right;font-size:17px;').'" href="'.esc_url($n_pg).'">'.esc_html("Refresh").'</a></div>';
        }
      }

      if(isset($_GET['initiatepay']) && $_GET['initiatepay'] == 'false' && isset($_GET['id']) && !empty($_GET['id'])) {
        if($id == $_GET['id']) {
          global $wp;
          $c_pg = home_url( $wp->request );
          $n_pg = explode('?' ,$c_pg)[0];
          echo '<div style="'.esc_attr("color:#fff;background:red;padding:10px;box-shadow:3px 3px 3px #000;font-size:17px;").'">'.esc_html("Your payment could not be initiated! Fill the form correctly and try again.").' <a style="'.esc_attr("background:#fff;color:red;padding:3px;border-radius:2px;float:right;font-size:17px;").'" href="'.esc_url($n_pg).'">'.esc_html("Refresh").'</a></div>';

          // echo "<div style='".esc_attr('color:#fff;background:red;padding:10px;box-shadow:3px 3px 3px #000;')."'>".esc_html('You must connect to your 1App Business account first on')." <a style='".esc_attr('background:#fff;color:red;padding:3px;border-radius:2px;')."' href='".esc_url($settingslink)."'>".esc_html('settings')."</a></div>";
        }
      }
      
      if(isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['txref']) && !empty($_GET['txref']) && isset($_GET['token']) && !empty($_GET['token'])) {
        //verify payment

        //get business ID first
        $site_path = site_url();
        if(isset(explode('//', $site_path)[1])) {
          $site_url = explode('//', $site_path)[1];
        }
        elseif(!isset(explode('//', $site_path)[1])) {
          $site_url = site_url();
        }

        $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

        $bodycp = wp_remote_retrieve_body( $responsecp );

        $res_check = json_decode($bodycp, true);
        if($res_check['status'] == true) {
          $c_bid = $res_check['businessid'];

          //payment verification starts

          $responsevp = wp_remote_get( 'https://api.1app.online/v2/verifypay?business_id='.$c_bid.'&txref='.sanitize_text_field($_GET['txref']).'&access_token='.sanitize_text_field($_GET['token']) );

          $bodyvp = wp_remote_retrieve_body( $responsevp );


          $ver_res = json_decode($bodyvp, true);
          if($ver_res['status'] == true) {
            $f_txref = $ver_res['data'][0]['customer_reference'];
            //payment was made
            //update DB and mark record as paid
            global $wpdb;
            $table = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;
            $record = $wpdb->get_results("SELECT * FROM $table WHERE (txn_code = '" . $f_txref . "')");
            if (array_key_exists("0", $record)) {
              $paid_at = date('Y-m-d h:i:s');
              $update_pay_rec = $wpdb->update($table, array('paid' => 1, 'paid_at' => $paid_at), array('txn_code' => $f_txref));
            }
            if(isset($update_pay_rec)) {
              if($id == $_GET['id']) {
                global $wp;
                $c_pg = home_url( $wp->request );
                $n_pg = explode('?' ,$c_pg)[0];
                echo '<div style="'.esc_attr('color:#fff;background:green;padding:10px;box-shadow:3px 3px 3px #000;font-size:17px;').'">'.esc_html($ver_res["message"]).' <a style="'.esc_attr('background:#fff;color:green;padding:3px;border-radius:2px;float:right;font-size:17px;').'" href="'.esc_url($n_pg).'">'.esc_html("Refresh").'</a></div>';
              }
            }
          }
          else {
            //payment not made or unable to be verified
            if($id == $_GET['id']) {
              global $wp;
              $c_pg = home_url( $wp->request );
              $n_pg = explode('?' ,$c_pg)[0];
              echo '<div style="'.esc_attr('color:#fff;background:red;padding:10px;box-shadow:3px 3px 3px #000;font-size:17px;').'"> '.esc_html($ver_res["message"]).' <a style="'.esc_attr('background:#fff;color:red;padding:3px;border-radius:2px;float:right;font-size:17px;').'" href="'.esc_url($n_pg).'">'.esc_html("Refresh").'</a></div>';
            }
          }

          

        }

        

      }
          
      echo '<form enctype="'.esc_attr('multipart/form-data').'" action="' .esc_url(admin_url('admin-ajax.php')) . '" url="' .esc_url( admin_url() ). '" method="'.esc_attr('POST').'">
      <div class="'.esc_attr('j-row').'">';
      echo '<input type="'.esc_attr('hidden').'" name="'.esc_attr('action').'" value="'.esc_attr('one1app_1apppf_1app_submit_action').'">';
      echo '<input type="'.esc_attr('hidden').'" name="'.esc_attr('pf-id').'" value="' .esc_attr($id) . '" />';
      global $wp;
      echo '<input type="'.esc_attr('hidden').'" name="'.esc_attr('pf-page').'" value="' .esc_attr(home_url( $wp->request ) ). '" />';
      echo '<input type="'.esc_attr('hidden').'" name="'.esc_attr('pf-currency').'" id="'.esc_attr('pf-currency').'" value="' .esc_attr($currency) . '" />';
      
      echo "
      <div class='".esc_attr('span12 unit')."' style='".esc_attr('float:left;width:49%;margin-right:1%;')."'>
       <label class='".esc_attr('label')."' style='".esc_attr('font-size:17px;')."'>".('First Name')." <span>".esc_html('*')."</span></label>
       <div class='".esc_attr('input')."'>
         <input style='".esc_attr('width:100%;')."' type='".esc_attr('text')."' name='".esc_attr('pf-fname')."' placeholder='".esc_attr('First Name')."' required>
       </div>
      </div>";
      echo '
      <div class="'.esc_attr('span12 unit').'" style="'.esc_attr('float:left;width:50%;').'">
       <label class="'.esc_attr('label').'" style="'.esc_attr('font-size:17px;').'">'.esc_html('Last Name').' <span>'.esc_html('*').'</span></label>
       <div class="'.esc_attr('input').'">
         <input style="'.esc_attr('width:100%;').'" type="text" name="'.esc_attr('pf-lname').'" placeholder="'.esc_attr('Last Name').'" required>
       </div>
      </div>';
      // echo '<div style="'.esc_attr('clear:both;').'">'.esc_html('&nbsp;').'</div>
      echo '
      <div class="'.esc_attr('span12 unit').'">
       <label class="'.esc_attr('label').'" style="'.esc_attr('font-size:17px;').'">'.esc_html('Email').' <span>'.esc_html('*').'</span></label>
       <div class="'.esc_attr('input').'">
         <input style="'.esc_attr('width:100%;').'" type="email" name="'.esc_attr('pf-pemail').'" placeholder="'.esc_attr('Email Address').'"  id="'.esc_attr('pf-email').'" required>
        </div>
      </div>';
      echo '
      <div class="'.esc_attr('span12 unit').'">
       <label class="'.esc_attr('label').'" style="'.esc_attr('font-size:17px;').'">'.esc_html('Phone').' <span>'.esc_html('*').'</span></label>
       <div class="'.esc_attr('input').'">
         <input style="'.esc_attr('width:100%;').'" oninput="'.esc_attr('javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);').'" type = "'.esc_attr('number').'" maxlength="'.esc_attr('11').'" onkeydown="'.esc_attr('javascript: return event.keyCode === 8 || event.keyCode === 46 ? true : !isNaN(Number(event.key))').'" name="'.esc_attr('pf-pphone').'" placeholder="'.esc_attr('Phone Number').'"  id="'.esc_attr('pf-phone').'" required>
        </div>
      </div>';

      echo '
      <div class="'.esc_attr('span12 unit').'">
       <label class="'.esc_attr('label').'" style="'.esc_attr('font-size:17px;').'">'.esc_html('Amount').' <span>'.esc_html('*').'</span></label>
       <div class="'.esc_attr('input').'">';
        
        if ($amount == 0) {
          echo '<input style="'.esc_attr('width:100%;').'" type = "'.esc_attr('number').'" onkeydown="'.esc_attr('javascript: return event.keyCode === 8 || event.keyCode === 46 ? true : !isNaN(Number(event.key))').'" name="'.esc_attr('pf-amount').'" class="'.esc_attr('pf-number').'" id="'.esc_attr('pf-amount').'" placeholder="'.esc_attr('Amount').'" required/>';
        }
        elseif ($amount != 0) {
          echo '<input style="'.esc_attr('width:100%;').'" type="'.esc_attr('text').'" name="'.esc_attr('pf-amount').'" value="' .esc_attr($amount) . '" id="'.esc_attr('pf-amount').'" required readonly="'.esc_attr('readonly').'">';
        }
        echo '
      </div>
     </div>';

        

      echo (do_shortcode($obj->post_content));

      echo '
      <div class="span12 unit">
        <br>
        <button type="'.esc_attr('submit').'" class="'.esc_attr('primary-btn').'" class="'.esc_attr('connect-submit').'" name="'.esc_attr('proceed_to_pay_1app').'" style="'.esc_attr('background:#E70A80;color:#fff;border-radius:5px;float:right;').'">'.esc_html('Proceed to Pay').'</button><br>';
        
        
       echo '
      </div>';

      echo '</div>
      </form>';
      echo '</div>';
      
    }
  }




  return ob_get_clean();
}
add_shortcode('pf-1app', 'one1app_1apppf_1app_form_shortcode');



function one1app_1apppf_1app_generate_code()
{
  $code = 0;
  $check = true;
  while ($check) {
    $code = one1app_1apppf_1app_generate_new_code();
    $check = one1app_1apppf_1app_check_code($code);
  }

  return $code;
}

function one1app_1apppf_1app_generate_new_code($length = 10)
{
  $characters = '06EFGHI9KL' . time() . 'MNOPJRSUVW01YZ923234' . time() . 'ABCD5678QXT';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return time() . "_" . $randomString;
}


function one1app_1apppf_1app_check_code($code)
{
  global $wpdb;
  $table = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;
  $o_exist = $wpdb->get_results("SELECT * FROM $table WHERE txn_code = '" . $code . "'");

  if (count($o_exist) > 0) {
    $result = true;
  } else {
    $result = false;
  }

  return $result;
}


function one1app_1apppf_1app_get_the_user_ip()
{
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}



add_action('wp_ajax_one1app_1apppf_1app_submit_action', 'one1app_1apppf_1app_submit_action');
add_action('wp_ajax_nopriv_one1app_1apppf_1app_submit_action', 'one1app_1apppf_1app_submit_action');
function one1app_1apppf_1app_submit_action()
{
  $err = '';

  if (trim($_POST['pf-pemail']) == '') {
    // $response['result'] = 'failed';
    // $response['message'] = 'Email is required';
    // exit(json_encode($response));
    $err .= '<li>Email is required</li>';
  }
  if (trim($_POST['pf-pphone']) == '') {
    // $response['result'] = 'failed';
    // $response['message'] = 'Email is required';
    // exit(json_encode($response));
    $err .= '<li>Phone Number is required</li>';
  }
  if(strlen(trim($_POST['pf-pphone'])) > 11 || strlen(trim($_POST['pf-pphone'])) < 11 || !is_numeric(trim($_POST['pf-pphone'])) ) {
    $err .= '<li>Phone number must be numbers and must have 11 characters.</li>';
  }
  if (trim($_POST['pf-fname']) == '' || trim($_POST['pf-lname']) == '' ) {
    $err .= '<li>First and last names are required</li>';
  }
  if(trim($_POST['pf-amount']) == 0 || trim($_POST['pf-amount']) == '') {
    $err .= '<li>Amount cannot be 0 nor empty</li>';
  }

  if($err != '') {
    wp_redirect(sanitize_text_field($_POST['pf-page']).'?err=true&id='.sanitize_text_field($_POST['pf-id']));
    // echo '<div>Found some errors:<ul style="color:red;">'.$err.'</ul></div>';
  }
  else{
    // Hookable location. Allows other plugins use a fresh submission before it is saved to the database.
    // Such a plugin only needs do
    // add_action( 'one1app_1apppf_1app_before_save', 'function_to_use_posted_values' );
    // somewhere in their code;
    do_action('one1app_1apppf_1app_before_save');

    global $wpdb;
    $code = one1app_1apppf_1app_generate_code();

    $table = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;
    $metadata = $_POST;
    $fname = sanitize_text_field($_POST['pf-fname']);
    $lname = sanitize_text_field($_POST['pf-lname']);
    $fullname = $fname.' '.$lname;
    // if(!isset(explode(' ', $fullname)[1]) || empty(explode(' ', $fullname)[1])) {
    //   $lname = '';
    //   $fname = $fullname;
    // }
    // else {
    //   $fname = explode(' ', $fullname)[0];
    //   $lname = explode(' ', $fullname)[1];
    // }
    unset($metadata['action']);
    unset($metadata['pf-id']);
    unset($metadata['pf-page']);
    unset($metadata['pf-pemail']);
    unset($metadata['pf-pphone']);
    unset($metadata['pf-amount']);

    // echo '<pre>';

    $untouchedmetadata = one1app_1apppf_1app_meta_as_custom_fields($metadata);
    $fixedmetadata = [];
    // print_r($fixedmetadata );
    $currency = get_post_meta(sanitize_text_field($_POST["pf-id"]), '_currency', true);
    $formamount = get_post_meta(sanitize_text_field($_POST["pf-id"]), '_amount', true); /// From form
    

    $amount = (int) str_replace(' ', '', sanitize_text_field($_POST["pf-amount"]));
    $originalamount = $amount;

    // if (($recur == 'no') && ($formamount != 0)) {
    //   $amount = (int) str_replace(' ', '', $formamount);
    // }
    
    $fixedmetadata[] =  array(
      'display_name' => 'Unit Price',
      'variable_name' => 'Unit_Price',
      'type' => 'text',
      'value' => $currency . number_format($amount)
    );
    //--------------------------------------

    

    $fixedmetadata = json_decode(json_encode($fixedmetadata, JSON_NUMERIC_CHECK), true);
    $fixedmetadata = array_merge($untouchedmetadata, $fixedmetadata);

    $insert =  array(
      'post_id' => sanitize_text_field($_POST["pf-id"]),
      'fullname' => sanitize_text_field($_POST["pf-fname"]).' '.sanitize_text_field($_POST["pf-lname"]),
      'email' => sanitize_text_field($_POST["pf-pemail"]),
      'phone' => sanitize_text_field($_POST["pf-pphone"]),
      'amount' => sanitize_text_field($amount),
      'ip' => one1app_1apppf_1app_get_the_user_ip(),
      'txn_code' => $code,
      //'metadata' => 'metadata'
      'metadata' => json_encode($fixedmetadata)
    );
    $exist = $wpdb->get_results(
      "SELECT * FROM $table WHERE (post_id = '" . $insert['post_id'] . "'
      AND fullname = '" .$insert['fullname'] . "'
      AND email = '" . $insert['email'] . "'
      AND phone = '" . $insert['phone'] . "'
      AND amount = '" . $insert['amount'] . "'
      AND ip = '" . $insert['ip'] . "'
      AND paid = '0'
      AND metadata = '" . $insert['metadata'] . "')"
    );
    if (count($exist) > 0) {
      // $insert['txn_code'] = $code;
      // $insert['plan'] = $exist[0]->plan;
      $wpdb->update($table, array('txn_code' => $code, 'modified' => date("Y-m-d h:i:s")), array('id' => $exist[0]->id));
    }
    else {
      $wpdb->insert(
        $table,
        $insert
      );
    }
    

    $amount = floatval($insert['amount']) * 100;
    $response = array(
      'result' => 'success',
      'code' => $insert['txn_code'],
      'email' => $insert['email'],
      'name' => $fullname,
      'total' => round($amount),
      'currency' => $currency,
      'custom_fields' => $fixedmetadata
    );

   
     
    //initiate payment
    if($response['result'] == 'success') {

      $site_path = site_url();
      if(isset(explode('//', $site_path)[1])) {
        $site_url = explode('//', $site_path)[1];
      }
      elseif(!isset(explode('//', $site_path)[1])) {
        $site_url = site_url();
      }


      

      //get business ID

      $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

      $bodycp = wp_remote_retrieve_body( $responsecp );

     

      $res_check = json_decode($bodycp, true);
      if($res_check['status'] == true) {
        $c_bid = $res_check['businessid'];

         

        $body = array(
          'reference'    => $code,
          'amount'   => $insert['amount'],
          'customer_email' => $insert['email'],
          'currency' => sanitize_text_field($_POST["pf-currency"]),
          'site_url' => $site_url,
          'business_id' => $c_bid,
          'phn' => $insert['phone'],
          'fname' => $fname,
          'lname' => $lname,
          'redirecturl' => sanitize_text_field($_POST['pf-page']).'?id='.sanitize_text_field($_POST["pf-id"]),
        );

        $args = array(
          'body'        => $body,
          'timeout'     => '5',
          'redirection' => '5',
          'httpversion' => '1.0',
          'blocking'    => true,
          'headers'     => array(),
          'cookies'     => array(),
        );

        $respip = wp_remote_post( 'https://api.1app.online/v2/initiatepay', $args );
        $bodyip = wp_remote_retrieve_body( $respip );

        $d_res = json_decode($bodyip, true);
        if($d_res['status'] == true) {
          $access_token = $d_res['access_token'];
          $ref_1app_pay = $d_res['reference'];
          header('location: '.$d_res['authorization_url']);
        }
        else {
          wp_redirect(sanitize_text_field($_POST['pf-page']).'?initiatepay=false&id='.sanitize_text_field($_POST['pf-id']));
        }

      }
      


    }
    



  }



  
}




function one1app_1apppf_1app_meta_as_custom_fields($metadata)
{
  $custom_fields = array();
  foreach ($metadata as $key => $value) {
    if (is_array($value)) {
      $value = implode(', ', $value);
    }
    if ($key == 'pf-fname') {
      $custom_fields[] =  array(
        'display_name' => 'Full Name',
        'variable_name' => 'Full_Name',
        'type' => 'text',
        'value' => $value
      );
    }
    else {
      $custom_fields[] =  array(
        'display_name' => ucwords(str_replace("_", " ", $key)),
        'variable_name' => $key,
        'type' => 'text',
        'value' => (string) $value
      );
    }
  }
  return $custom_fields;
}




// add_action('wp_ajax_kkd_1apppf_1app_confirm_payment', 'one1app_1apppf_1app_confirm_payment');
// add_action('wp_ajax_nopriv_kkd_1apppf_1app_confirm_payment', 'one1app_1apppf_1app_confirm_payment');

// function one1app_1apppf_1app_confirm_payment() {
  
// }


function format_data($data)
{
    $new = json_decode($data);
    $text = '';
    if (array_key_exists("0", $new)) {
        foreach ($new as $key => $item) {
            if ($item->type == 'text') {
                $text .= '<b>' . $item->display_name . ": </b> " . $item->value . "<br />";
            } else {
                $text .= '<b>' . $item->display_name . ": </b>  <a target='_blank' href='" . $item->value . "'>link</a><br />";
            }
        }
    } else {
        $text = '';
        if (count($new) > 0) {
            foreach ($new as $key => $item) {
                $text .= '<b>' . $key . ": </b> " . $item . "<br />";
            }
        }
    }
    //
    return $text;
}


if (!class_exists('WP_List_Table')) {
  include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class one1app_1apppf_1app_Payments_List_Table extends WP_List_Table
{
  public function prepare_items()
  {
    $post_id = sanitize_text_field($_GET['form']);
    $currency = get_post_meta($post_id, '_currency', true);

    global $wpdb;

    $table = $wpdb->prefix . ONE1APP_1APPPF_1APP_TABLE;
    $data = array();
    $alldbdata = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '" . $post_id . "' AND paid = '1')");

    foreach ($alldbdata as $key => $dbdata) {
      $newkey = $key + 1;
      if ($dbdata->txn_code_2 != "") {
        $txn_code = $dbdata->txn_code_2;
      } else {
        $txn_code = $dbdata->txn_code;
      }
      $data[] = array(
        //'id'  => $newkey,
        'email' => '<a href="mailto:' . $dbdata->email . '">' . $dbdata->email . '</a>',
        'amount' => $currency . '<b>' . number_format($dbdata->amount) . '</b>',
        'txn_code' => $txn_code,
        //'metadata' => format_data($dbdata->metadata),
        'date'  => $dbdata->created_at
      );
    }

    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    usort($data, array(&$this, 'sort_data'));
    $perPage = 20;
    $currentPage = $this->get_pagenum();
    $totalItems = count($data);
    $this->set_pagination_args(
      array(
        'total_items' => $totalItems,
        'per_page'    => $perPage
      )
    );
    $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
    $this->_column_headers = array($columns, $hidden, $sortable);
    $this->items = $data;

    $rows = count($alldbdata);
    return $rows;
  }

  public function get_columns()
  {
    $columns = array(
      //'id'  => '#',
      'email' => 'Email',
      'amount' => 'Amount',
      'txn_code' => 'Txn Code',
      //'metadata' => 'Data',
      'date'  => 'Date'
    );
    return $columns;
  }
  /**
   * Define which columns are hidden
   *
   * @return Array
   */
  public function get_hidden_columns()
  {
    return array();
  }
  public function get_sortable_columns()
  {
    return array('email' => array('email', false), 'date' => array('date', false), 'amount' => array('amount', false));
  }
  /**
   * Get the table data
   *
   * @return Array
   */
  private function table_data($data)
  {
    return $data;
  }
  /**
   * Define what data to show on each column of the table
   *
   * @param Array  $item        Data
   * @param String $column_name - Current column name
   *
   * @return Mixed
   */
  public function column_default($item, $column_name)
  {
    switch ($column_name) {
      //case 'id':
      case 'email':
      case 'amount':
      case 'txn_code':
      //case 'metadata':
      case 'date':
        return $item[$column_name];
      default:
      return print_r($item, true);
    }
  }

  /**
   * Allows you to sort the data by the variables set in the $_GET
   *
   * @return Mixed
   */
  private function sort_data($a, $b)
  {
    $orderby = 'date';
    $order = 'desc';
    if (!empty($_GET['orderby'])) {
      $orderby = sanitize_text_field($_GET['orderby']);
    }
    if (!empty($_GET['order'])) {
      $order = sanitize_text_field($_GET['order']);
    }
    $result = strcmp($a[$orderby], $b[$orderby]);
    if ($order === 'asc') {
      return $result;
    }
    return -$result;
  }
}



class one1app_1apppf_1app_Transactions_List_Table extends WP_List_Table
{
    public function prepare_items()
    {
      $site_path = site_url();
      if(isset(explode('//', $site_path)[1])) {
        $site_url = explode('//', $site_path)[1];
      }
      elseif(!isset(explode('//', $site_path)[1])) {
        $site_url = site_url();
      }

      $responsecp = wp_remote_get( 'https://api.1app.online/v2/checkplugin-connect?path='.$site_url );

      $bodycp = wp_remote_retrieve_body( $responsecp );

      

      $check_res = json_decode($bodycp, true);

      if($check_res['status'] == true) {
        $w_b_id = $check_res['businessid'];
        $w_skey = $check_res['secretkey'];

        $body = array(
          'business_id'    => $w_b_id,
          'secretkey'   => $w_skey,
        );

        $args = array(
          'body'        => $body,
          'timeout'     => '5',
          'redirection' => '5',
          'httpversion' => '1.0',
          'blocking'    => true,
          'headers'     => array(),
          'cookies'     => array(),
        );

        $resptl = wp_remote_post( 'https://api.1app.online/v2/translist', $args );
        $bodytl = wp_remote_retrieve_body( $resptl );


        $alltrxlist = json_decode($bodytl, true);

        if($alltrxlist['status'] == true) {
          $alltrxdata = $alltrxlist['data'];
          if(count($alltrxdata) > 0) {

            $idd = 1;
            foreach($alltrxdata as $dbdata) {
              $data[] = array(
                //'id'  => $idd,
                'amount' => $dbdata['currency'] . '<b>' . number_format($dbdata['amount']) . '</b>',
                'transfee' => $dbdata['currency'] . '<b>' . number_format($dbdata['transfee']) . '</b>',
                'reference' => $dbdata['reference'],
                'name' => $dbdata['customer_name'],
                'email' => $dbdata['customer_email'],
                'phone' => $dbdata['customer_phone'],
                'paid_through' => $dbdata['paid_through'],
                'time' => $dbdata['payment_time']
                //'metadata' => format_data($dbdata->metadata),
                //'date'  => $dbdata->created_at
              );
              $idd++;

            }

          }
          else {
            $data[] = array();
          }
          

        }
        else {
          $data[] = array();
        }

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        usort($data, array(&$this, 'sort_data'));
        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args(
          array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
          )
        );
        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;

        $rows = count($alltrxlist);
        return $rows;




      }
      else {
        echo '<div class="'.esc_attr('notice notice-info').'"><p>'.esc_html('Kindly go to').' <a href="'.esc_url('edit.php?post_type=1app_form&page=1app-page.php').'">'.esc_html('settings').'</a> '.esc_html('to connect to your 1app business account.').'</p></div>';
      }


       
    }

    public function get_columns()
    {
      $columns = array(
        //'id'  => '#',
        'reference' => 'Reference',
        'name' => 'Customer',
        'amount' => 'Amount',
        'email' => 'Email',
        'phone' => 'Phone',
        'transfee' => 'Fee',
        //'metadata' => 'Data',
        'paid_through' => 'Paid Through',
        'time'  => 'Date'
      );
      return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
      return array();
    }
    public function get_sortable_columns()
    {
      return array('time' => array('time', false), 'amount' => array('amount', false));
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($data)
    {
      return $data;
    }
    /**
     * Define what data to show on each column of the table
     *
     * @param Array  $item        Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
      switch ($column_name) {
        //case 'id':
        case 'email':
        case 'name':
        case 'transfee':
        case 'phone':
        case 'paid_through':
        case 'amount':
        case 'reference':
        //case 'metadata':
        case 'time':
          return $item[$column_name];
        default:
        return print_r($item, true);
      }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {
      $orderby = 'time';
      $order = 'desc';
      if (!empty($_GET['orderby'])) {
        $orderby = sanitize_key($_GET['orderby']);
      }
      if (!empty($_GET['order'])) {
        $order = sanitize_key($_GET['order']);
      }
      $result = strcmp($a[$orderby], $b[$orderby]);
      if ($order === 'asc') {
        return $result;
      }
      return -$result;
    }
}