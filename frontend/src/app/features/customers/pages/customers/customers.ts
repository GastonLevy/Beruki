import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Customer } from '../../services/customer';

@Component({
  selector: 'app-customers',
  standalone: true,
  imports: [
    FormsModule,
    RouterLink
  ],
  templateUrl: './customers.html',
  styleUrl: './customers.scss',
})
export class Customers implements OnInit {

  customers: any[] = [];

  search = '';

  constructor(private customerService: Customer) {}

  ngOnInit(): void {}

  loadCustomers(): void {

    this.customerService.getAll(
      1,
      25,
      this.search
    ).subscribe({
      next: (response: any) => {

        this.customers = response.data ?? [];

        console.log(response);

      },
      error: (error) => {
        console.error(error);
      }
    });

  }

  onSearch(): void {

    if (this.search.trim().length < 2) {

      this.customers = [];

      return;

    }

    this.loadCustomers();

  }

}