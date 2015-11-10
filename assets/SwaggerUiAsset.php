<?php
/**
 * Created by PhpStorm.
 * User: Ltm
 * Date: 05.11.2015
 * Time: 15:51
 */

namespace app\assets;

class SwaggerUiAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/swagger-ui/dist';
    public $js = [
        'lib/jquery-1.8.0.min.js',
        'lib/jquery.slideto.min.js',
        'lib/jquery.wiggle.min.js',
        'lib/jquery.ba-bbq.min.js',
        'lib/handlebars-2.0.0.js',
        'lib/underscore-min.js',
        'lib/backbone-min.js',
        'swagger-ui.js',
        'lib/highlight.7.3.pack.js',
        'lib/marked.js',
        'lib/swagger-oauth.js',
    ];

    public $css = [
        'css/typography.css',
        'css/reset.css',
        'css/screen.css',
    ];
}