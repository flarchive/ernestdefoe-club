import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';
import posterUrl from 'ext:ernestdefoe/marquee/forum/utils/posterUrl';
import ClubPanel from './ClubPanel';
import ClubEditModal from './ClubEditModal';
import { setMembership } from '../api';
import { t, text, monthName } from '../util';

/** One club: what it's on this month, everyone's progress, and its past picks. */
export default class ClubPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    this.club = null;

    app.store.find('clubs', m.route.param('id')).then(
      (club) => {
        this.club = club;
        app.setTitle(club.name());
        const latest = (club.picks() || [])[0];
        // The member list comes with the pick's own page.
        if (latest) app.store.find('club-picks', latest.id()).then(() => m.redraw());
        m.redraw();
      },
      () => {
        this.missing = true;
        m.redraw();
      }
    );
  }

  view() {
    if (this.missing)
      return (
        <div className="container ClubPage">
          <Placeholder text={t('club.missing')} />
        </div>
      );
    if (!this.club) return <LoadingIndicator display="block" />;

    const club = this.club;
    const picks = (club.picks() || []).filter(Boolean);
    const [latest, ...past] = picks;

    return (
      <div className="container ClubPage">
        <header className="ClubPage-header">
          <h1>{club.name()}</h1>
          <p className="ClubPage-meta">{t('clubs.members', { count: club.memberCount() })}</p>
          {club.description() ? <p className="ClubPage-description">{club.description()}</p> : null}
          <div className="ClubPage-controls">{this.controls(club)}</div>
        </header>

        {latest ? (
          <section className="ClubPage-current">
            {this.pickCard(latest)}
            <ClubPanel pick={latest} all />
          </section>
        ) : (
          <Placeholder text={t(club.canEdit() ? 'club.no_picks_host' : 'club.no_picks')} />
        )}

        {past.length ? (
          <section className="ClubPage-past">
            <h2>{t('club.past')}</h2>
            <ul className="ClubPicks">
              {past.map((pick) => (
                <li>{this.pickCard(pick, true)}</li>
              ))}
            </ul>
          </section>
        ) : null}
      </div>
    );
  }

  pickCard(pick, small) {
    const media = pick.media();
    const discussion = pick.discussion();

    return (
      <Link className={small ? 'ClubPick ClubPick--small' : 'ClubPick'} href={discussion ? app.route.discussion(discussion) : undefined}>
        {media && media.poster() ? (
          <img src={posterUrl(media.poster(), small ? 'w185' : 'w342')} alt="" loading="lazy" decoding="async" />
        ) : (
          <span className="ClubPick-blank" />
        )}
        <span className="ClubPick-month">{monthName(pick.month())}</span>
        <b>{media ? media.title() : discussion ? discussion.title() : ''}</b>
        {small ? null : <span className="ClubPick-open">{t('club.open_discussion')}</span>}
      </Link>
    );
  }

  controls(club) {
    const items = [];

    if (app.session.user && club.viewerRole() !== 'host') {
      items.push(
        club.viewerRole() ? (
          <Button className="Button" icon="fas fa-user-minus" onclick={() => this.member(false)}>
            {t('club.leave')}
          </Button>
        ) : club.canJoin() ? (
          <Button className="Button Button--primary" icon="fas fa-user-plus" onclick={() => this.member(true)}>
            {t('club.join')}
          </Button>
        ) : null
      );
    }

    if (club.canEdit()) {
      items.push(
        <Button className="Button" icon="fas fa-pencil-alt" onclick={() => app.modal.show(ClubEditModal, { club })}>
          {t('club.edit')}
        </Button>,
        <Button className="Button Button--danger" icon="far fa-trash-alt" onclick={() => this.remove()}>
          {t('club.delete')}
        </Button>
      );
      items.push(<p className="ClubPage-hint">{t('club.how_to_pick')}</p>);
    }

    return items;
  }

  member(join) {
    setMembership(this.club, join)
      .then(() => {
        const latest = (this.club.picks() || [])[0];
        return latest ? app.store.find('club-picks', latest.id()) : null;
      })
      .then(() => m.redraw());
  }

  remove() {
    if (!confirm(text('club.delete_confirm', { name: this.club.name() }))) return;
    app.request({ method: 'DELETE', url: `${app.forum.attribute('apiUrl')}/clubs/${this.club.id()}` }).then(() => {
      app.store.remove(this.club);
      m.route.set(app.route('clubs'));
    });
  }
}
