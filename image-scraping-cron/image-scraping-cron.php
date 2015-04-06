<?php

/**
 * Plugin Name: Image scraping cron 
 * Description: Demo for Image scraping functions in WordPress
 * Version: 1.0
 * Author: Mudassar Ali
 * License: GPL2
 */
// Add a new submenu under DASHBOARD
function image_scraping_cron_menu() {

    // using a wrapper function (easy, but not good for adding JS later - hence not used)
    // add_dashboard_page('Plugin Starter', 'Plugin Starter', 'administrator', 'pluginStarter', 'pluginStarter');
    // using array - same outcome, and can call JS with it
    // explained here: http://codex.wordpress.org/Function_Reference/wp_enqueue_script
    // and here: http://pippinsplugins.com/loading-scripts-correctly-in-the-wordpress-admin/
    global $scraping_cron_admin_page;
    $scraping_cron_admin_page = add_submenu_page('index.php', __('Image scraping cron', 'image-scraping-cron'), __('Image scraping cron', 'image-scraping-cron'), 'manage_options', 'imageScrapingCron', 'imageScrapingCron');
}

add_action('admin_menu', 'image_scraping_cron_menu');

// register our JS file
function image_scraping_cron_admin_init() {
     wp_register_script('custom-starter-script', plugins_url('/image-scraping-cron.js', __FILE__));
}

 add_action('admin_init', 'image_scraping_cron_admin_init');
// now load the scripts we need
function scraping_cron_admin_scripts($hook) {

    global $scraping_cron_admin_page;
    if ($hook != $scraping_cron_admin_page) {
        return;
    }
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('custom-starter-script');
}

// and make sure it loads with our custom script
add_action('admin_enqueue_scripts', 'scraping_cron_admin_scripts');

// link some styles to the admin page
$starterstyles = plugins_url('image-scraping-cron.css', __FILE__);
wp_enqueue_style('imagescrapingstyles', $starterstyles);

////////////////////////////////////////////
/*         CRON DEMO STARTS HERE          */
/////////////////////////////////////////////
// unschedule event upon plugin deactivation
function imagecronstarter_deactivate() {
    // find out when the last event was scheduled
    $timestamp = wp_next_scheduled('image_scraping_cron_job');
    // unschedule previous event if any
    wp_unschedule_event($timestamp, 'image_scraping_cron_job');
}

register_deactivation_hook(__FILE__, 'imagecronstarter_deactivate');

// create a scheduled event (if it does not exist already)
function imagecronstarter_activation() {
    if (!wp_next_scheduled('image_scraping_cron_job')) {
        wp_schedule_event(time(), 'everyminute', 'image_scraping_cron_job');
    }
}

// and make sure it's called whenever WordPress loads
add_action('wp', 'imagecronstarter_activation');

// here's the function we'd like to call with our cron job
function image_cron_repeat_function() {
    $keyword_value = get_option('keyword_value');
    $tag_array = explode(',', $keyword_value);
    foreach ($tag_array as $tag) {
        for ($i = 1; $i <= 4; $i++) {
            $start = $i * 8;
            $result = open_url($tag, $start);
            save_and_display_data($result);
        }
    }
}

// hook that function onto our scheduled event:
add_action('image_scraping_cron_job', 'image_cron_repeat_function');

function open_url($tag, $start) {
    $full_url = 'http://ajax.googleapis.com/ajax/services/search/images?v=1.0&rsz=8&start=' . $start . '&q=' . trim($tag);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $full_url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_REFERER, 'http://localhost');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    //  $json = utf8_decode(curl_exec($curl));
    $json = curl_exec($curl);
    curl_close($curl);
    $data = json_decode($json);
    return $data;
}

function save_and_display_data($data) {
    if (isset($data->responseData->results) && count($data->responseData->results)) {
        foreach ($data->responseData->results as $value) {
            print "<pre>";
            print_r($value);
            //  print '<img src="'.$value->url.'" >';
            print "</pre>";
        }
    }
}


// CUSTOM INTERVALS
// by default we only have hourly, twicedaily and daily as intervals 
// to add your own, use something like this - the example adds 'weekly'
// http://codex.wordpress.org/Function_Reference/wp_get_schedules

function image_cron_add_weekly($schedules) {
    // Adds once weekly to the existing schedules.
    $schedules['weekly'] = array(
        'interval' => 604800,
        'display' => __('Once Weekly')
    );
    return $schedules;
}

add_filter('cron_schedules', 'image_cron_add_weekly');

// add another interval
function image_cron_add_minute($schedules) {
    // Adds once every minute to the existing schedules.
    $schedules['everyminute'] = array(
        'interval' => 60,
        'display' => __('Once Every Minute')
    );
    return $schedules;
}

add_filter('cron_schedules', 'image_cron_add_minute');

/*
  more info here:
  http://wp.tutsplus.com/tutorials/theme-development/do-it-yourself-wordpress-scheduling-mastering-wp-cron/

  THE REST OF THE CODE IS NOT USED FOR THE CRON FUNCTION
 */

////////////////////////////////////////////
// here's the code for the actual admin page
function imageScrapingCron() {
    if (isset($_POST['submit'])) {
        $keyword_value = $_POST['keyword_img'];
        if ($keyword_value) {
            // The option already exists, so we just update it.
            update_option('keyword_value', $keyword_value);
        } else {
            add_option('keyword_value', $keyword_value);
        }
    }
    $keyword_value = get_option('keyword_value');
    // die($keyword_value);
// check that the user has the required capability 
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient privileges to access this page. Sorry!'));
    }

    ///////////////////////////////////////
    // MAIN AMDIN CONTENT SECTION
    ///////////////////////////////////////
    // display heading with icon WP style
    ?>
    <div class="wrap">
        <div id="icon-index" class="icon32"><br></div>
        <h2>Image scraping cron</h2>
        <?php
        // let's create jQuery UI Tabs, as demonstrated in the standalone version 
        // or at http://jqueryui.com/tabs/#default

        echo '<p>Add key word to scrapp</p>';
        ?>

        <div id="tabs">
            <ul>
                <li><a href="#tabs-1">Key word tab</a></li>
                <li><a href="#tabs-2">Results Done</a></li>
            </ul>
            <div id="tabs-1">
                <h3>Add keyword</h3>
                <form action="<?php echo site_url('/wp-admin/admin.php?page=imageScrapingCron'); ?>" method="post">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td>
                                    <textarea name="keyword_img" id="keyword_img" class="large-text code" rows="12"><?php echo $keyword_value; ?></textarea>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td>
                                    <p>add comma separated key words (like asus, dell, intel) on which basis you wants to scrap images from google</p>
                                </td>
                            </tr>

                        </tbody></table>


                    <p class="submit">
                        <input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit">
                    </p>
                </form>
            </div>
            <div id="tabs-2">
                <h3>Keywords which scaping done</h3>
                <textarea name="keyword_img" id="keyword_img" class="large-text code" rows="12" readonly><?php echo $keyword_value; ?></textarea>
            </div>
        </div> <!-- end of tabs wrap -->

    </div> <!-- end of main wrap -->
    <?php
}

// end of main function
?>
