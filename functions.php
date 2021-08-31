<?php

if(file_exists($composer = __DIR__ . '/vendor/autoload.php'))
    require_once $composer;

define('PARENT_THEME_URI', get_template_directory_uri() . '/');
define('THEME_URI', get_stylesheet_directory_uri() . '/');
define('THEME_DIR', get_stylesheet_directory() . '/');
define('THEME_CSS', THEME_URI . 'assets/css/');
define('THEME_JS', THEME_URI . 'assets/js/');
define('THEME_IMGS', THEME_URI . 'assets/images/');

require_once(dirname(__FILE__) . '/vendor/lordealeister/popular-posts/popular-posts.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_ACF_Leaders.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_ACF_HomeFields.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_ACF_PostFields.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_ACF_Site-ministries.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_CPT_Projects.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_CPT_Leaders.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_CPT_SliderHome.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_Enqueue_Files.class.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_Page_Lideres.php');
require_once(dirname(__FILE__) . '/classes/controllers/PA_Util.class.php');
require_once(dirname(__FILE__) . '/classes/PA_Helpers.php');

new \Blocks\ChildBlocks;

add_filter('popular-posts/settings/url', function() {
    return THEME_URI . 'vendor/lordealeister/popular-posts/';
});

add_filter('blade/view/paths', function ($paths) {
    $paths = (array)$paths;

    $paths[] = get_stylesheet_directory();

    return $paths;
});

add_filter('template_include', function ($template) {
    $path = explode('/', $template);
    $template_chosen = basename(end($path), '.php');
    $template_chosen = str_replace('.blade', '', $template_chosen);
    $grandchild_template = dirname(__FILE__) . '/' . $template_chosen . '.blade.php';

    if(file_exists($grandchild_template))
        return blade($template_chosen);

    return $template;
});

/**
* Modify category query
*/
add_action('pre_get_posts', function($query) {
    if(is_admin() || !is_category() || !$query->is_main_query())
        return $query;

    global $queryFeatured;
    $queryFeatured = new WP_Query(
        array(
            'posts_per_page' => 1,
            'post_status'	 => 'publish',
            'cat'			 => get_query_var('cat'),
            'post__in'       => get_option('sticky_posts'),
        )
    );

    if(empty($queryFeatured->found_posts)):
        $queryFeatured = new WP_Query(
            array(
                'posts_per_page' 	   => 1,
                'post_status'	 	   => 'publish',
                'cat'			 	   => get_query_var('cat'),
                'ignore_sticky_posts ' => true,
            )
        );
    endif;

    $query->set('posts_per_page', 15);
    $query->set('ignore_sticky_posts', true);
    $query->set('post__not_in', !empty($queryFeatured->found_posts) ? array($queryFeatured->posts[0]->ID) : null);

    return $query;
});

/**
* Filter save post to get video length
*/
add_action('acf/save_post', function($post_id) {
    if(get_post_type($post_id) != 'post')
        return;

    $url = parse_url(get_field('video_url', $post_id, false));
    $host = '';
    $id = '';

    if(empty($url))
        return;

    if(str_contains($url['host'], 'youtube') || str_contains($url['host'], 'youtu.be')):
        $host = 'youtube';

        if(array_key_exists('query', $url)):
            parse_str($url['query'], $params);
            $id = $params['v'];
        else:
            $id = str_replace('/', '', $url['path']);
        endif;
    elseif(str_contains($url['host'], 'vimeo')):
        $host = 'vimeo';
        $id = str_replace('/', '', $url['path']);
    endif;

    if(!empty($host) && !empty($id))
        getVideoLength($post_id, $host, $id);
});

/**
 * getVideoLength Get video length and save data
 *
 * @param  int    $post_id The post ID
 * @param  string $video_host The video host
 * @param  string $video_id The video ID
 * @return void
 */
function getVideoLength(int $post_id, string $video_host, string $video_id): void {
    $json = file_get_contents("https://api.feliz7play.com/v4/{$video_host}info?video_id={$video_id}");
    $obj = json_decode($json);

    if(!empty($obj))
        update_field('video_length', $obj->time, $post_id);
}
