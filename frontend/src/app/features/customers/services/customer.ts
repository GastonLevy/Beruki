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

  getByCode(customerCode: string) {
    return this.api.get(`/customers/${customerCode}`);
  }

  payMonthlyFee(customerCode: string) {
    return this.api.post(`/customers/${customerCode}/payments`, {});
  }

}
