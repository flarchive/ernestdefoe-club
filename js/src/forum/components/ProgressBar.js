import { percent } from '../schedule';

/** A thin bar, filled to how far through a pick someone is. */
export default function progressBar(done, count, label) {
  const value = percent(done, count);

  return (
    <span className="ClubBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow={value} aria-label={label}>
      <span style={{ width: `${value}%` }} />
    </span>
  );
}
