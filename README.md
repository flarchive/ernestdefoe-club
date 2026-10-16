# Club

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/ernestdefoe/club.svg)](https://packagist.org/packages/ernestdefoe/club)

A [Flarum 2](https://flarum.org) companion to [Marquee](https://github.com/ernestdefoe/marquee) for book clubs and watch clubs. A club picks a title each month, splits it into a schedule ("chapters 1–5 by the 10th", "episodes 1–3 this week"), and discusses it section by section, with every reply about a section hidden from members who haven't finished it yet. Each member ticks off sections as they go, and everyone's progress shows beside the discussion.

![A club pick's discussion with its schedule and members' progress](https://raw.githubusercontent.com/ernestdefoe/club/main/.github/screenshots/discussion.jpg)

## Features

- **Clubs.** `/clubs` (linked from the forum's sidebar) lists them. Anyone with permission starts one and becomes its host; members join and leave with one click.
- **A monthly pick from Marquee.** A host opens any discussion Marquee links to a title and chooses **Make it a club pick** from its controls: which club, which month, and the schedule. **Even sections over the month** fills in a schedule to edit, or build one section by section with names and due dates.
- **Spoiler-gated replies.** Replying in a pick's discussion asks which section the reply is about (it starts on the one you last finished, or "everything so far"). A reply about a section you haven't finished arrives without its text, with **I've finished this section** in its place. The text is held back by the server, not hidden in the browser, so it isn't in the page at all. Your own replies are always open to you.
- **Progress for everyone.** The discussion's sidebar shows the schedule, your own progress bar and every member's. Tick a section off and the replies about it open.
- **A page per club**, with this month's pick, its schedule and members, and the past picks.
- **Translatable.** Every word on screen comes from the language files.

![A reply about a section the reader hasn't finished](https://raw.githubusercontent.com/ernestdefoe/club/main/.github/screenshots/spoiler.png)

![Making a discussion the club's pick](https://raw.githubusercontent.com/ernestdefoe/club/main/.github/screenshots/pick-modal.png)

![A club's page](https://raw.githubusercontent.com/ernestdefoe/club/main/.github/screenshots/club-page.jpg)

## Installation

```sh
composer require ernestdefoe/club
php flarum migrate
```

Enable **Marquee** first, then **Club**.

## Permissions

| Permission | Default | What it allows |
|---|---|---|
| Join book and watch clubs | Members | Joining, leaving and tracking progress |
| Start book and watch clubs | Moderators | Starting a club and hosting it |
| Run any club | Moderators | Editing or deleting any club and changing any pick |

A pick is visible to whoever can see its discussion, so a pick in a restricted tag stays restricted.

## Good to know

- **What is spoiler-gated.** Replies in the forum (the discussion, search results, mentions, the no-JavaScript page) for anyone who hasn't finished that section, staff included. Email notifications about new replies are not: someone who follows the discussion by email gets the text.
- **Lean on every page.** Ordinary discussions cost nothing: only replies that name a section are checked, and a page of them costs two small queries however many there are.
- **A club always has a host.** Its only host can't leave; they can delete the club, or a moderator can step in.
- **Schedules** have up to 31 sections.

## Updating

```sh
composer update ernestdefoe/club
php flarum migrate
php flarum cache:clear
```

## Links

- [Packagist](https://packagist.org/packages/ernestdefoe/club)
- [GitHub](https://github.com/ernestdefoe/club)
- [Marquee](https://github.com/ernestdefoe/marquee)
