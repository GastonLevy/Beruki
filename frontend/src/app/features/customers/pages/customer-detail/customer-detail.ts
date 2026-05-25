import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { ActivatedRoute, RouterLink} from '@angular/router';
import { BehaviorSubject, Observable, switchMap } from 'rxjs';
import { Customer } from '../../services/customer';

@Component({
  selector: 'app-customer-detail',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink
  ],
  templateUrl: './customer-detail.html',
  styleUrl: './customer-detail.scss',
})
export class CustomerDetail {

  customer$: Observable<any>;

  private refreshCustomer$ = new BehaviorSubject<void>(undefined);

  private customerId: number;

  constructor(
    private route: ActivatedRoute,
    private customerService: Customer
  ) {
    this.customerId = Number(this.route.snapshot.paramMap.get('id'));

    this.customer$ = this.refreshCustomer$.pipe(
      switchMap(() => {
        return this.customerService.getById(this.customerId);
      })
    );
  }

  payMonthlyFee(): void {
    this.customerService.payMonthlyFee(this.customerId).subscribe({
      next: () => {
        this.refreshCustomer$.next();
      },
      error: (error) => {
        console.error(error);
      }
    });
  }

}