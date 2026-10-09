'use strict';

import autoprefixer from 'gulp-autoprefixer';
import sourcemaps from 'gulp-sourcemaps';
import gulpSass from 'gulp-sass';
import * as sassEmbedded from 'sass-embedded';
import cleanCss from 'gulp-clean-css';
import gulpIf from 'gulp-if';
import rev from 'gulp-rev';
import imagemin from 'gulp-imagemin';
import imageminJpegtran from 'imagemin-jpegtran';
import imageminOptipng from 'imagemin-optipng';
import imageminGifsicle from 'imagemin-gifsicle';
import imageminSvgo from 'imagemin-svgo';
import uglify from 'gulp-uglify';
import zip from 'gulp-zip';
import phpcs from 'gulp-phpcs';
import phpcbf from 'gulp-phpcbf';
import browser from 'browser-sync';
import gulp from 'gulp';
import {rimraf} from 'rimraf';
import {load as yamlLoad} from 'js-yaml';
import {accessSync, constants as fsConstants, readFileSync} from 'fs';
import webpackStream from 'webpack-stream';
import webpack2 from 'webpack';
import named from 'vinyl-named';
import log from 'fancy-log';
import colors from 'ansi-colors';
import yargs from 'yargs';
import {hideBin} from 'yargs/helpers';

// Initialize sass compiler
const sass = gulpSass(sassEmbedded);

// Parse CLI args
const argv = yargs(hideBin(process.argv)).argv;

// Check for --production flag
const PRODUCTION = !!(argv['production']);

// Check for --development flag unminified with sourcemaps
const DEV = !!(argv.dev);

// Load settings from settings.yml
const {BROWSERSYNC, COMPATIBILITY, REVISIONING, PATHS} = loadConfig();

// Check if file exists synchronously
function checkFileExists(filepath) {
  let flag = true;
  try {
    accessSync(filepath, fsConstants.F_OK);
  } catch (e) {
    flag = false;
  }
  return flag;
}

// Load default or custom YML config file
function loadConfig() {
  log('Loading config file...');

  if (checkFileExists('config.yml')) {
    log(colors.bold(colors.cyan('config.yml')), 'exists, loading', colors.bold(colors.cyan('config.yml')));
    let ymlFile = readFileSync('config.yml', 'utf8');
    return yamlLoad(ymlFile);

  } else if (checkFileExists('config-default.yml')) {
    log(colors.bold(colors.cyan('config.yml')), 'does not exist, loading', colors.bold(colors.cyan('config-default.yml')));
    let ymlFile = readFileSync('config-default.yml', 'utf8');
    return yamlLoad(ymlFile);

  } else {
    log('Exiting process, no config file exists.');
    process.exit(1);
  }
}

// Delete the "dist" folder
// This happens every time a build starts
async function clean() {
  await rimraf(PATHS.dist);
}

// Copy files out of the assets folder
// This task skips over the "images", "js", and "scss" folders, which are parsed separately
function copy() {
  return gulp.src(PATHS.assets)
    .pipe(gulp.dest(PATHS.dist + '/assets'));
}

// Compile Sass into CSS
// In production, the CSS is compressed
function sassBuild() {
  return gulp.src(['src/assets/scss/app.scss', 'src/assets/scss/editor.scss', 'src/assets/scss/svg-wp-admin.scss'])
    .pipe(sourcemaps.init())
    .pipe(sass({
      loadPaths: PATHS.sass,
      silenceDeprecations: [
        'legacy-js-api',
        'import',
        'if-function',
        'global-builtin',
      ]
    }).on('error', sass.logError))
    .pipe(autoprefixer({
      overrideBrowserslist: COMPATIBILITY
    }))
    .pipe(gulpIf(PRODUCTION, cleanCss()))
    .pipe(gulpIf(!PRODUCTION, sourcemaps.write()))
    .pipe(gulpIf(REVISIONING && PRODUCTION || REVISIONING && DEV, rev()))
    .pipe(gulp.dest(PATHS.dist + '/assets/css'))
    .pipe(gulpIf(REVISIONING && PRODUCTION || REVISIONING && DEV, rev.manifest()))
    .pipe(gulp.dest(PATHS.dist + '/assets/css'))
    .pipe(browser.reload({stream: true}));
}

// Combine JavaScript into one file
// In production, the file is minified
const webpackConfig = {
  mode: PRODUCTION ? 'production' : 'development',
  module: {
    rules: [
      {
        test: /.js$/,
        loader: 'babel-loader',
        exclude: /node_modules(?![\\\/]foundation-sites)/,
      },
    ],
  },
  externals: {
    jquery: 'jQuery',
  },
};

function webpackChangeHandler(err, stats) {
  log('[webpack]', stats.toString({
    colors: true,
  }));
  browser.reload();
}

function webpackBuild() {
  return gulp.src(PATHS.entries)
    .pipe(named())
    .pipe(webpackStream(webpackConfig, webpack2))
    .pipe(gulpIf(PRODUCTION, uglify()
      .on('error', e => {
        console.log(e);
      }),
    ))
    .pipe(gulpIf(REVISIONING && PRODUCTION || REVISIONING && DEV, rev()))
    .pipe(gulp.dest(PATHS.dist + '/assets/js'))
    .pipe(gulpIf(REVISIONING && PRODUCTION || REVISIONING && DEV, rev.manifest()))
    .pipe(gulp.dest(PATHS.dist + '/assets/js'));
}

function webpackWatch() {
  const watchConfig = Object.assign({}, webpackConfig, {
    watch: true,
    devtool: 'inline-source-map',
  });

  return gulp.src(PATHS.entries)
    .pipe(named())
    .pipe(webpackStream(watchConfig, webpack2, webpackChangeHandler)
      .on('error', (err) => {
        log('[webpack:error]', err.toString({
          colors: true,
        }));
      }),
    )
    .pipe(gulp.dest(PATHS.dist + '/assets/js'));
}

// Copy images to the "dist" folder
// In production, the images are compressed
function images() {
  return gulp.src('src/assets/images/**/*')
    .pipe(gulpIf(PRODUCTION, imagemin([
      imageminJpegtran({progressive: true}),
      imageminOptipng({optimizationLevel: 5}),
      imageminGifsicle({interlaced: true}),
      imageminSvgo({
        plugins: [{name: 'preset-default'}],
      }),
    ])))
    .pipe(gulp.dest(PATHS.dist + '/assets/images'));
}

// WordPress derives the installed theme folder from the zip file name, so it must equal the theme slug.
const THEME_SLUG = 'hyperPress';

// Create a .zip archive of the theme
function archive() {
  return gulp.src(PATHS.package)
    .pipe(zip(THEME_SLUG + '.zip'))
    .pipe(gulp.dest('packaged'));
}

// Start BrowserSync to preview the site in
function server(done) {
  browser.init({
    proxy: BROWSERSYNC.url,
    ui: {
      port: 8080
    },
  });
  done();
}

// Reload the browser with BrowserSync
function reload(done) {
  browser.reload();
  done();
}

// Watch for changes to static assets, pages, Sass, and JavaScript
function watch() {
  gulp.watch(PATHS.assets, copy);
  gulp.watch('src/assets/scss/**/*.scss', sassBuild)
    .on('change', path => log('File ' + colors.bold(colors.magenta(path)) + ' changed.'))
    .on('unlink', path => log('File ' + colors.bold(colors.magenta(path)) + ' was removed.'));
  gulp.watch('**/*.php', reload)
    .on('change', path => log('File ' + colors.bold(colors.magenta(path)) + ' changed.'))
    .on('unlink', path => log('File ' + colors.bold(colors.magenta(path)) + ' was removed.'));
  gulp.watch('src/assets/images/**/*', gulp.series(images, reload));
}

// PHP Code Sniffer task
export const phpcsTask = function phpcsTask() {
  return gulp.src(PATHS.phpcs)
    .pipe(phpcs({
      bin: 'wpcs/vendor/bin/phpcs',
      standard: './codesniffer.ruleset.xml',
      showSniffCode: true,
    }))
    .pipe(phpcs.reporter('log'));
};
phpcsTask.displayName = 'phpcs';

// PHP Code Beautifier task
export const phpcbfTask = function phpcbfTask() {
  return gulp.src(PATHS.phpcs)
    .pipe(phpcbf({
      bin: 'wpcs/vendor/bin/phpcbf',
      standard: './codesniffer.ruleset.xml',
      warningSeverity: 0
    }))
    .on('error', log)
    .pipe(gulp.dest('.'));
};
phpcbfTask.displayName = 'phpcbf';

// Build the "dist" folder by running all the below tasks
export const build = gulp.series(clean, gulp.parallel(sassBuild, webpackBuild, images, copy));

// Build the site, run the server, and watch for file changes
export default gulp.series(build, server, gulp.parallel(webpackWatch, watch));

// Package task
export const packageTask = gulp.series(build, archive);
packageTask.displayName = 'package';




