export interface ProductImage {
  url: string;
  path: string | null;
  type: 'photo' | 'packaging';
}

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

export interface PaginatedResponse<T> {
  items: T[];
  page: number;
  limit: number;
  total: number;
  total_pages: number;
}
