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
    const currency =
      attrs.currency ||
      app.forum.attribute('point-system.currency_name') ||
      '积分';

    if (attrs.blocked) {
      return (
        <div className="Modal-body">
          <div className="ContentRiskFeeModal-alert ContentRiskFeeModal-alert--blocked">
            <span className="ContentRiskFeeModal-alertIcon">
              <Icon name="fas fa-ban" />
            </span>
            <div className="ContentRiskFeeModal-alertContent">
              <strong>
                {app.translator.trans(
                  'lowseekai-content-risk-fee.forum.modal.blocked_title'
                )}
              </strong>
              <p>
                {app.translator.trans(
                  'lowseekai-content-risk-fee.forum.modal.blocked_message'
                )}
              </p>
            </div>
          </div>
          <div className="ContentRiskFeeModal-actions">
            <Button
              className="Button Button--secondary ContentRiskFeeModal-backButton"
              onclick={() => this.hide()}
            >
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.back'
              )}
            </Button>
          </div>
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="ContentRiskFeeModal-alert">
          <span className="ContentRiskFeeModal-alertIcon">
            <Icon name="fas fa-shield-alt" />
          </span>
          <div className="ContentRiskFeeModal-alertContent">
            <strong>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.risk_title'
              )}
            </strong>
            <p>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.risk_message'
              )}
            </p>
            <p>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.risk_message_note'
              )}
            </p>
          </div>
        </div>
        <dl className="ContentRiskFeeModal-summary">
          <div className="ContentRiskFeeModal-summaryRow">
            <dt>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.risk_type'
              )}
            </dt>
            <dd>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.risk_value'
              )}
            </dd>
          </div>
          <div className="ContentRiskFeeModal-summaryRow">
            <dt>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.fee'
              )}
            </dt>
            <dd className="ContentRiskFeeModal-value--accent">
              {attrs.fee} {currency}
            </dd>
          </div>
          <div className="ContentRiskFeeModal-summaryRow">
            <dt>
              {app.translator.trans(
                'lowseekai-content-risk-fee.forum.modal.balance_after'
              )}
            </dt>
            <dd>
              {attrs.remainingBalance} {currency}
            </dd>
          </div>
        </dl>
        {attrs.balance < attrs.fee && (
          <p className="helpText ContentRiskFeeModal-insufficient">
            {app.translator.trans(
              'lowseekai-content-risk-fee.forum.modal.insufficient'
            )}
          </p>
        )}
        <div className="ContentRiskFeeModal-actions">
          <Button
            className="Button Button--primary ContentRiskFeeModal-confirmButton"
            disabled={attrs.balance < attrs.fee}
            loading={attrs.loading}
            onclick={() => {
              this.hide();
              attrs.onconfirm();
            }}
          >
            {app.translator.trans(
              'lowseekai-content-risk-fee.forum.modal.confirm',
              { fee: attrs.fee, currency }
            )}
          </Button>
          <Button
            className="Button Button--secondary ContentRiskFeeModal-backButton"
            onclick={() => this.hide()}
          >
            {app.translator.trans(
              'lowseekai-content-risk-fee.forum.modal.back'
            )}
          </Button>
        </div>
      </div>
    );
  }
}
