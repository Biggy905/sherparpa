import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';
import {
  PaginatedResponse,
  Product,
  ProductImage,
  RawPaginatedResponse,
  RawProduct,
} from '../models/product.model';

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

    return this.http
      .get<RawPaginatedResponse<RawProduct>>(`${this.baseUrl}/products`, {
        params: httpParams,
      })
      .pipe(
        map((res) => ({
          items: res.items.map((p) => this.normalizeProduct(p)),
          page: res.page,
          limit: res.limit,
          total: res.total,
          total_pages: res.total_pages,
        })),
      );
  }

  detail(id: number): Observable<Product> {
    return this.http
      .get<RawProduct>(`${this.baseUrl}/products/${id}`)
      .pipe(map((p) => this.normalizeProduct(p)));
  }

  private normalizeProduct(raw: RawProduct): Product {
    return {
      id: raw.id,
      external_code: raw.external_code,
      name: raw.name,
      description: raw.description,
      price: this.toNumber(raw.price),
      discount: this.toNumber(raw.discount),
      attributes: raw.attributes ?? {},
      images: (raw.images ?? []).map((i) => this.normalizeImage(i)),
    };
  }

  private normalizeImage(img: ProductImage): ProductImage {
    return {
      url: img.url,
      path: img.path ?? null,
      type: img.type,
      status: img.status ?? null,
      error: img.error ?? null,
    };
  }

  private toNumber(value: string | number | null | undefined): number {
    if (value == null) return 0;
    const n = typeof value === 'number' ? value : parseFloat(value);
    return Number.isFinite(n) ? n : 0;
  }
}
