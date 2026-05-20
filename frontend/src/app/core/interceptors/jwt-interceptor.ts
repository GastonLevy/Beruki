import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Auth } from '../../features/auth/services/auth';

export const jwtInterceptor: HttpInterceptorFn = (req, next) => {

  const auth = inject(Auth);

  const token = auth.getToken();

  if (!token) {
    return next(req);
  }

  const clonedRequest = req.clone({
    setHeaders: {
      Authorization: `Bearer ${token}`
    }
  });

  return next(clonedRequest);

};