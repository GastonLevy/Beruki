import { Component, inject, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Navbar } from './layout/components/navbar/navbar';
import { Auth } from './features/auth/services/auth';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, Navbar],
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App {
  protected readonly title = signal('Beruki');

  private readonly auth = inject(Auth);

  get isAuthenticated(): boolean {
    return this.auth.isAuthenticated();
  }
}