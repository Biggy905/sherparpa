import { Product } from '../../models/product.model';

export interface ProductsState {
  items: Product[];
  page: number;
  limit: number;
  total: number;
  totalPages: number;
  loading: boolean;
  error: string | null;
}

export const initialProductsState: ProductsState = {
  items: [],
  page: 1,
  limit: 20,
  total: 0,
  totalPages: 0,
  loading: false,
  error: null,
};
