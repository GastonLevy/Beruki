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
  isOpeningAdmin = false;

  isAdmin(): boolean {
    return this.auth.isAdmin();
  }

  goToAdmin(): void {
    if (this.isOpeningAdmin) {
      return;
    }

    this.isOpeningAdmin = true;

    this.auth.createAdminSession().subscribe({
      next: () => {
        this.navigateToAdmin();
      },
      error: () => {
        this.isOpeningAdmin = false;
      }
    });
  }

  logout(): void {
    this.auth.logoutAdminSession().subscribe({
      next: () => {
        this.finishLogout();
      },
      error: () => {
        this.finishLogout();
      }
    });
  }

  private finishLogout(): void {
    this.auth.logout();

    this.router.navigate(['/login']);
  }

  protected navigateToAdmin(): void {
    window.location.href = '/admin';
  }

}
