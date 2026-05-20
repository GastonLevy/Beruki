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

}