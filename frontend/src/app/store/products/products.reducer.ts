import { createReducer, on } from '@ngrx/store';
import * as ProductsActions from './products.actions';
import { initialProductsState } from './products.state';

export const productsReducer = createReducer(
  initialProductsState,

  on(ProductsActions.loadProducts, (state, { page, limit }) => ({
    ...state,
    loading: true,
    error: null,
    page,
    limit,
  })),

  on(ProductsActions.loadProductsSuccess, (state, { response }) => ({
    ...state,
    items: response.items,
    page: response.page,
    limit: response.limit,
    total: response.total,
    totalPages: response.total_pages,
    loading: false,
    error: null,
  })),

  on(ProductsActions.loadProductsFailure, (state, { error }) => ({
    ...state,
    loading: false,
    error,
  })),
);
