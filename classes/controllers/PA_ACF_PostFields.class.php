<?php

use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Oembed;
use Extended\ACF\Location;

class PaAcfPostFields {

    public function __construct() {
        add_action('init', [$this, 'createACFFields']);
    }

    function createACFFields() {
        register_extended_field_group([
            'title' => __('Video info', 'iasd'),
            'key'   => 'video_info',
            'style' => 'default',
            'fields' => [
                Oembed::make(__('Video', 'iasd'), 'video_url')
                    ->required(),
                Number::make(__('Lenght', 'iasd'), 'video_length')
                    ->instructions(__('It will be get when saving the post.', 'iasd'))
                    ->readOnly(),
            ],
            'location' => [
                Location::where('post_type', '==', 'post'),
            ]
        ]);
    }

}

$PaAcfPostFields = new PaAcfPostFields();
