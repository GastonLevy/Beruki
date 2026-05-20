import { Injectable } from '@angular/core';
import { Api } from '../../../core/services/api';

@Injectable({
  providedIn: 'root',
})
export class Auth {

  constructor(private api: Api) {}

  login(username: string, password: string) {
    return this.api.post('/login', {
      username,
      password
    });
  }

  setToken(token: string): void {
    localStorage.setItem('token', token);
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  logout(): void {
    localStorage.removeItem('token');
  }

}