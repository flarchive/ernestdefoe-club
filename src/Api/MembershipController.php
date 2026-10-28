<?php

namespace Ernestdefoe\Club\Api;

use Carbon\Carbon;
use Ernestdefoe\Club\Club;
use Ernestdefoe\Club\Viewer;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** POST joins a club, DELETE leaves it. */
class MembershipController implements RequestHandlerInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private Viewer $viewer,
        private TranslatorInterface $translator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $club = Club::query()->findOrFail((int) Arr::get($request->getQueryParams(), 'id'));
        $members = $this->db->table('club_members')->where('club_id', $club->id);
        $role = $this->viewer->role($actor, $club->id);

        if ($request->getMethod() === 'POST') {
            $actor->assertCan('ernestdefoe-club.join');

            if (! $role) {
                $this->db->table('club_members')->insertOrIgnore(['club_id' => $club->id, 'user_id' => $actor->id, 'role' => 'member', 'joined_at' => Carbon::now()]);
            }
        } elseif ($role) {
            // A club always keeps someone to run it.
            if ($role === 'host' && (clone $members)->where('role', 'host')->count() === 1) {
                throw new ValidationException(['club' => $this->translator->trans('ernestdefoe-club.api.last_host')]);
            }

            (clone $members)->where('user_id', $actor->id)->delete();
        }

        $club->member_count = (clone $members)->count();
        $club->save();
        $this->viewer->forget($actor);

        return new JsonResponse([
            'data' => [
                'type' => 'clubs',
                'id' => (string) $club->id,
                'attributes' => ['memberCount' => $club->member_count, 'viewerRole' => $this->viewer->role($actor, $club->id)],
            ],
        ]);
    }
}
