var gulp = require('gulp');

var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

gulp.task("copyfiles", function() {
    gulp.src([
        // jquery
        "./bower_components/jquery/dist/jquery.min.js",
        // bootstrap
        "./bower_components/bootstrap/dist/css/bootstrap.min.css",
        "./bower_components/bootstrap/dist/js/bootstrap.min.js",
        "./bower_components/bootstrap/dist/fonts/**.*",
        // datatables
        "./bower_components/datatables/media/css/dataTables.bootstrap.min.css",
        "./bower_components/datatables/media/js/dataTables.bootstrap.min.js",
        "./bower_components/datatables/media/js/jquery.dataTables.min.js",
        // adminlte
        "./bower_components/AdminLTE/dist/css/AdminLTE.min.css",
        "./bower_components/AdminLTE/dist/css/skins/skin-blue.min.css",
        "./bower_components/AdminLTE/dist/js/app.min.js",
        "./bower_components/AdminLTE/plugins/**/*",

        // font-awesome
        "./bower_components/font-awesome/css/font-awesome.min.css",
        "./bower_components/font-awesome/fonts/**.*",

        // jquery-validation
        "./bower_components/jquery-validation/dist/jquery.validate.js",
        "./bower_components/jquery-validation/dist/additional-methods.js",

    ], {base: './bower_components/'})
        .   pipe(gulp.dest("./public/plugins/"));
});

elixir(function(mix) {
    mix.copy('resources/assets/img', 'public/img');
});

elixir(function(mix) {
    mix.copy('resources/assets/fonts', 'public/fonts');
});

elixir(function(mix) {
    mix.copy('resources/assets/css', 'public/css');
});

elixir(function(mix) {
    mix.copy('resources/assets/js', 'public/js');
});

// elixir(function(mix) {
//     mix.sass('app.scss');
// });
