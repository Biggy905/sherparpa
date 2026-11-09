import { createFeatureSelector, createSelector } from '@ngrx/store';
import { ProductsState } from './products.state';

export const selectProductsState =
  createFeatureSelector<ProductsState>('products');

export const selectAllProducts = createSelector(
  selectProductsState,
  (state: ProductsState) => state.items,
);

export const selectProductsLoading = createSelector(
  selectProductsState,
  (state: ProductsState) => state.loading,
);

export const selectProductsError = createSelector(
  selectProductsState,
  (state: ProductsState) => state.error,
);

export const selectCurrentPage = createSelector(
  selectProductsState,
  (state: ProductsState) => state.page,
);

export const selectPageSize = createSelector(
  selectProductsState,
  (state: ProductsState) => state.limit,
);

export const selectTotalItems = createSelector(
  selectProductsState,
  (state: ProductsState) => state.total,
);

export const selectTotalPages = createSelector(
  selectProductsState,
  (state: ProductsState) => state.totalPages,
);
