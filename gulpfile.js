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
        "./bower_components/datatables/media/js/jquery.dataTables.min.js"
    ], {base: './bower_components/'})
        .   pipe(gulp.dest("./public/plugins/"));
    // gulp.src("bower_componets/datatables/media/css/jquery.dataTables.min.css")
    //     .pipe(gulp.dest("public/plugins/datatables/"));
    // gulp.src("bower_componets/datatables/media/js/jquery.dataTables.min.js")
    //     .pipe(gulp.dest("public/plugins/datatables/"));
    // gulp.src("bower_componets/bootstrap/css/**")
    //     .pipe(gulp.dest("public/plugins/bootstrap"));
    // gulp.src("bower_componets/bootstrap/dist/js/bootstrap.js")
    //     .pipe(gulp.dest("resources/assets/js/"));
    // gulp.src("vendor/bower_dl/bootstrap/dist/fonts/**")
    //     .pipe(gulp.dest("public/assets/fonts"));
    // gulp.src("vendor/bower_dl/fontawesome/less/**")
    //     .pipe(gulp.dest("resources/assets/less/fontawesome"));
    // gulp.src("vendor/bower_dl/fontawesome/fonts/**")
    //     .pipe(gulp.dest("public/assets/fonts"));
    // // Copy datatables  var dtDir = 'vendor/bower_dl/datatables-plugins/integration/';
    // gulp.src("vendor/bower_dl/datatables/media/js/jquery.dataTables.js")
    //     .pipe(gulp.dest('resources/assets/js/'));
    // gulp.src(dtDir + 'bootstrap/3/dataTables.bootstrap.css')
    //     .pipe(rename('dataTables.bootstrap.less'))
    //     .pipe(gulp.dest('resources/assets/less/others/'));
    // gulp.src(dtDir + 'bootstrap/3/dataTables.bootstrap.js')
    //     .pipe(gulp.dest('resources/assets/js/'));
});

elixir(function(mix) {
    mix.sass('app.scss');
});
