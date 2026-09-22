import app from 'flarum/admin/app';

app.initializers.add('lowseekai/content-risk-fee-admin', () => {
  app.registry
    .for('lowseekai-content-risk-fee')
    .registerSetting({
      setting: 'content-risk-fee.enabled',
      type: 'boolean',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.enabled'),
      help: app.translator.trans('lowseekai-content-risk-fee.admin.settings.enabled_help'),
    })
    .registerSetting({
      setting: 'content-risk-fee.detect_links',
      type: 'boolean',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.detect_links'),
    })
    .registerSetting({
      setting: 'content-risk-fee.detect_sensitive_words',
      type: 'boolean',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.detect_sensitive_words'),
    })
    .registerSetting({
      setting: 'content-risk-fee.link_fee',
      type: 'number',
      min: 0,
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.link_fee'),
    })
    .registerSetting({
      setting: 'content-risk-fee.sensitive_fee',
      type: 'number',
      min: 0,
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.sensitive_fee'),
    })
    .registerSetting({
      setting: 'content-risk-fee.max_fee',
      type: 'number',
      min: 0,
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.max_fee'),
    })
    .registerSetting({
      setting: 'content-risk-fee.whitelist_domains',
      type: 'textarea',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.whitelist_domains'),
      help: app.translator.trans('lowseekai-content-risk-fee.admin.settings.whitelist_domains_help'),
    })
    .registerSetting({
      setting: 'content-risk-fee.sensitive_words',
      type: 'textarea',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.sensitive_words'),
      help: app.translator.trans('lowseekai-content-risk-fee.admin.settings.sensitive_words_help'),
    })
    .registerSetting({
      setting: 'content-risk-fee.custom_regex',
      type: 'textarea',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.custom_regex'),
      help: app.translator.trans('lowseekai-content-risk-fee.admin.settings.custom_regex_help'),
    })
    .registerSetting({
      setting: 'content-risk-fee.duplicate_enabled',
      type: 'boolean',
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.duplicate_enabled'),
    })
    .registerSetting({
      setting: 'content-risk-fee.duplicate_window_hours',
      type: 'number',
      min: 1,
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.duplicate_window_hours'),
    })
    .registerSetting({
      setting: 'content-risk-fee.duplicate_block_after',
      type: 'number',
      min: 2,
      label: app.translator.trans('lowseekai-content-risk-fee.admin.settings.duplicate_block_after'),
    })
    .registerPermission(
      {
        icon: 'fas fa-shield-alt',
        label: app.translator.trans('lowseekai-content-risk-fee.admin.permissions.bypass'),
        permission: 'lowseekai-content-risk-fee.bypass',
      },
      'moderate'
    );
});
