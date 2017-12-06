<?php
/**
* Plugin Name: Asteriski Plugin
* Plugin URI: http://www.asteriski.fi
* Description: Asteriski-lisäosa Wordpressiin. Artikkelien lähetys ja niiden hallinnointi.
* Version: 0.0.1
* Author: Marko Loponen
* Author URI: https://github.com/z00ze
* License: GGWP 666
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/** ACTIVATION & DEACTIVATION */

function asteriski_plugin_activation(){
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS asteriski_emails (id int)");
    if (! wp_next_scheduled ( 'asteriski_send_emails_event' )) {
	wp_schedule_event(time(), 'hourly', 'asteriski_send_emails_event');
    }
}
register_activation_hook(__FILE__, 'asteriski_plugin_activation');

function asteriski_plugin_deactivation(){
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS asteriski_emails");
    wp_clear_scheduled_hook('asteriski_send_emails_event');
}
register_deactivation_hook(__FILE__, 'asteriski_plugin_deactivation');



/** ADMIN */
add_action('admin_menu', function() {
    add_options_page( 'Asteriski-lisäosan asetukset', 'Asteriski-lisäosa', 'manage_options', 'asteriski-plugin', 'asteriski_plugin_page' );
});
 
 
add_action( 'admin_init', function() {
    register_setting( 'asteriski-plugin-settings', 'send_to', 'asteriski_validate_email');
    register_setting( 'asteriski-plugin-settings', 'mail_prefix','asteriski_validate_text');
    register_setting( 'asteriski-plugin-settings', 'mail_header' );
    register_setting( 'asteriski-plugin-settings', 'mail_footer' );
    register_setting( 'asteriski-plugin-settings', 'mails_send_hour' );
    register_setting( 'asteriski-plugin-settings', 'mails_send_day' );
    register_setting( 'asteriski-plugin-settings', 'delete_post_poned' );

});
function asteriski_validate_text($input){
    return $input;
}
function asteriski_validate_email($input){
    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
      return ""; 
    }
    else {
        return $input;
    }
}
 
function asteriski_plugin_page() {
  ?>
    <div style="text-align: left;">
        <h1>Asteriski-plugin</h1>
      <form action="options.php" method="post">
 
        <?php
          settings_fields( 'asteriski-plugin-settings' );
          do_settings_sections( 'asteriski-plugin-settings' );
        ?>
        <table>
            <tr>
                <th valign="top">Where to send emails</th>
                <td><input type="email" placeholder="" name="send_to" value="<?php echo esc_attr( get_option('send_to') ); ?>" size="33" /></td>
            </tr>

            <tr>
                <th valign="top">Prefix of email</th>
                <td><input type="text" placeholder="" name="mail_prefix" value="<?php echo esc_attr( get_option('mail_prefix') ); ?>" size="33" /></td>
            </tr>
            <hr>
            <tr>
                <th valign="top">Header</th>
                <td><textarea placeholder="" name="mail_header" rows="5" cols="50"><?php echo esc_attr( get_option('mail_header') ); ?></textarea></td>
            </tr>
            
            <tr>
                <th valign="top">Footer</th>
                <td><textarea placeholder="" name="mail_footer" rows="5" cols="50"><?php echo esc_attr( get_option('mail_footer') ); ?></textarea></td>
            </tr>
 
            <tr>
                <th valign="top">Postponed emails sending hour</th>
                <td><select name="mails_send_hour">
                    <?php for ($i = 0; $i < 24; $i++) : ?>
                        <option <?php if(get_option('mails_send_hour')==$i) { echo "selected"; } ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th valign="top">Postponed emails sending day</th>
                <td>
                    <?php 
                    $msd = get_option('mails_send_day');
                    ?>
                <select name="mails_send_day">
                    <option value="1" <?php if($msd==1){ echo "selected"; }?>>Monday</option>
                    <option value="2" <?php if($msd==2){ echo "selected"; }?>>Tuesday</option>
                    <option value="3" <?php if($msd==3){ echo "selected"; }?>>Wednesday</option>
                    <option value="4" <?php if($msd==4){ echo "selected"; }?>>Thursday</option>
                    <option value="5" <?php if($msd==5){ echo "selected"; }?>>Friday</option>
                    <option value="6" <?php if($msd==6){ echo "selected"; }?>>Saturday</option>
                    <option value="0" <?php if($msd==0){ echo "selected"; }?>>Sunday</option>
                </select>
                </td>
            </tr>
            <tr>
            <th valign="top">Currently waiting to be sent</th>
                <td><?php 
                    global $wpdb;
                    $posts = $wpdb->get_results("SELECT DISTINCT id FROM asteriski_emails");
                    for($i = 0;$i<count($posts);$i++){
                        echo get_the_title($posts[$i]->id)."<br>";
                    }
                    if(get_option('delete_post_poned')==1){
                        delete_asteriski_emails();
                        update_option('delete_post_poned',0);
                    }
                    else{
                        
                    }
                    echo get_option('delete_post_poned');
                    echo '<label><input type="checkbox" value="1" name="delete_post_poned" />Remove all from postponed email list.</label>';
                    ?>
                </td>
            </tr>
            
            <tr>
                <td><?php submit_button(); ?></td>
            </tr>

 
        </table>
 
      </form>
    </div>
  <?php
}

/** POST */

add_action('post_submitbox_misc_actions', 'send_now');
add_action('save_post', 'save_send_now');
function send_now()
{
    $post_id = get_the_ID();
  
    if (get_post_type($post_id) != 'post') {
        return;
    }
  
    $value = get_post_meta($post_id, '_send_now', true);
    wp_nonce_field('asteriski_plugin_nonce_'.$post_id, 'asteriski_plugin_nonce');
    ?>
    <div class="misc-pub-section misc-pub-section-last">
        <label><input type="checkbox" value="1" <?php checked($value, true, true); ?> name="_send_now" />Send now!</label>
    </div>
    <div class="misc-pub-section misc-pub-section-last">
        <label><input type="checkbox" value="1" <?php checked($value, true, true); ?> name="_send_later" />Send later!</label>
    </div>
    <?php
}
function save_send_now($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (
        !isset($_POST['asteriski_plugin_nonce']) ||
        !wp_verify_nonce($_POST['asteriski_plugin_nonce'], 'asteriski_plugin_nonce_'.$post_id)
    ) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['_send_later'])){
        global $wpdb;
        $wpdb->query("INSERT INTO asteriski_emails (id) VALUES (".$post_id.")");
    }
    else{
        if (isset($_POST['_send_now'])) {
            send_email($post_id);
        }
    }
}
/** SEND EMAIL */
function send_email($post_id){
    $post = get_post($post_id);
    $to = get_option('send_to');
    $subject = get_option('mail_prefix')." ".get_the_category($post_id)." ".$post->post_title;
    $body = get_option('mail_header')."<br>".nl2br($post->post_content)."<br>".get_option('mail_footer');
    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail( $to, $subject, $body, $headers );
}
function send_later_emails(){
    global $wpdb;
    $posts = $wpdb->get_results("SELECT DISTINCT id FROM asteriski_emails");
    if(count($posts)==0) { return; }
    else{
        if(count($posts)==1) {
            if(send_email($posts[0]->id)){
                $wpdb->query("DELETE FROM asteriski_emails");
            }
        }
        else{
            $subject = get_option('mail_prefix')." <Jotain> ";
            $body = get_option('mail_header')."<br>";
            $subject_temp = "";
            $body_subject_temp = "";
            $body_temp = "";
            for($i = 0;$i<count($posts);$i++){
                $post = get_post($posts[$i]->id);
                $subject_temp .= $post->post_title." ";
                $body_subject_temp .= $post->post_title."<br>";
                $body_temp .= $post->post_title."<br><br>".nl2br($post->post_content)."<br><br>-----------------------------<br>";
            }
            $subject .= $subject_temp;
            $body .= $body_subject_temp."<br>-----------------------------<br>".$body_temp."".get_option('mail_footer');
            $to = get_option('send_to');
            $headers = array('Content-Type: text/html; charset=UTF-8');
            if(wp_mail( $to, $subject, $body, $headers )){
                delete_asteriski_emails();
            }
        }
    }
}
function delete_asteriski_emails(){
    global $wpdb;
    $wpdb->query("DELETE FROM asteriski_emails");
}

/** CRON JOB*/
function asteriski_send_emails_event(){
    if((date('H')+2) == get_option('mails_send_hour') && date('w') == get_option('mails_send_day')){
        send_later_emails();
    }
}