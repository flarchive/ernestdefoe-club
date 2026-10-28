import Button from 'flarum/common/components/Button';
import { setProgress } from '../api';
import { t, sectionName } from '../util';

/** In place of a reply about a section the reader hasn't finished. */
export default function spoilerNotice(post) {
  const discussion = post.discussion();
  const pick = discussion && discussion.clubPick && discussion.clubPick();
  const section = post.attribute('clubSection');
  const member = pick && pick.club() && pick.club().viewerRole();

  return (
    <div className="ClubSpoiler">
      <i className="fas fa-eye-slash" aria-hidden="true" />
      <div>
        <b>{t('spoiler.title', { section: pick ? sectionName(pick, section) : section })}</b>
        <p>{t(member ? 'spoiler.body_member' : 'spoiler.body')}</p>
        {member ? (
          <Button className="Button" onclick={() => setProgress(pick, section).then(() => m.redraw())}>
            {t('spoiler.finished', { section: sectionName(pick, section) })}
          </Button>
        ) : null}
      </div>
    </div>
  );
}
