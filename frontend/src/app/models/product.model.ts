export type ProductImageType = 'photo' | 'packaging';

export interface ProductImage {
  url: string;
  path: string | null;
  type: ProductImageType;
  status?: string | null;
  error?: string | null;
}

/** То, что реально приходит с бэкенда: числа строками. */
export interface RawProduct {
  id: number;
  external_code: string;
  name: string;
  description: string | null;
  price: string;
  discount: string;
  attributes: Record<string, string> | null;
  images: ProductImage[] | null;
}

/** Нормализованная модель для UI: числа — числа. */
export interface Product {
  id: number;
  external_code: string;
  name: string;
  description: string | null;
  price: number;
  discount: number;
  attributes: Record<string, string>;
  images: ProductImage[];
}

export interface RawPaginatedResponse<T> {
  items: T[];
  page: number;
  limit: number;
  total: number;
  total_pages: number;
}

export interface PaginatedResponse<T> {
  items: T[];
  page: number;
  limit: number;
  total: number;
  total_pages: number;
}
