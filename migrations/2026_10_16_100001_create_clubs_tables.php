<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('clubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        $schema->create('club_members', function (Blueprint $table) {
            $table->unsignedInteger('club_id');
            $table->unsignedInteger('user_id');
            // host | member
            $table->string('role', 8)->default('member');
            $table->dateTime('joined_at');

            $table->primary(['club_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $schema->create('club_picks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('club_id');
            $table->unsignedInteger('discussion_id');
            $table->unsignedInteger('media_id');
            // The first day of the month it is the pick for.
            $table->date('month');
            // [{ label, due }] in order; a member's progress counts them.
            $table->json('sections');
            $table->dateTime('created_at');

            $table->unique('discussion_id');
            $table->index(['club_id', 'month']);
            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->foreign('discussion_id')->references('id')->on('discussions')->cascadeOnDelete();
            $table->foreign('media_id')->references('id')->on('marquee_media')->cascadeOnDelete();
        });

        $schema->create('club_progress', function (Blueprint $table) {
            $table->unsignedInteger('pick_id');
            $table->unsignedInteger('user_id');
            // How many sections the member has finished.
            $table->unsignedTinyInteger('done')->default(0);
            $table->dateTime('updated_at');

            $table->primary(['pick_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('pick_id')->references('id')->on('club_picks')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $schema->table('posts', function (Blueprint $table) {
            // Which section of a club pick a reply discusses; null = general.
            $table->unsignedTinyInteger('club_section')->nullable();
        });
    },
    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropColumn('club_section');
        });
        $schema->dropIfExists('club_progress');
        $schema->dropIfExists('club_picks');
        $schema->dropIfExists('club_members');
        $schema->dropIfExists('clubs');
    },
];
