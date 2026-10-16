import Model from 'flarum/common/Model';

export class Club extends Model {}

Object.assign(Club.prototype, {
  name: Model.attribute('name'),
  description: Model.attribute('description'),
  memberCount: Model.attribute('memberCount'),
  createdAt: Model.attribute('createdAt', Model.transformDate),
  viewerRole: Model.attribute('viewerRole'),
  canEdit: Model.attribute('canEdit'),
  canJoin: Model.attribute('canJoin'),
  user: Model.hasOne('user'),
  picks: Model.hasMany('picks'),
});

export class Pick extends Model {}

Object.assign(Pick.prototype, {
  month: Model.attribute('month'),
  sections: Model.attribute('sections'),
  viewerDone: Model.attribute('viewerDone'),
  progress: Model.attribute('progress'),
  club: Model.hasOne('club'),
  discussion: Model.hasOne('discussion'),
  media: Model.hasOne('media'),
});
