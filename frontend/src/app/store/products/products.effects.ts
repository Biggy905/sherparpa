import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { catchError, map, of, switchMap } from 'rxjs';
import { ProductService } from '../../services/product.service';
import * as ProductsActions from './products.actions';

@Injectable()
export class ProductsEffects {
  private readonly actions$ = inject(Actions);
  private readonly productService = inject(ProductService);

  loadProducts$ = createEffect(() =>
    this.actions$.pipe(
      ofType(ProductsActions.loadProducts),
      switchMap(({ page, limit, name, priceFrom, priceTo }) =>
        this.productService.list({ page, limit, name, priceFrom, priceTo }).pipe(
          map((response) => ProductsActions.loadProductsSuccess({ response })),
          catchError((err) =>
            of(
              ProductsActions.loadProductsFailure({
                error: err.message ?? 'Ошибка загрузки',
              }),
            ),
          ),
        ),
      ),
    ),
  );
}
