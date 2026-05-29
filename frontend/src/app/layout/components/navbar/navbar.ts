import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Auth } from '../../../features/auth/services/auth';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.html',
  styleUrl: './navbar.scss',
})
export class Navbar {

  private readonly auth = inject(Auth);
  private readonly router = inject(Router);

  isAdmin(): boolean {
    return this.auth.isAdmin();
  }

  goToAdmin(): void {
    window.location.href = '/admin';
  }

  logout(): void {

    this.auth.logout();

    this.router.navigate(['/login']);

  }

}