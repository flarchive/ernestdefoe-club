import test from 'node:test';
import assert from 'node:assert/strict';
import { percent, splitMonth, current, replySection } from '../src/forum/schedule.js';

test('progress as a share', () => {
  assert.equal(percent(0, 4), 0);
  assert.equal(percent(1, 3), 33);
  assert.equal(percent(4, 4), 100);
  assert.equal(percent(9, 4), 100);
  assert.equal(percent(2, 0), 0);
});

test('a month split into even sections, the last due on its last day', () => {
  const parts = splitMonth('2026-02-01', 4, (n) => `Week ${n}`);
  assert.deepEqual(
    parts.map((p) => p.due),
    ['2026-02-07', '2026-02-14', '2026-02-21', '2026-02-28']
  );
  assert.equal(parts[3].label, 'Week 4');
  assert.equal(splitMonth('2026-10-01', 1, String)[0].due, '2026-10-31');
  assert.equal(splitMonth('2026-12-01', 31, String)[30].due, '2026-12-31');
});

test('where a member is', () => {
  assert.equal(current(0, 3), 1);
  assert.equal(current(2, 3), 3);
  assert.equal(current(3, 3), 3);
});

test('a reply defaults to what the member last finished', () => {
  assert.equal(replySection(0, 3), 1);
  assert.equal(replySection(2, 3), 2);
  assert.equal(replySection(5, 3), 3);
  assert.equal(replySection(1, 0), null);
});
