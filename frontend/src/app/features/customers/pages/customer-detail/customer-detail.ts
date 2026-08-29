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

  private customerCode: string;

  constructor(
    private route: ActivatedRoute,
    private customerService: Customer
  ) {
    this.customerCode = this.route.snapshot.paramMap.get('customerCode') ?? '';

    this.customer$ = this.refreshCustomer$.pipe(
      switchMap(() => {
        return this.customerService.getByCode(this.customerCode);
      })
    );
  }

  payMonthlyFee(): void {
    this.customerService.payMonthlyFee(this.customerCode).subscribe({
      next: () => {
        this.refreshCustomer$.next();
      },
      error: (error) => {
        console.error(error);
      }
    });
  }

}
