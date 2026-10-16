<?php

use Ernestdefoe\Club\Access\ClubPolicy;
use Ernestdefoe\Club\Api;
use Ernestdefoe\Club\Club;
use Ernestdefoe\Club\ClubServiceProvider;
use Ernestdefoe\Club\Pick;
use Ernestdefoe\Club\SpoilerGate;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Resource\PostResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Tobyz\JsonApiServer\Schema\Field\Field;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less')
        ->route('/clubs', 'clubs')
        ->route('/clubs/{id:\d+}', 'club'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(ClubServiceProvider::class),

    (new Extend\Model(Discussion::class))
        ->hasOne('clubPick', Pick::class, 'discussion_id'),

    (new Extend\Model(Post::class))
        ->cast('club_section', 'integer'),

    new Extend\ApiResource(Api\ClubResource::class),
    new Extend\ApiResource(Api\PickResource::class),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToOne::make('clubPick')
                ->type('club-picks')
                ->includable(),
        ])
        ->endpoint(Endpoint\Show::class, fn (Endpoint\Show $endpoint) => $endpoint->addDefaultInclude(['clubPick', 'clubPick.club'])),

    (new Extend\ApiResource(PostResource::class))
        ->fields(Api\PostFields::class)
        // A reply about a section the viewer hasn't finished arrives without its text.
        ->field('contentHtml', fn (Field $field) => $field->visible(
            fn (Post $post, Context $context) => $post instanceof CommentPost && ! resolve(SpoilerGate::class)->closed($post, $context->getActor())
        )),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canStartClub')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('ernestdefoe-club.create')),
        ]),

    (new Extend\Routes('api'))
        ->post('/clubs/{id:\d+}/membership', 'clubs.join', Api\MembershipController::class)
        ->delete('/clubs/{id:\d+}/membership', 'clubs.leave', Api\MembershipController::class)
        ->post('/club-picks/{id:\d+}/progress', 'club-picks.progress', Api\ProgressController::class),

    (new Extend\Policy())
        ->modelPolicy(Club::class, ClubPolicy::class)
        ->modelPolicy(Pick::class, ClubPolicy::class),
];
