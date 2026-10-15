import UIkit from 'uikit';
import { describe, expect, it, vi } from 'vitest';
import { initUikit } from '../src';

describe('initUikit', () => {
  it('calls UIkit.use exactly once across repeated calls', () => {
    const use = vi.spyOn(UIkit, 'use');
    initUikit();
    initUikit();
    expect(use).toHaveBeenCalledTimes(1);
    use.mockRestore();
  });

  it('renders uk-icon elements as svg', async () => {
    document.body.innerHTML = '<span uk-icon="icon: home"></span>';
    initUikit();
    const span = document.querySelector('span[uk-icon]');
    await vi.waitFor(() => expect(span?.querySelector('svg')).not.toBeNull());
  });
});
