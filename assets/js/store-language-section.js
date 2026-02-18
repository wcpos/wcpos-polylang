(function () {
  function registerLanguageSection() {
    if (!window.wcpos || !window.wcpos.storeEdit || typeof window.wcpos.storeEdit.registerSection !== 'function') {
      setTimeout(registerLanguageSection, 40);
      return;
    }

    var config = window.wcposPolylangStoreEdit || {};
    var defaultLanguage = config.defaultLanguage || '';
    var languages = Array.isArray(config.languages) ? config.languages : [];

    if (window.wcpos.storeEdit.getSections && window.wcpos.storeEdit.getSections().has('wcpos-polylang-language')) {
      return;
    }

    var el = window.wp && window.wp.element ? window.wp.element.createElement : null;
    var useEffect = window.wp && window.wp.element ? window.wp.element.useEffect : null;

    if (!el) {
      return;
    }

    function LanguageSection(props) {
      if (typeof useEffect === 'function') {
        useEffect(function () {
          if (!props.store.language && defaultLanguage) {
            props.onChange('language', defaultLanguage);
          }
        }, [props.store.language]);
      }

      var value = props.store.language || defaultLanguage || '';

      return el(
        'div',
        { className: 'wcpos:rounded-lg wcpos:border wcpos:border-gray-200 wcpos:bg-white wcpos:p-6' },
        el('div', { className: 'wcpos:mb-4' },
          el('h3', { className: 'wcpos:text-base wcpos:font-semibold wcpos:text-gray-900 wcpos:m-0' }, 'Language'),
          el('p', { className: 'wcpos:mt-1 wcpos:text-sm wcpos:text-gray-500' }, 'Choose which Polylang language this store uses in WCPOS.')
        ),
        languages.length > 0
          ? el(
            'select',
            {
              className: 'wcpos:block wcpos:w-full wcpos:rounded-md wcpos:border wcpos:border-gray-300 wcpos:px-2.5 wcpos:py-1.5 wcpos:text-sm wcpos:shadow-xs wcpos:focus:outline-none wcpos:focus:ring-2 wcpos:focus:ring-wp-admin-theme-color wcpos:focus:border-wp-admin-theme-color',
              value: value,
              onChange: function (event) {
                props.onChange('language', event.target.value);
              }
            },
            languages.map(function (language) {
              return el('option', { key: language.value, value: language.value }, language.label);
            })
          )
          : el('p', { className: 'wcpos:text-sm wcpos:text-gray-500' }, 'No Polylang languages found.')
      );
    }

    window.wcpos.storeEdit.registerSection('wcpos-polylang-language', {
      component: LanguageSection,
      label: 'Language',
      column: 'sidebar',
      priority: 32
    });
  }

  registerLanguageSection();
})();
