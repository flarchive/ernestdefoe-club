import { Admin } from 'flarum/common/extenders';
import app from 'flarum/admin/app';

const t = (key) => app.translator.trans(`ernestdefoe-club.admin.${key}`);

// Callbacks, not values: this module runs before the app has booted.
export default [
  new Admin()
    .permission(() => ({ icon: 'fas fa-user-plus', label: t('permission_join'), permission: 'ernestdefoe-club.join' }), 'reply', 90)
    .permission(() => ({ icon: 'fas fa-book-open-reader', label: t('permission_create'), permission: 'ernestdefoe-club.create' }), 'start', 90)
    .permission(() => ({ icon: 'fas fa-users-gear', label: t('permission_moderate'), permission: 'ernestdefoe-club.moderate' }), 'moderate', 90),
];
