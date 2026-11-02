import $ from 'jquery';
import whatInput from 'what-input';

window.$ = $;

import Foundation from 'foundation-sites';
// If you want to pick and choose which modules to include, comment out the above and uncomment
// the line below
// import './lib/foundation-explicit-pieces';

Foundation.Sticky.defaults.marginTop = 0;

$(document).foundation();

// Foundation's ResponsiveToggle sets no ARIA on its button, so keep aria-expanded in step with the menu it shows.
function syncMenuToggleState() {
  $('[data-responsive-toggle]').each(function () {
    const $bar = $(this);
    const expanded = $('#' + $bar.attr('data-responsive-toggle')).is(':visible');

    $bar.find('[data-toggle][aria-expanded]').attr('aria-expanded', expanded);
  });
}

$('[data-responsive-toggle]').on('toggled.zf.responsiveToggle', syncMenuToggleState);
$(window).on('changed.zf.mediaquery', syncMenuToggleState);
