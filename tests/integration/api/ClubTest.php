<?php

namespace Ernestdefoe\Club\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class ClubTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const SECTIONS = [
        ['label' => 'Chapters 1–5', 'due' => '2026-10-10'],
        ['label' => 'Chapters 6–10', 'due' => '2026-10-20'],
        ['label' => 'Chapters 11–15', 'due' => null],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'ernestdefoe-marquee', 'ernestdefoe-club');
        // Several replies in a row would trip the flood guard.
        $this->extend((new Extend\ThrottleApi())->remove('postTimeout'));

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'host', 'email' => 'host@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'reader', 'email' => 'reader@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'outsider', 'email' => 'outsider@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 3],
                ['user_id' => 4, 'group_id' => 3],
                ['user_id' => 5, 'group_id' => 3],
            ],
            'marquee_media' => [
                ['id' => 1, 'kind' => 'book', 'external_id' => 'OL1W', 'title' => 'Dune', 'year' => 1965, 'poster' => 'ol:b:1', 'fetched_at' => $now, 'data' => '{}'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Dune (1965)', 'user_id' => 3, 'created_at' => $now, 'comment_count' => 4, 'marquee_media_id' => 1, 'first_post_id' => 1, 'last_post_number' => 4],
                ['id' => 2, 'title' => 'Not linked', 'user_id' => 3, 'created_at' => $now, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Hidden Dune', 'user_id' => 1, 'created_at' => $now, 'comment_count' => 1, 'marquee_media_id' => 1, 'hidden_at' => $now],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'number' => 1, 'created_at' => $now, 'content' => '<t><p>Welcome</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'number' => 2, 'created_at' => $now, 'content' => '<t><p>Paul meets the worm</p></t>', 'club_section' => 1],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'number' => 3, 'created_at' => $now, 'content' => '<t><p>The ending twist</p></t>', 'club_section' => 3],
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'number' => 4, 'created_at' => $now, 'content' => '<t><p>My own late take</p></t>', 'club_section' => 3],
            ],
        ]);
    }

    private function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    private function call(string $method, string $path, ?int $as, ?array $json = null): ResponseInterface
    {
        $options = $as ? ['authenticatedAs' => $as] : [];
        if ($json !== null) {
            $options['json'] = $json;
        }

        return $this->send($this->request($method, $path, $options));
    }

    private function createClub(int $as = 3): ResponseInterface
    {
        return $this->call('POST', '/api/clubs', $as, ['data' => ['type' => 'clubs', 'attributes' => ['name' => 'Sci-fi readers', 'description' => 'One classic a month.']]]);
    }

    private function createPick(int $club, int $discussion, int $as = 3, array $sections = self::SECTIONS): ResponseInterface
    {
        return $this->call('POST', '/api/club-picks', $as, ['data' => [
            'type' => 'club-picks',
            'attributes' => ['month' => '2026-10-14', 'sections' => $sections],
            'relationships' => [
                'club' => ['data' => ['type' => 'clubs', 'id' => (string) $club]],
                'discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussion]],
            ],
        ]]);
    }

    /** A club hosted by user 3 with a pick on discussion 1, joined by user 4. */
    private function clubWithPick(): array
    {
        $this->database()->table('group_permission')->insert(['group_id' => 3, 'permission' => 'ernestdefoe-club.create']);

        $club = (int) $this->json($this->createClub())['data']['id'];
        $pick = (int) $this->json($this->createPick($club, 1))['data']['id'];
        $this->call('POST', "/api/clubs/$club/membership", 4);

        return [$club, $pick];
    }

    private function postHtml(int $postId, ?int $as): ?string
    {
        $body = $this->json($this->call('GET', "/api/posts/$postId", $as));

        return $body['data']['attributes']['contentHtml'] ?? null;
    }

    #[Test]
    public function only_permitted_members_start_clubs(): void
    {
        $this->assertSame(403, $this->createClub(4)->getStatusCode());
        $this->assertContains($this->call('POST', '/api/clubs', null, ['data' => ['type' => 'clubs', 'attributes' => ['name' => 'X']]])->getStatusCode(), [400, 401]);

        $created = $this->createClub(1);
        $this->assertSame(201, $created->getStatusCode(), (string) $created->getBody());
        $this->assertSame('host', $this->json($created)['data']['attributes']['viewerRole']);
        $this->assertSame(1, $this->json($created)['data']['attributes']['memberCount']);
    }

    #[Test]
    public function members_join_and_leave_but_the_last_host_stays(): void
    {
        [$club] = $this->clubWithPick();

        $show = $this->json($this->call('GET', "/api/clubs/$club", 4));
        $this->assertSame('member', $show['data']['attributes']['viewerRole']);
        $this->assertSame(2, $show['data']['attributes']['memberCount']);

        $this->assertSame(200, $this->call('DELETE', "/api/clubs/$club/membership", 4)->getStatusCode());
        $this->assertNull($this->json($this->call('GET', "/api/clubs/$club", 4))['data']['attributes']['viewerRole']);

        $this->assertSame(422, $this->call('DELETE', "/api/clubs/$club/membership", 3)->getStatusCode());
    }

    #[Test]
    public function only_hosts_pick_and_only_linked_visible_discussions(): void
    {
        [$club] = $this->clubWithPick();

        // A member is not a host.
        $this->assertSame(403, $this->createPick($club, 2, 4)->getStatusCode());
        // No Marquee title on it.
        $this->assertSame(422, $this->createPick($club, 2)->getStatusCode());
        // Already this club's pick.
        $this->assertSame(422, $this->createPick($club, 1)->getStatusCode());
        // A discussion the host can't see does not exist for them.
        $this->assertContains($this->createPick($club, 3)->getStatusCode(), [404, 422]);
        // Schedules need labels.
        $this->assertSame(422, $this->createPick($club, 1, 3, [['label' => '  ']])->getStatusCode());
        $this->assertSame(422, $this->createPick($club, 1, 3, [])->getStatusCode());
    }

    #[Test]
    public function the_discussion_carries_its_pick_and_everyones_progress(): void
    {
        [$club, $pick] = $this->clubWithPick();
        $this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => 2]);

        $body = $this->json($this->call('GET', '/api/discussions/1', 4));
        $included = collect($body['included']);
        $pickData = $included->first(fn ($r) => $r['type'] === 'club-picks');

        $this->assertSame((string) $pick, $body['data']['relationships']['clubPick']['data']['id']);
        $this->assertSame(2, $pickData['attributes']['viewerDone']);
        $this->assertSame('2026-10-01', substr($pickData['attributes']['month'], 0, 10));
        $this->assertCount(3, $pickData['attributes']['sections']);
        $this->assertSame(['reader' => 2, 'host' => 0], array_column($pickData['attributes']['progress'], 'done', 'username'));
        $this->assertNotNull($included->first(fn ($r) => $r['type'] === 'clubs'));
    }

    #[Test]
    public function progress_is_for_members_and_clamped(): void
    {
        [, $pick] = $this->clubWithPick();

        $this->assertSame(403, $this->call('POST', "/api/club-picks/$pick/progress", 5, ['done' => 1])->getStatusCode());
        $this->assertSame(3, $this->json($this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => 99]))['data']['attributes']['viewerDone']);
        $this->assertSame(0, $this->json($this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => -4]))['data']['attributes']['viewerDone']);
    }

    #[Test]
    public function replies_about_unread_sections_arrive_without_text(): void
    {
        [, $pick] = $this->clubWithPick();

        // A guest and an outsider have read nothing: section posts are closed, the opener is not.
        $this->assertNull($this->postHtml(2, null));
        $this->assertNull($this->postHtml(3, 5));
        $this->assertStringContainsString('Welcome', (string) $this->postHtml(1, null));

        // The reader finishes section 1: its replies open, section 3 stays shut…
        $this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => 1]);
        $this->assertStringContainsString('worm', (string) $this->postHtml(2, 4));
        $this->assertNull($this->postHtml(3, 4));
        // …except their own reply, which is always open to them.
        $this->assertStringContainsString('late take', (string) $this->postHtml(4, 4));

        $closed = $this->json($this->call('GET', '/api/posts/3', 4))['data']['attributes'];
        $this->assertTrue($closed['clubClosed']);
        $this->assertSame(3, $closed['clubSection']);

        // The whole discussion's post list is gated the same way.
        $posts = $this->json($this->send($this->request('GET', '/api/posts', ['authenticatedAs' => 4])->withQueryParams(['filter' => ['discussion' => '1']])));
        $html = array_column(array_column($posts['data'], 'attributes', 'id'), 'contentHtml', 'number');
        $this->assertArrayHasKey(2, $html);
        $this->assertArrayNotHasKey(3, $html);
    }

    #[Test]
    public function gated_posts_cost_no_more_queries_in_bulk(): void
    {
        [, $pick] = $this->clubWithPick();
        $this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => 1]);

        $now = Carbon::now();
        for ($n = 5; $n < 25; $n++) {
            $this->database()->table('posts')->insert(['id' => $n, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'number' => $n, 'created_at' => $now, 'content' => "<t><p>Reply $n</p></t>", 'club_section' => 1 + $n % 3]);
        }

        $queries = 0;
        $this->database()->listen(function () use (&$queries) {
            $queries++;
        });
        $count = function (int $limit) use (&$queries): int {
            $queries = 0;
            $this->send($this->request('GET', '/api/posts', ['authenticatedAs' => 4])->withQueryParams(['filter' => ['discussion' => '1'], 'page' => ['offset' => 1, 'limit' => $limit]]));

            return $queries;
        };

        $count(2);
        $this->assertSame($count(2), $count(20));
    }

    #[Test]
    public function a_reply_names_a_section_that_exists(): void
    {
        [$club] = $this->clubWithPick();

        $reply = fn (mixed $section, int $discussion = 1) => $this->call('POST', '/api/posts', 4, ['data' => [
            'type' => 'posts',
            'attributes' => ['content' => 'Thoughts on this part.', 'clubSection' => $section],
            'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussion]]],
        ]]);

        $ok = $reply(2);
        $this->assertSame(201, $ok->getStatusCode(), (string) $ok->getBody());
        $this->assertSame(2, $this->json($ok)['data']['attributes']['clubSection']);

        $this->assertSame(422, $reply(4)->getStatusCode());
        // Not a pick: no sections at all.
        $this->assertSame(422, $reply(1, 2)->getStatusCode());
        // General replies need no section.
        $this->assertSame(201, $reply(null)->getStatusCode());
    }

    #[Test]
    public function hosts_edit_and_others_cannot(): void
    {
        [$club, $pick] = $this->clubWithPick();

        $patch = fn (int $as) => $this->call('PATCH', "/api/club-picks/$pick", $as, ['data' => ['type' => 'club-picks', 'id' => (string) $pick, 'attributes' => ['sections' => [['label' => 'All of it', 'due' => null]]]]]);
        $this->assertSame(403, $patch(4)->getStatusCode());
        $this->assertSame(200, $patch(3)->getStatusCode());

        $this->assertSame(403, $this->call('PATCH', "/api/clubs/$club", 4, ['data' => ['type' => 'clubs', 'id' => (string) $club, 'attributes' => ['name' => 'Mine']]])->getStatusCode());
        $this->assertSame(403, $this->call('DELETE', "/api/clubs/$club", 5)->getStatusCode());
        // Moderators (the admin here) can step in.
        $this->assertSame(204, $this->call('DELETE', "/api/clubs/$club", 1)->getStatusCode());
    }

    #[Test]
    public function a_pick_on_a_hidden_discussion_is_hidden_too(): void
    {
        [$club, $pick] = $this->clubWithPick();
        $this->database()->table('discussions')->where('id', 1)->update(['hidden_at' => Carbon::now()]);

        $this->assertSame(404, $this->call('GET', "/api/club-picks/$pick", 5)->getStatusCode());
        $show = $this->json($this->call('GET', "/api/clubs/$club", 5));
        $this->assertSame([], $show['data']['relationships']['picks']['data']);
        $this->assertSame(404, $this->call('POST', "/api/club-picks/$pick/progress", 4, ['done' => 1])->getStatusCode());
    }
}
