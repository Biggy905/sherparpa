import { createAction, props } from '@ngrx/store';
import { PaginatedResponse, Product } from '../../models/product.model';
import { ProductListParams } from '../../services/product.service';

export const loadProducts = createAction(
  '[Products] Load',
  props<ProductListParams>(),
);

export const loadProductsSuccess = createAction(
  '[Products] Load Success',
  props<{ response: PaginatedResponse<Product> }>(),
);

export const loadProductsFailure = createAction(
  '[Products] Load Failure',
  props<{ error: string }>(),
);
