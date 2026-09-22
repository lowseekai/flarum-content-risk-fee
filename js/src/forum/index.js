import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import ContentRiskModal from './ContentRiskModal';

function enabled() {
  return app.forum.attribute('content-risk-fee.enabled') !== false;
}

function errorMessage(error) {
  return (
    error?.response?.errors?.[0]?.detail ||
    app.translator.trans('lowseekai-content-risk-fee.forum.errors.inspect_failed')
  );
}

function inspect(component, original) {
  if (!enabled() || component._contentRiskChecking) {
    return original();
  }

  const data = component.data();
  const content = String(data.content || '');
  const title = String(data.title || component.attrs.discussion?.title?.() || '');

  if (!content.trim()) {
    return original();
  }

  component._contentRiskChecking = true;
  component.loading = true;
  m.redraw();

  return app
    .request({
      method: 'POST',
      url: `${app.forum.attribute('apiUrl')}/content-risk/inspect`,
      body: {
        data: {
          type: 'content-risk-checks',
          attributes: { title, content },
        },
      },
    })
    .then((response) => {
      const attrs = response.data?.attributes || {};

      component._contentRiskChecking = false;
      component.loading = false;
      m.redraw();

      if (attrs.blocked) {
        app.modal.show(ContentRiskModal, {
          blocked: true,
        });
        return;
      }

      if (!attrs.requiresPayment) {
        return original();
      }

      app.modal.show(ContentRiskModal, {
        fee: Number(attrs.fee || 0),
        balance: Number(attrs.balance || 0),
        remainingBalance: Number(attrs.remainingBalance || 0),
        currency: attrs.currency,
        onconfirm: () => {
          component._contentRiskApproved = {
            token: attrs.token,
            title,
          };
          try {
            original();
          } finally {
            component._contentRiskApproved = false;
          }
        },
      });
    })
    .catch((error) => {
      component._contentRiskChecking = false;
      component.loading = false;
      m.redraw();
      app.alerts.show({ type: 'error' }, errorMessage(error));
    });
}

app.initializers.add('lowseekai/content-risk-fee', () => {
  ['DiscussionComposer', 'ReplyComposer'].forEach((composerName) => {
    override(`flarum/forum/components/${composerName}`, 'data', function (original) {
      const data = original();
      const approval = this._contentRiskApproved;

      if (approval && typeof approval === 'object' && approval.token) {
        data.contentRiskToken = approval.token;
        data.contentRiskTitle = approval.title || '';
      }

      return data;
    });
  });

  override('flarum/forum/components/DiscussionComposer', 'onsubmit', function (original) {
    return inspect(this, original.bind(this));
  });

  override('flarum/forum/components/ReplyComposer', 'onsubmit', function (original) {
    return inspect(this, original.bind(this));
  });
});
