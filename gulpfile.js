const { src, dest, watch, parallel, series } = require('gulp');

// CSS
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const postcss = require('gulp-postcss');
const sourcemaps = require('gulp-sourcemaps');

// Imagenes
const cache = require('gulp-cache');
const imagemin = require('gulp-imagemin');
const webp = require('gulp-webp');
const avif = require('gulp-avif');

// Javascript
const terser = require('gulp-terser');
const concat = require('gulp-concat');
const rename = require('gulp-rename');

const paths = {
    scss:            'src/scss/**/*.scss',
    js:              'src/js/**/*.js',
    imagenes:        'src/img/**/*',
    // Imágenes subidas dinámicamente por PHP
    uploadsBlog:     'public/build/assets/blog/**/*.{jpg,jpeg,png}',
    uploadsNot:      'public/build/assets/noticias/**/*.{jpg,jpeg,png}',
    uploadsUsuarios: 'public/build/assets/usuarios/**/*.{jpg,jpeg,png}',
};

function css() {
    return src(paths.scss)
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: 'expanded' }))
        .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('.'))
        .pipe(dest('public/build/css'));
}

function cssProd() {
    return src(paths.scss)
        .pipe(sass({ outputStyle: 'compressed' }))
        .pipe(postcss([autoprefixer(), cssnano()]))
        .pipe(dest('public/build/css'));
}

function javascript() {
    return src(paths.js)
        .pipe(sourcemaps.init())
        .pipe(concat('bundle.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('.'))
        .pipe(rename({ suffix: '.min' }))
        .pipe(dest('./public/build/js'));
}

function imagenes() {
    return src(paths.imagenes)
        .pipe(cache(imagemin({ optimizationLevel: 3 })))
        .pipe(dest('public/build/assets/img'));
}

function versionWebp() {
    return src('src/img/**/*.{png,jpg,jpeg}')
        .pipe(webp({ quality: 80 }))
        .pipe(dest('public/build/assets/img'));
}

function versionAvif() {
    return src('src/img/**/*.{png,jpg,jpeg}')
        .pipe(avif({ quality: 70 }))
        .pipe(dest('public/build/assets/img'));
}

/* ── Optimización de imágenes subidas (blog, noticias, usuarios) ── */

function optimizarUploads() {
    return src([paths.uploadsBlog, paths.uploadsNot, paths.uploadsUsuarios], { base: 'public/build/assets' })
        .pipe(imagemin({ optimizationLevel: 4 }))
        .pipe(dest('public/build/assets'));
}

function uploadsAWebp() {
    return src([paths.uploadsBlog, paths.uploadsNot, paths.uploadsUsuarios], { base: 'public/build/assets' })
        .pipe(webp({ quality: 82, method: 4 }))
        .pipe(dest('public/build/assets'));
}

/* WebP/AVIF para assets estáticos (public/build/assets/img/) referenciados en vistas */
function staticAssetsWebp() {
    return src('public/build/assets/img/**/*.{png,jpg,jpeg}', { base: 'public/build/assets' })
        .pipe(webp({ quality: 80 }))
        .pipe(dest('public/build/assets'));
}

function staticAssetsAvif() {
    return src('public/build/assets/img/**/*.{png,jpg,jpeg}', { base: 'public/build/assets' })
        .pipe(avif({ quality: 70 }))
        .pipe(dest('public/build/assets'));
}

/* Tarea completa: optimiza lossless + genera .webp para todos los uploads */
const optimizarImagenes = series(optimizarUploads, uploadsAWebp);

function dev(done) {
    watch(paths.scss, css);
    watch(paths.js, javascript);
    watch(paths.imagenes, imagenes);
    watch(paths.imagenes, versionWebp);
    watch(paths.imagenes, versionAvif);
    // Observa nuevas imágenes subidas y las optimiza automáticamente
    watch([paths.uploadsBlog, paths.uploadsNot, paths.uploadsUsuarios], optimizarImagenes);
    done();
}

exports.css              = css;
exports.cssProd          = cssProd;
exports.js               = javascript;
exports.imagenes         = imagenes;
exports.versionWebp      = versionWebp;
exports.versionAvif      = versionAvif;
exports.staticWebp       = staticAssetsWebp;   // npx gulp staticWebp
exports.staticAvif       = staticAssetsAvif;   // npx gulp staticAvif
exports.optimizar        = optimizarImagenes;  // npx gulp optimizar
exports.dev              = parallel(css, imagenes, versionWebp, versionAvif, javascript, dev);
exports.build            = parallel(cssProd, imagenes, versionWebp, versionAvif, staticAssetsWebp, staticAssetsAvif, javascript);
