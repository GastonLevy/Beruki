import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../../auth/services/auth';
import { Customer } from '../../../customers/services/customer';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss',
})
export class Dashboard {

  constructor(
    private auth: Auth,
    private router: Router,
    private customer: Customer
  ) {}

  loadCustomers(): void {
    this.customer.getAll().subscribe({
      next: (response) => {
        console.log(response);
      },
      error: (error) => {
        console.error(error);
      }
    });
  }

  logout(): void {

    this.auth.logout();

    this.router.navigate(['/login']);

  }

}