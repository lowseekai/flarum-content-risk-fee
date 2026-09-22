import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';

export default class ContentRiskModal extends Modal {
  className() {
    return 'ContentRiskFeeModal Modal--small';
  }

  title() {
    return app.translator.trans('lowseekai-content-risk-fee.forum.modal.title');
  }

  content() {
    const attrs = this.attrs;
    const currency = attrs.currency || app.forum.attribute('point-system.currency_name') || '积分';

    if (attrs.blocked) {
      return (
        <div className="Modal-body">
          <div className="ContentRiskFeeModal-message ContentRiskFeeModal-message--blocked">
            <Icon name="fas fa-ban" />
            <div>
              <strong>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.blocked_title')}</strong>
              <p>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.blocked_message')}</p>
            </div>
          </div>
          <div className="Form-controls">
            <Button className="Button Button--secondary" onclick={() => this.hide()}>
              {app.translator.trans('lowseekai-content-risk-fee.forum.modal.back')}
            </Button>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="ContentRiskFeeModal-message">
          <Icon name="fas fa-shield-alt" />
          <div>
            <strong>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.risk_title')}</strong>
            <p>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.risk_message')}</p>
          </div>
        </div>
        <dl className="ContentRiskFeeModal-summary">
          <div>
            <dt>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.risk_type')}</dt>
            <dd>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.risk_value')}</dd>
          </div>
          <div>
            <dt>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.fee')}</dt>
            <dd>{attrs.fee} {currency}</dd>
          </div>
          <div>
            <dt>{app.translator.trans('lowseekai-content-risk-fee.forum.modal.balance_after')}</dt>
            <dd>{attrs.remainingBalance} {currency}</dd>
          </div>
        </dl>
        {attrs.balance < attrs.fee && (
          <p className="helpText ContentRiskFeeModal-insufficient">
            {app.translator.trans('lowseekai-content-risk-fee.forum.modal.insufficient')}
          </p>
        )}
        <div className="Form-controls">
          <Button
            className="Button Button--primary"
            disabled={attrs.balance < attrs.fee}
            loading={attrs.loading}
            onclick={() => {
              this.hide();
              attrs.onconfirm();
            }}
          >
            {app.translator.trans('lowseekai-content-risk-fee.forum.modal.confirm', { fee: attrs.fee, currency })}
          </Button>
          <Button className="Button Button--secondary" onclick={() => this.hide()}>
            {app.translator.trans('lowseekai-content-risk-fee.forum.modal.back')}
          </Button>
        </div>
      </div>
    );
  }
}
