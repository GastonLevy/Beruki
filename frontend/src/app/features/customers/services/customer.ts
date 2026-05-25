import { Injectable } from '@angular/core';
import { Api } from '../../../core/services/api';

@Injectable({
  providedIn: 'root',
})
export class Customer {

  constructor(private api: Api) {}

  getAll(page: number = 1, limit: number = 25, search: string = '') {
    return this.api.get(
      `/customers?page=${page}&limit=${limit}&search=${search}`
    );
  }

  getById(id: number) {
    return this.api.get(`/customers/${id}`);
  }

  payMonthlyFee(id: number) {
    return this.api.post(`/customers/${id}/payments`, {});
  }

}