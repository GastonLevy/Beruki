import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { BehaviorSubject, Observable, switchMap } from 'rxjs';
import { Customer } from '../../services/customer';

@Component({
  selector: 'app-customers',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    RouterLink
  ],
  templateUrl: './customers.html',
  styleUrl: './customers.scss',
})
export class Customers {

  search = '';

  private search$ = new BehaviorSubject<string>('');

  customers$: Observable<any> = this.search$.pipe(
    switchMap(search => {
      if (search.length < 2) {
        return this.customerService.getAll(1, 25, '');
      }

      return this.customerService.getAll(1, 25, search);
    })
  );

  constructor(private customerService: Customer) {}

  onSearch(): void {
    this.search$.next(this.search.trim());
  }

}