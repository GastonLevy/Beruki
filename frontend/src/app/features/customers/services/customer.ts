import { Injectable } from '@angular/core';
import { Api } from '../../../core/services/api';

@Injectable({
  providedIn: 'root',
})
export class Customer {

  constructor(private api: Api) {}

  getAll() {
    return this.api.get('/customers');
  }

}