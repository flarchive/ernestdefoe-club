import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import Select from 'flarum/common/components/Select';
import CommentPost from 'flarum/forum/components/CommentPost';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import { Club, Pick } from './models';
import ClubPanel from './components/ClubPanel';
import spoilerNotice from './components/SpoilerNotice';
import { replySection } from './schedule';
import { t, text, sectionName } from './util';

app.initializers.add('ernestdefoe-club', () => {
  app.store.models.clubs = Club;
  app.store.models['club-picks'] = Pick;

  app.routes.clubs = { path: '/clubs', component: () => import('./components/ClubsPage') };
  app.routes.club = { path: '/clubs/:id', component: () => import('./components/ClubPage') };

  Discussion.prototype.clubPick = Model.hasOne('clubPick');

  // A closed reply arrives without its text; never hand Mithril undefined.
  override(Post.prototype, 'contentHtml', function (original) {
    return this.attribute('clubClosed') ? '' : original();
  });

  extend(IndexSidebar.prototype, 'navItems', function (items) {
    items.add(
      'clubs',
      <LinkButton href={app.route('clubs')} icon="fas fa-book-open-reader">
        {t('clubs.nav')}
      </LinkButton>,
      84
    );
  });

  // The schedule and everyone's progress, beside a pick's discussion.
  extend(DiscussionPage.prototype, 'sidebarItems', function (items) {
    const pick = this.discussion && this.discussion.clubPick && this.discussion.clubPick();
    if (pick) items.add('clubPanel', <ClubPanel pick={pick} />, -50);
  });

  // Hosts make a linked discussion their club's pick from its controls.
  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    if (!app.session.user || !discussion.marqueeMedia || !discussion.marqueeMedia()) return;
    const pick = discussion.clubPick && discussion.clubPick();
    if (pick && !(pick.club() && pick.club().canEdit())) return;

    items.add(
      'clubPick',
      <Button icon="fas fa-book-open-reader" onclick={() => app.modal.show(() => import('./components/PickModal'), { discussion, pick })}>
        {t(pick ? 'controls.edit_pick' : 'controls.make_pick')}
      </Button>,
      -20
    );
  });

  // Each reply in a pick's discussion says which section it is about.
  extend('flarum/forum/components/ReplyComposer', 'headerItems', function (items) {
    const pick = this.attrs.discussion.clubPick && this.attrs.discussion.clubPick();
    const sections = (pick && pick.sections()) || [];
    if (!sections.length) return;

    const fields = this.composer.fields;
    if (fields.clubSection === undefined) fields.clubSection = replySection(pick.viewerDone() || 0, sections.length);

    const options = { 0: text('composer.general') };
    sections.forEach((s, i) => (options[i + 1] = sectionName(pick, i + 1)));

    items.add(
      'clubSection',
      <span className="ClubComposerSection">
        <label>{t('composer.about')}</label>
        <Select value={String(fields.clubSection || 0)} options={options} onchange={(value) => (fields.clubSection = Number(value) || null)} />
      </span>,
      -10
    );
  });

  extend('flarum/forum/components/ReplyComposer', 'data', function (data) {
    const pick = this.attrs.discussion.clubPick && this.attrs.discussion.clubPick();
    if (pick) data.clubSection = this.composer.fields.clubSection || null;
  });

  extend(CommentPost.prototype, 'headerItems', function (items) {
    const post = this.attrs.post;
    const section = post.attribute('clubSection');
    const pick = section && post.discussion() && post.discussion().clubPick && post.discussion().clubPick();
    if (pick) items.add('clubSection', <span className="ClubSectionBadge">{sectionName(pick, section)}</span>, -10);
  });

  extend(CommentPost.prototype, 'content', function (content) {
    if (this.attrs.post.attribute('clubClosed')) content.push(spoilerNotice(this.attrs.post));
  });

  extend(CommentPost.prototype, 'elementAttrs', function (attrs) {
    if (this.attrs.post.attribute('clubClosed')) attrs.className = (attrs.className || '') + ' Post--clubClosed';
  });
});
