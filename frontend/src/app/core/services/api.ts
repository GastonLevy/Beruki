import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class Api {

  private readonly baseUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  post<T>(endpoint: string, body: unknown) {
    return this.http.post<T>(`${this.baseUrl}${endpoint}`, body);
  }

  get<T>(endpoint: string) {
    return this.http.get<T>(`${this.baseUrl}${endpoint}`);
  }

}