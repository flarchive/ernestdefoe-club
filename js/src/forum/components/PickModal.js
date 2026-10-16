import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Select from 'flarum/common/components/Select';
import { splitMonth } from '../schedule';
import { t, text } from '../util';

const MAX = 31;

/** Make a Marquee-linked discussion a club's pick for a month, with its schedule. */
export default class PickModal extends FormModal {
  oninit(vnode) {
    super.oninit(vnode);

    const pick = this.attrs.pick;
    this.clubs = null;
    this.clubId = pick ? pick.club().id() : null;
    this.month = (pick ? pick.month() : dayjs().format('YYYY-MM-DD')).slice(0, 7);
    this.sections = pick ? pick.sections().map((s) => ({ ...s })) : [];
    this.parts = 4;

    if (!pick) {
      app.store.find('clubs', { page: { limit: 50 } }).then((clubs) => {
        this.clubs = clubs.filter((club) => club.canEdit());
        if (this.clubs.length) this.clubId = this.clubs[0].id();
        m.redraw();
      });
    }
    if (!this.sections.length) this.split();
  }

  className() {
    return 'ClubPickModal';
  }

  title() {
    return t(this.attrs.pick ? 'pick.edit_title' : 'pick.title', { title: this.attrs.discussion.title() });
  }

  split() {
    const kind = this.attrs.discussion.marqueeMedia && this.attrs.discussion.marqueeMedia()?.kind();
    const unit = kind === 'book' ? 'part' : kind === 'tv' ? 'week' : 'part';
    this.sections = splitMonth(`${this.month}-01`, this.parts, (n) => text(`pick.default_${unit}`, { number: n }));
  }

  content() {
    if (!this.attrs.pick && this.clubs === null)
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    if (!this.attrs.pick && !this.clubs.length)
      return (
        <div className="Modal-body">
          <p>{t('pick.no_clubs')}</p>
        </div>
      );

    return (
      <div className="Modal-body">
        {this.attrs.pick ? null : (
          <div className="Form-group">
            <label>{t('pick.club_label')}</label>
            <Select
              value={this.clubId}
              options={Object.fromEntries(this.clubs.map((club) => [club.id(), club.name()]))}
              onchange={(value) => (this.clubId = value)}
            />
          </div>
        )}
        <div className="Form-group">
          <label for="club-pick-month">{t('pick.month_label')}</label>
          <input
            id="club-pick-month"
            className="FormControl"
            type="month"
            required
            value={this.month}
            oninput={(e) => (this.month = e.target.value)}
          />
        </div>

        <div className="Form-group">
          <label>{t('pick.schedule_label')}</label>
          <p className="helpText">{t('pick.schedule_help')}</p>
          <div className="ClubPickModal-split">
            <span>{t('pick.split_into')}</span>
            <input
              className="FormControl"
              type="number"
              min="1"
              max={MAX}
              value={this.parts}
              aria-label={text('pick.parts_label')}
              oninput={(e) => (this.parts = Math.max(1, Math.min(MAX, Number(e.target.value) || 1)))}
            />
            <Button className="Button" onclick={() => this.split()}>
              {t('pick.split_button')}
            </Button>
          </div>
          <ol className="ClubPickModal-sections">
            {this.sections.map((section, i) => (
              <li key={i}>
                <input
                  className="FormControl"
                  maxlength="100"
                  required
                  value={section.label}
                  aria-label={text('pick.section_label', { number: i + 1 })}
                  placeholder={text('pick.section_placeholder')}
                  oninput={(e) => (section.label = e.target.value)}
                />
                <input
                  className="FormControl"
                  type="date"
                  value={section.due || ''}
                  aria-label={text('pick.due_label', { number: i + 1 })}
                  oninput={(e) => (section.due = e.target.value || null)}
                />
                <Button
                  className="Button Button--icon"
                  icon="fas fa-times"
                  aria-label={text('pick.remove_section', { number: i + 1 })}
                  disabled={this.sections.length === 1}
                  onclick={() => this.sections.splice(i, 1)}
                />
              </li>
            ))}
          </ol>
          {this.sections.length < MAX ? (
            <Button className="Button Button--link" icon="fas fa-plus" onclick={() => this.sections.push({ label: '', due: null })}>
              {t('pick.add_section')}
            </Button>
          ) : null}
        </div>

        <div className="Form-group">
          <Button type="submit" className="Button Button--primary Button--block" loading={this.loading}>
            {t(this.attrs.pick ? 'pick.save' : 'pick.create')}
          </Button>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();
    this.loading = true;

    const pick = this.attrs.pick;
    const attributes = { month: `${this.month}-01`, sections: this.sections.map((s) => ({ label: s.label.trim(), due: s.due || null })) };
    const body = pick
      ? { data: { type: 'club-picks', id: pick.id(), attributes } }
      : {
          data: {
            type: 'club-picks',
            attributes,
            relationships: {
              club: { data: { type: 'clubs', id: this.clubId } },
              discussion: { data: { type: 'discussions', id: this.attrs.discussion.id() } },
            },
          },
        };

    app
      .request({ method: pick ? 'PATCH' : 'POST', url: `${app.forum.attribute('apiUrl')}/club-picks${pick ? '/' + pick.id() : ''}`, body })
      .then((payload) => {
        const saved = app.store.pushPayload(payload);
        // The discussion page reads its pick through the relationship.
        this.attrs.discussion.pushData({ relationships: { clubPick: { data: { type: 'club-picks', id: saved.id() } } } });
        // Members' progress comes with the pick's own page; fetch it once.
        return app.store.find('club-picks', saved.id());
      })
      .then(() => {
        this.hide();
        m.redraw();
      }, this.loaded.bind(this));
  }
}
