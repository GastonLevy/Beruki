import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../../auth/services/auth';

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
    private router: Router
  ) {}

  logout(): void {

    this.auth.logout();

    this.router.navigate(['/login']);

  }

}