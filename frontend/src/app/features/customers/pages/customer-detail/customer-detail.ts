import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Customer } from '../../services/customer';

@Component({
  selector: 'app-customer-detail',
  standalone: true,
  imports: [
    CommonModule
  ],
  templateUrl: './customer-detail.html',
  styleUrl: './customer-detail.scss',
})
export class CustomerDetail implements OnInit {

  customer: any = null;

  constructor(
    private route: ActivatedRoute,
    private customerService: Customer
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.customerService.getById(id).subscribe({
      next: (response: any) => {
        this.customer = response;
        console.log(response);
      },
      error: (error) => {
        console.error(error);
      }
    });
  }

}