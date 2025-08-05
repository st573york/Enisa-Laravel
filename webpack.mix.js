const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/mix/js').sass('resources/sass/app.scss', 'css').version();

mix.js('resources/js/index/comparison.js', 'public/mix/js').version();
mix.js('resources/js/index/edit.js', 'public/mix/js').version();
mix.js('resources/js/index/sunburst.js', 'public/mix/js').version();
mix.js('resources/js/questionnaire/questionnaire.js', 'public/mix/js').version();
mix.js('resources/js/questionnaire/questionnaire-actions.js', 'public/mix/js').version();
mix.js('resources/js/questionnaire/questionnaire-list.js', 'public/mix/js').version();
mix.js('resources/js/main/main.js', 'public/mix/js').version();
mix.js('resources/js/main/alert.js', 'public/mix/js').version();
mix.js('resources/js/main/modal.js', 'public/mix/js').version();
mix.js('resources/js/main/datepicker-custom.js', 'public/mix/js').version();
mix.js('resources/js/main/tinymce-custom.js', 'public/mix/js').version();
mix.js('resources/js/reports/paged.polyfill.js', 'public/mix/js').version();

mix.copyDirectory('vendor/tinymce/tinymce', 'public/js/tinymce');

