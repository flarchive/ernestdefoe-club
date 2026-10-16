import app from 'flarum/forum/app';
import { apiUrl } from './util';

/** Join (true) or leave (false) a club; the club model is updated in place. */
export function setMembership(club, join) {
  return app
    .request({ method: join ? 'POST' : 'DELETE', url: apiUrl(`clubs/${club.id()}/membership`) })
    .then((payload) => club.pushAttributes(payload.data.attributes));
}

/**
 * Say how many sections of a pick you've finished. Updates the pick (and your
 * row in its member list), then reloads any replies that were closed to you,
 * since some of them may now be open.
 */
export function setProgress(pick, done) {
  return app.request({ method: 'POST', url: apiUrl(`club-picks/${pick.id()}/progress`), body: { done } }).then((payload) => {
    const me = app.session.user;
    const progress = (pick.progress() || []).map((row) => (me && row.id === me.id() ? { ...row, done: payload.data.attributes.viewerDone } : row));
    pick.pushAttributes({ viewerDone: payload.data.attributes.viewerDone, progress });

    const discussionId = pick.discussion() && pick.discussion().id();
    const closed = app.store
      .all('posts')
      .filter((post) => post.attribute('clubClosed') && post.discussion() && post.discussion().id() === discussionId)
      .map((post) => post.id());

    return closed.length ? app.store.find('posts', closed) : null;
  });
}
