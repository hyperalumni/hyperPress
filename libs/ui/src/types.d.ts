// @types/uikit already declares 'uikit'; it has no declaration for the icons bundle.
declare module 'uikit/dist/js/uikit-icons' {
  import type UIkit from 'uikit';

  const Icons: Parameters<typeof UIkit.use>[0];
  export default Icons;
}
