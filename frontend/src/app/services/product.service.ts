import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { PaginatedResponse, Product } from '../models/product.model';

export interface ProductListParams {
  page: number;
  limit: number;
  name?: string;
  priceFrom?: number;
  priceTo?: number;
}

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

  list(params: ProductListParams): Observable<PaginatedResponse<Product>> {
    let httpParams = new HttpParams()
      .set('page', params.page)
      .set('limit', params.limit);

    if (params.name?.trim()) {
      httpParams = httpParams.set('name', params.name.trim());
    }
    if (params.priceFrom != null) {
      httpParams = httpParams.set('price_from', params.priceFrom);
    }
    if (params.priceTo != null) {
      httpParams = httpParams.set('price_to', params.priceTo);
    }

    return this.http.get<PaginatedResponse<Product>>(
      `${this.baseUrl}/products`,
      { params: httpParams },
    );
  }

  detail(id: number): Observable<Product> {
    return this.http.get<Product>(`${this.baseUrl}/products/${id}`);
  }
}
