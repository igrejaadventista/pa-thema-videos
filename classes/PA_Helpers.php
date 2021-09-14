<?php

/** 
 * videoLength Format video length in 'mm:ss'
 *
 * @param  int $post_id The post ID
 * @return string Formated length string
 */
function videoLength(int $post_id = 0): string {
    $length = get_field('video_length', !empty($post_id) ? $post_id : get_the_ID());

    if(empty($length))
        return "";

    if($length / 3600 >= 1)
        return sprintf('%02d:%02d:%02d', ($length / 3600), ($length / 60 % 60), $length % 60);
    else
	    return sprintf('%02d:%02d', ($length / 60 % 60), $length % 60);
}

/**
 * Search the first priority seat of the post
 *
 * @param string $post_id The post ID
 * @return string
 */
function getPrioritySeat($post_id): string {
    if($term = get_the_terms($post_id, 'xtt-pa-owner'))
        return $term[0]->name;

    return 'Não há nenhuma sede proprietária vinculada a este post.';
}

/**
 * Search the first department of the post
 *
 * @param string $post_id The post ID
 * @return mixed
 */
function getDepartment($post_id) {
    if($term = get_the_terms($post_id, 'xtt-pa-departamentos'))
        return $term[0];

    return null;
}

/**
 * Search the related posts
 *
 * @param string $post_id The post ID
 * @param int $limit Maximum posts per query. Default = 6
 * @return array
 */
function getRelatedPosts($post_id, $limit = 6): array {
    if($term = get_the_terms($post_id, 'xtt-pa-projetos')):
        $args = array(
            'post_type'      => 'post',
            'post__not_in'   => array($post_id),
            'posts_per_page' => $limit,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'xtt-pa-projetos',
                    'terms'    => $term[0]->name,
                ),
            ),
        );
        
        return get_posts($args);
    endif;

    return array();
}

/**
 * Create a share link
 *
 * @param string $post_id The post ID
 * @param string $social A Social Network [Twitter, Facebook or Whatsapp].
 * @return void
 */
function linkToShare($post_id, $social): void {
    $texto = get_the_excerpt($post_id);
    $url = get_permalink($post_id);
    $titulo = get_the_title($post_id);
    $site = get_bloginfo('name');
    $via = "IASD";
    
    switch($social):
        case('twitter'):
            echo "https://twitter.com/intent/tweet?text=" . urlencode(wp_html_excerpt($texto, (247 - strlen($via)), '...')) . "&via=" . $via . "&url=" . urlencode($url);

            break;
        case('facebook'):
            echo "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url);

            break;
        case('whatsapp'):
            echo "https://api.whatsapp.com/send?text=" . urlencode($titulo) . "%20-%20" . $site . "%20-%20" . urlencode($url);

            break;
        default:
            die();
    endswitch;
}

function getHeaderTitle($post_id = NULL) {
    if(is_home() || is_front_page()) //is home
        return 'Divisão Sul-Americana';

    if(is_archive()) //is archive
        return get_taxonomy(get_queried_object()->taxonomy)->label . ' | ' . get_queried_object()->name;
    elseif(is_single()) //is single
        return get_taxonomy('xtt-pa-departamentos')->label . ' | ' . getDepartment($post_id)->name;
    
    return the_title(); //default
}

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
