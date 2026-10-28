<?php

namespace Ernestdefoe\Club\Api;

use Ernestdefoe\Club\Pick;
use Ernestdefoe\Club\SpoilerGate;
use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Illuminate\Support\Arr;

class PostFields
{
    public function __construct(private SpoilerGate $gate, private TranslatorInterface $translator)
    {
    }

    public function __invoke(): array
    {
        return [
            // Which section of the club's schedule a reply is about.
            Schema\Integer::make('clubSection')
                ->nullable()
                ->get(fn (Post $post) => $post->getAttribute('club_section'))
                ->writable(fn (Post $post, Context $context) => $context->creating() || ($post instanceof CommentPost && $context->getActor()->can('edit', $post)))
                ->set(fn (Post $post, mixed $value, Context $context) => $this->setSection($post, $value, $context)),
            Schema\Boolean::make('clubClosed')
                ->visible(fn (Post $post) => $post->getAttribute('club_section') !== null)
                ->get(fn (Post $post, Context $context) => $this->gate->closed($post, $context->getActor())),
        ];
    }

    private function setSection(Post $post, mixed $value, Context $context): void
    {
        if ($value === null || $value === '' || (int) $value === 0) {
            $post->setAttribute('club_section', null);

            return;
        }

        $discussionId = $post->discussion_id ?: (int) Arr::get($context->body(), 'data.relationships.discussion.data.id');
        $pickId = $this->gate->pickFor((int) $discussionId);
        $count = $pickId ? (Pick::query()->find($pickId)?->sectionCount() ?? 0) : 0;
        $section = (int) $value;

        if ($section < 1 || $section > $count) {
            throw new ValidationException(['clubSection' => $this->translator->trans('ernestdefoe-club.api.bad_section')]);
        }

        $post->setAttribute('club_section', $section);
    }
}
