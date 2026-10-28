import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';
import PageStructure from 'flarum/forum/components/PageStructure';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import extractText from 'flarum/common/utils/extractText';
import ClubEditModal from './ClubEditModal';
import { t } from '../util';

/** /clubs: every club, biggest first, with what each is on now. */
export default class ClubsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);
    this.clubs = null;
    app.history.push('clubs', extractText(t('clubs.title')));

    app.store.find('clubs', { page: { limit: 50 } }).then((clubs) => {
      this.clubs = clubs;
      m.redraw();
    });
  }

  oncreate(vnode) {
    super.oncreate(vnode);
    app.setTitle(extractText(t('clubs.title')));
  }

  view() {
    return (
      <PageStructure className="ClubsPage" sidebar={() => <IndexSidebar />}>
        <div className="IndexPage-toolbar ClubsPage-toolbar">
          <h2>{t('clubs.title')}</h2>
          {app.forum.attribute('canStartClub') ? (
            <Button className="Button Button--primary" icon="fas fa-plus" onclick={() => app.modal.show(ClubEditModal)}>
              {t('clubs.start')}
            </Button>
          ) : null}
        </div>
        {this.clubs === null ? (
          <LoadingIndicator display="block" />
        ) : this.clubs.length ? (
          <ul className="ClubCards">
            {this.clubs.map((club) => (
              <li>
                <Link className="ClubCard" href={app.route('club', { id: club.id() })}>
                  <span className="ClubCard-icon" aria-hidden="true">
                    <i className="fas fa-book-open-reader" />
                  </span>
                  <b>{club.name()}</b>
                  <small>
                    {t('clubs.members', { count: club.memberCount() })}
                    {club.viewerRole() ? <> · {t(`clubs.role_${club.viewerRole()}`)}</> : null}
                  </small>
                  {club.description() ? <p>{club.description()}</p> : null}
                </Link>
              </li>
            ))}
          </ul>
        ) : (
          <Placeholder text={t('clubs.none')} />
        )}
      </PageStructure>
    );
  }
}
