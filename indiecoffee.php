<?php
/**
 * Plugin Name: Indie Coffee Shops
 * Description: Insert up to date information about indie coffee shops to your posts. List provided by adbeus.com.
 * Version: 1.3
 * Author: ADBEUS
 * Author URI: http://www.adbeus.com
 */

class Shopinfo {

  private static $json_source = 'http://api.indie.coffee/v1/shops';
  private static $json_maxage = 86400; //60*60*24;

  function __construct() {

    add_action( 'add_meta_boxes', array($this, 'add_meta_box') );

    add_action( 'save_post', array($this, 'save_meta_box_data') );

    add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );

    add_action( 'wp_ajax' .   '_shopinfo_shopname', array($this, 'shopname_autocomplete' ));
    add_action( 'wp_ajax_nopriv_shopinfo_shopname', array($this, 'shopname_autocomplete' ));

    add_action( 'init', array($this, 'shopname_init' ) );

    add_filter( 'the_content', array($this, 'the_content_filter'), 99 );
  }

  function the_content_filter( $content ) {

    global $post;

    $template = '';

    $shopID = get_post_meta( $post->ID, '_shopinfo_shopid'  , true ); // shopID

    if ($shopID) {

      $f = get_option( 'shopinfo_json_text' );
      $j = json_decode($f);

      if ($shopID && $j && $j->count > 0 && $j->features && is_array($j->features) && count($j->features) > 0) {

        foreach($j->features as $r) {

          $p = $r->properties;

          if (!$p->adbeusId || $p->adbeusId != $shopID) { continue; }

          $name = $p->name;
          $location = $p->meta_data->location->street;
          $o = $p->meta_data->hours;
          $url = $p->url;


          $s  = '';
          if ($o->mon_1_open && $o->mon_1_close) {
            $s .= 'Monday ' . $o->mon_1_open . ' - ' . $o->mon_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Monday - Closed';
          }
          if ($o->tue_1_open && $o->tue_1_close) {
            $s .= 'Tuesday ' . $o->tue_1_open . ' - ' . $o->tue_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Tuesday - Closed';
          }
          if ($o->wed_1_open && $o->wed_1_close) {
            $s .= 'Wednesday ' . $o->wed_1_open . ' - ' . $o->wed_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Wednesday - Closed';
          }
          if ($o->thu_1_open && $o->thu_1_close) {
            $s .= 'Thursday ' . $o->thu_1_open . ' - ' . $o->thu_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Thursday - Closed';
          }

          if ($o->fri_1_open && $o->fri_1_close) {
            $s .= 'Friday ' . $o->fri_1_open . ' - ' . $o->fri_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Friday - Closed';
          }
          if ($o->sat_1_open && $o->sat_1_close) {
            $s .= 'Saturday ' . $o->sat_1_open . ' - ' . $o->sat_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Saturday - Closed';
          }
          if ($o->sun_1_open && $o->sun_1_close) {
            $s .= 'Sunday ' . $o->sun_1_open . ' - ' . $o->sun_1_close . '<br/>' . "\n";
          }
          else {
            $s .= 'Sunday - Closed';
          }

          $openhours = $s;
/*
[hours] => stdClass Object
   (
       [mon_1_open] => 08:00
       [mon_1_close] => 18:00
       [tue_1_open] => 08:00
       [tue_1_close] => 18:00
       [wed_1_open] => 
       [wed_1_close] => 
       [thu_1_open] => 08:00
       [thu_1_close] => 18:00
       [fri_1_open] => 08:00
       [fri_1_close] => 18:00
       [sat_1_open] => 08:00
       [sat_1_close] => 18:00
       [sun_1_open] => 08:00
       [sun_1_close] => 18:00
   )
*/

          $template = '
<!-- Shop ID: %SHOPID% -->
<a href="%URL%"><img class="alignleft wp-image-759" src="http://mtlbeanstalk.com/wp-content/uploads/2015/08/adbeus_icon-02.jpg" alt="adbeus_icon-02" width="50" height="54" /></a>
<strong>%NAME%</strong>
<br>
%LOCATION%
<br>
<a href="%URL%">Opening Hours</a> / <a href="http://adbeus.com/app/">Download Adbeus App</a>
';

          $template = str_replace('%NAME%'     , $name     , $template);
          $template = str_replace('%SHOPID%'   , $shopID   , $template);
          $template = str_replace('%LOCATION%' , $location , $template);
          $template = str_replace('%OPENHOURS%', $openhours, $template);
          $template = str_replace('%URL%', $url, $template);
        }
      }

    }

    $content = $content . "\n" . '<!-- Shop Info -->' . "\n" . $template . "\n";

    return $content;
  } 

  function shopname_init() {

    $old = get_option( 'shopinfo_json_time' );

    if (time() - $old - self::$json_maxage > 0) {

      $this->update_json_feed();
    }

    //$f = get_option( 'shopinfo_json_text' );
    //$j = json_decode($f);
    //print '<br/><pre>' . "\n";
    //print_r($j);
    //print '</pre>';
    //exit;
  }

  function shopname_autocomplete() {

    $suggestions = array();

    $term = strtolower( trim($_GET['term']) );

    if (strlen($term) > 0) {

      $f = get_option( 'shopinfo_json_text' );
      $j = json_decode($f);

      if ($j && $j->count > 0 && $j->features && is_array($j->features) && count($j->features) > 0) {

        foreach($j->features as $key => $r) {

          $p = $r->properties;

          if (!$p->name || !$p->adbeusId) { continue; }

          $pos = strpos(strtolower($p->name), $term);

          if ($pos === false) {

            continue;
          }

          $suggestion = array();
          $suggestion['label']   = $p->name;
          $suggestion['link']    = $p->adbeusId;
          $suggestion['shopid']  = $p->adbeusId;

          $suggestions[] = $suggestion;
        }
      }
    }

    $response = json_encode( $suggestions );
    echo $response;
    exit();
  }

  function enqueue_scripts() {

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-autocomplete');

    wp_register_style( 'jquery-ui-styles','http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
    wp_enqueue_style(  'jquery-ui-styles' );

    wp_register_script( 'shopinfo-autocomplete', plugins_url( '/js/indiecoffee-autocomplete.js' , __FILE__ ), array( 'jquery', 'jquery-ui-autocomplete' ), '1.0', false );
    wp_localize_script( 'shopinfo-autocomplete', 'ShopInfo', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
    wp_enqueue_script(  'shopinfo-autocomplete' );
                                           
  }

  function add_meta_box( $post_type ) {

    $post_types = array( 'post' );

    if ( in_array( $post_type, $post_types )) {

      add_meta_box(
        'shopinfo_metabox',
        __( 'Coffee Shop Information' ),
        array($this, 'render_meta_box_content'),
        $post_type
        ,'advanced'
        ,'high'
      );
    }

  }

  function render_meta_box_content( $post ) {

    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'shopinfo_save_meta_box_data', 'shopinfo_meta_box_nonce' );

    $shopID = get_post_meta( $post->ID, '_shopinfo_shopid'  , true ); // shopID

    $name = '';

    $f = get_option( 'shopinfo_json_text' );
    $j = json_decode($f);

    if ($shopID && $j && $j->count > 0 && $j->features && is_array($j->features) && count($j->features) > 0) {

      foreach($j->features as $r) {

        $p = $r->properties;

        if (!$p->adbeusId || $p->adbeusId != $shopID) { continue; }

        $name = $p->name;
      }
    }


    echo "\n" . '<label for="shopinfo_shopname">';
    _e( 'Name:' );
    echo '</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . "\n";
    echo ' <input type="text" id="shopinfo_shopname" name="shopinfo_shopname" value="' . esc_attr( $name ) . '" size="40" />' . "\n";

    echo "\n" . '<br/>' . "\n";
    echo "\n" . '<label for="shopinfo_shopid">';
    _e( 'Coffee Shop ID:' );
    echo '</label> ' . "\n";
    echo '<b><span id="shopinfo_textid">' . $shopID . '</span></b>' . "\n";
    echo '<input type="hidden" id="shopinfo_shopid" name="shopinfo_shopid"    value="' . esc_attr( $shopID )     . '" />' . "\n";
    echo '<br/><input type="button" class="button" name="B1" value="Remove" onclick="jQuery(\'#shopinfo_shopid\').val(\'\');jQuery(\'#shopinfo_textid\').html(\'\');jQuery(\'#shopinfo_shopname\').val(\'\');" />' . "\n";

    
    $t = get_option( 'shopinfo_json_time' );
    $f = get_option( 'shopinfo_json_text' );

    echo '<br/>';
    echo '<span>';
    echo '<br/>';
    echo 'DETAILS' . '<br/>' . "\n";
    // echo 'JSON lenghth: ' . strlen($f) . '<br/>' . "\n";
    echo 'Last updated: ' . date('Y-m-d H:i:s', $t) . '<br/>' . "\n";
    echo 'Current date/time: ' . date('Y-m-d H:i:s');
    echo '</span>' . "\n";
  }

  function save_meta_box_data( $post_id ) {

    /*
    * We need to verify this came from our screen and with proper authorization,
    * because the save_post action can be triggered at other times.
    */

    // Check if our nonce is set.
    if ( ! isset( $_POST['shopinfo_meta_box_nonce'] ) ) {
      return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['shopinfo_meta_box_nonce'], 'shopinfo_save_meta_box_data' ) ) {
      return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
      return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'post' == $_POST['post_type'] ) {

      if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
      }
    }
    else {

      return;
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if ( ! isset( $_POST['shopinfo_shopid'] ) ) {
      return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field( $_POST['shopinfo_shopid'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, '_shopinfo_shopid', $my_data );
  }

  private function update_json_feed() {

    $f = file_get_contents(self::$json_source); // json_decode

    update_option( 'shopinfo_json_text', $f );
    update_option( 'shopinfo_json_time', time() );
  }

  static function install() {

    $my_shopinfo2 = new Shopinfo;
    $my_shopinfo2->update_json_feed();
  }
  static function uninstall() {

     delete_option( 'shopinfo_json_text' );
     delete_option( 'shopinfo_json_time' );
  }
}

$my_shopinfo = new Shopinfo;

register_activation_hook(   __FILE__, array( 'Shopinfo', 'install'   ) );
register_deactivation_hook( __FILE__, array( 'Shopinfo', 'uninstall' ) );