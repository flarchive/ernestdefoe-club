<?php

namespace Ernestdefoe\Club\Api;

use Carbon\Carbon;
use Ernestdefoe\Club\Pick;
use Ernestdefoe\Club\Viewer;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** A member says how many sections of a pick they have finished. */
class ProgressController implements RequestHandlerInterface
{
    public function __construct(private ConnectionInterface $db, private Viewer $viewer)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $pick = Pick::query()
            ->whereIn('discussion_id', Discussion::whereVisibleTo($actor)->select('discussions.id'))
            ->findOrFail((int) Arr::get($request->getQueryParams(), 'id'));

        if (! $this->viewer->role($actor, $pick->club_id)) {
            throw new PermissionDeniedException();
        }

        $done = max(0, min($pick->sectionCount(), (int) Arr::get((array) $request->getParsedBody(), 'done')));

        $this->db->table('club_progress')->upsert(
            [['pick_id' => $pick->id, 'user_id' => $actor->id, 'done' => $done, 'updated_at' => Carbon::now()]],
            ['pick_id', 'user_id'],
            ['done', 'updated_at']
        );
        $this->viewer->forget($actor);

        return new JsonResponse(['data' => ['type' => 'club-picks', 'id' => (string) $pick->id, 'attributes' => ['viewerDone' => $done]]]);
    }
}
