// Admin: media pickers for brand / category / product fields + pricing confirmation.
/* global wp, BKAdmin */

function initMediaField(root) {
  const input = root.querySelector('[data-bk-media-input]');
  const preview = root.querySelector('[data-bk-media-preview]');
  const remove = root.querySelector('[data-bk-media-remove]');
  const isVideo = root.dataset.bkMediaType === 'video';
  let frame = null;

  root.querySelector('[data-bk-media-select]').addEventListener('click', (event) => {
    event.preventDefault();
    if (!frame) {
      frame = wp.media({
        title: isVideo ? BKAdmin.chooseVideo : BKAdmin.chooseImage,
        button: { text: BKAdmin.useThis },
        library: { type: isVideo ? 'video' : 'image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        input.value = attachment.id;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        if (preview && attachment.type === 'image') {
          preview.src = (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) || attachment.url;
          preview.hidden = false;
        }
        if (remove) remove.hidden = false;
      });
    }
    frame.open();
  });

  if (remove) {
    remove.addEventListener('click', (event) => {
      event.preventDefault();
      input.value = '';
      if (preview) preview.hidden = true;
      remove.hidden = true;
    });
  }
}

function init() {
  document.querySelectorAll('[data-bk-media]').forEach(initMediaField);

  // Reset media previews after a term is added via AJAX on the taxonomy screen.
  if (window.jQuery) {
    window.jQuery(document).ajaxSuccess((e, xhr, settings) => {
      if (settings && typeof settings.data === 'string' && settings.data.includes('action=add-tag')) {
        document.querySelectorAll('#addtag [data-bk-media]').forEach((root) => {
          root.querySelector('[data-bk-media-input]').value = '';
          const preview = root.querySelector('[data-bk-media-preview]');
          if (preview) preview.hidden = true;
        });
      }
    });
  }

  const pricing = document.querySelector('[data-bk-pricing-form]');
  if (pricing) {
    pricing.addEventListener('submit', (event) => {
      if (event.submitter && event.submitter.name === 'apply') {
        const rate = pricing.querySelector('[name="rate"]').value;
        // eslint-disable-next-line no-alert
        if (!window.confirm(`تأكيد: إعادة حساب الأسعار على سعر صرف ${rate} ل.س للدولار؟`)) {
          event.preventDefault();
        }
      }
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
