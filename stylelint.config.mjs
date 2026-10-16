// Stylelint for the repo's own Sass only (libs/uikit-tokens, themes/hyperpress-uikit).
// Foundation's legacy Sass is out of scope. Oxfmt is the formatter of record; Stylelint 17
// ships no stylistic rules, so there are no formatting conflicts to relax.
// No rule is relaxed so far: the UIkit `@import 'uikit/src/scss/...'` paths and the
// override-before-import order pass the standard-scss rules unchanged.
/** @type {import('stylelint').Config} */
export default {
  extends: ['stylelint-config-standard-scss'],
};
