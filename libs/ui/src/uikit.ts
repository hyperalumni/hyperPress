import UIkit from 'uikit';
import Icons from 'uikit/dist/js/uikit-icons';

let initialised = false;

/**
 * Register the UIkit Icons plugin once. Importing UIkit already makes it observe
 * the global document, so `_root` is accepted for the public signature only and a
 * second call is a no-op.
 */
export function initUikit(_root: Document = document): void {
  if (initialised) {
    return;
  }
  initialised = true;
  UIkit.use(Icons);
}
