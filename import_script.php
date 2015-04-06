<?php

$tag_string = "asus:mobile, dell:mobile, fog:wallpaper,alien:wallpaper";
$tag_array = explode(',', $tag_string);
foreach ($tag_array as $tag) {
    $tag_cat = explode(':', $tag);
    $tag = $tag_cat[0];
    $cat = $tag_cat[1];
    for ($i = 1; $i <= 4; $i++) {
        $start = $i * 8;
        $result = open_url($tag, $start);
        save_and_display_data($result, $cat);
    }
}

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

function save_and_display_data($data, $cat) {
    if (isset($data->responseData->results) && count($data->responseData->results)) {
        foreach ($data->responseData->results as $value) {
            print "<pre>";
            print_r($value);
            print '<img src="' . $value->url . '" >';
            // add_post_using_scrap($post_data, $term);
            print "</pre>";
        }
    }
}

function add_post_using_scrap($post_data, $term) {
    // global $user_ID;
    $user_ID = 1;
    $post_category = get_term_category($term);
    $new_post = array(
        'post_title' => 'My New Post',
        'post_content' => '',
        'post_status' => 'publish',
        'post_date' => date('Y-m-d H:i:s'),
        'post_author' => $user_ID,
        'post_type' => 'post',
        'post_category' => array($post_category)
    );
    $post_id = wp_insert_post($new_post);
    return $post_id;
}

function get_term_category($term) {
    // Get term by name ''news'' in Categories taxonomy.
    $category = get_term_by('name', $term, 'category');

// Get term by name ''news'' in Tags taxonomy.
//   $tag = get_term_by('name', 'news', 'post_tag');
// Get term by name ''news'' in Custom taxonomy.
//   $term = get_term_by('name', 'news', 'my_custom_taxonomy');
// Get term by name ''Default Menu'' from theme's nav menus.
// (Alternative to using wp_get_nav_menu_items)
//  $menu = get_term_by('name', 'Default Menu', 'nav_menu');
    return isset($category->term_id) ? $category->term_id : 0;
}
