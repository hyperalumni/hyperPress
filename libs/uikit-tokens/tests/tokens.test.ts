import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { compile } from 'sass';
import { describe, expect, it } from 'vitest';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');

function compileTokens(): string {
  return compile(resolve(root, 'src/index.scss'), {
    loadPaths: [resolve(root, 'node_modules')],
    quietDeps: true,
    silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
  }).css;
}

describe('uikit-tokens', () => {
  const css = compileTokens();

  it('applies the overridden primary background to .uk-button-primary', () => {
    const rule = /\.uk-button-primary\s*\{[^}]*\}/.exec(css);
    expect(rule).not.toBeNull();
    expect(rule?.[0]).toContain('background-color: #0b5fff');
  });

  it('applies the overridden font family to the base styles', () => {
    expect(css).toContain('font-family: system-ui');
  });

  it('includes the used components', () => {
    expect(css).toContain('.uk-button');
    expect(css).toContain('.uk-card');
  });

  it('leaves unused components out', () => {
    expect(css).not.toContain('.uk-slider');
  });
});
