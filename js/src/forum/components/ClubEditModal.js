import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import { t } from '../util';

/** Start a club, or rename and describe one. */
export default class ClubEditModal extends FormModal {
  oninit(vnode) {
    super.oninit(vnode);
    const club = this.attrs.club;
    this.name = club ? club.name() : '';
    this.description = (club && club.description()) || '';
  }

  className() {
    return 'ClubEditModal Modal--small';
  }

  title() {
    return t(this.attrs.club ? 'club.edit_title' : 'club.new_title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form-group">
          <label for="club-name">{t('club.name_label')}</label>
          <input id="club-name" className="FormControl" maxlength="100" required value={this.name} oninput={(e) => (this.name = e.target.value)} />
        </div>
        <div className="Form-group">
          <label for="club-description">{t('club.description_label')}</label>
          <textarea
            id="club-description"
            className="FormControl"
            rows="4"
            maxlength="2000"
            value={this.description}
            oninput={(e) => (this.description = e.target.value)}
          />
        </div>
        <div className="Form-group">
          <Button type="submit" className="Button Button--primary Button--block" loading={this.loading} disabled={!this.name.trim()}>
            {t('club.save')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    const club = this.attrs.club;
    const attributes = { name: this.name.trim(), description: this.description.trim() || null };

    app
      .request({
        method: club ? 'PATCH' : 'POST',
        url: `${app.forum.attribute('apiUrl')}/clubs${club ? '/' + club.id() : ''}`,
        body: { data: { type: 'clubs', ...(club ? { id: club.id() } : {}), attributes } },
      })
      .then((payload) => {
        const saved = app.store.pushPayload(payload);
        this.hide();
        if (!club) m.route.set(app.route('club', { id: saved.id() }));
      }, this.loaded.bind(this));
  }
}
