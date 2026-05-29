import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { Auth } from '../../features/auth/services/auth';

export const guestGuard: CanActivateFn = () => {

  const auth = inject(Auth);
  const router = inject(Router);

  console.log('guestGuard ejecutado');
  console.log('isAuthenticated:', auth.isAuthenticated());
  console.log('isAdmin:', auth.isAdmin());

  if (!auth.isAuthenticated()) {
    return true;
  }

  if (auth.isAdmin()) {
    window.location.href = '/admin';
    return false;
  }

  return router.createUrlTree(['/customers']);

};