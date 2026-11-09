import { Component, inject, OnInit, OnDestroy } from '@angular/core';
import {AsyncPipe, DecimalPipe} from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatIconModule } from '@angular/material/icon';
import { MatCardModule } from '@angular/material/card';
import {
  debounceTime,
  distinctUntilChanged,
  Subject,
  takeUntil,
} from 'rxjs';

import { Product } from '../../models/product.model';
import { loadProducts } from '../../store/products/products.actions';
import {
  selectAllProducts,
  selectProductsLoading,
  selectProductsError,
  selectCurrentPage,
  selectPageSize,
  selectTotalItems,
  selectTotalPages,
} from '../../store/products/products.selectors';

@Component({
  selector: 'app-products',
  standalone: true,
  imports: [
    AsyncPipe,
    ReactiveFormsModule,
    RouterLink,
    MatTableModule,
    MatPaginatorModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressBarModule,
    MatIconModule,
    MatCardModule,
    DecimalPipe,
  ],
  template: `
    <div class="page-header">
      <h2>Список товаров</h2>
      <button mat-stroked-button color="primary" routerLink="/imports">
        <mat-icon>upload_file</mat-icon>
        Импорт
      </button>
    </div>

    <mat-card class="filters-card">
      <form [formGroup]="filtersForm" class="filters-form">
        <mat-form-field appearance="outline" class="filter-name">
          <mat-label>Поиск по названию</mat-label>
          <input matInput formControlName="name" placeholder="Например: Бермуды" />
          <mat-icon matSuffix>search</mat-icon>
        </mat-form-field>

        <mat-form-field appearance="outline" class="filter-price">
          <mat-label>Цена от</mat-label>
          <input matInput type="number" formControlName="priceFrom" min="0" />
        </mat-form-field>

        <mat-form-field appearance="outline" class="filter-price">
          <mat-label>Цена до</mat-label>
          <input matInput type="number" formControlName="priceTo" min="0" />
        </mat-form-field>

        <button
          mat-stroked-button
          type="button"
          (click)="resetFilters()"
          [disabled]="filtersForm.pristine"
        >
          Сбросить
        </button>
      </form>
    </mat-card>

    @if (loading$ | async) {
      <mat-progress-bar mode="indeterminate" />
    }

    @if (error$ | async; as error) {
      <div class="error-banner">
        <mat-icon>error_outline</mat-icon>
        <span>{{ error }}</span>
      </div>
    }

    @if ((items$ | async)?.length === 0 && !(loading$ | async)) {
      <div class="empty-state">
        <mat-icon>inventory_2</mat-icon>
        <p>Товары не найдены. Попробуйте изменить фильтры или загрузите импорт.</p>
      </div>
    } @else {
      <table mat-table [dataSource]="(items$ | async) ?? []" class="products-table">
        <ng-container matColumnDef="name">
          <th mat-header-cell *matHeaderCellDef>Название</th>
          <td mat-cell *matCellDef="let product">
            <a [routerLink]="['/products', product.id]" class="product-link">
              {{ product.name }}
            </a>
            <div class="external-code">{{ product.external_code }}</div>
          </td>
        </ng-container>

        <ng-container matColumnDef="price">
          <th mat-header-cell *matHeaderCellDef>Цена</th>
          <td mat-cell *matCellDef="let product">{{ product.price | number: '1.2-2' }} ₽</td>
        </ng-container>

        <ng-container matColumnDef="discount">
          <th mat-header-cell *matHeaderCellDef>Скидка</th>
          <td mat-cell *matCellDef="let product">
            @if (product.discount > 0) {
              <span class="discount-badge">{{ product.discount | number: '1.0-2' }}%</span>
            } @else {
              —
            }
          </td>
        </ng-container>

        <ng-container matColumnDef="actions">
          <th mat-header-cell *matHeaderCellDef></th>
          <td mat-cell *matCellDef="let product" class="actions-cell">
            <a mat-icon-button [routerLink]="['/products', product.id]" aria-label="Открыть">
              <mat-icon>chevron_right</mat-icon>
            </a>
          </td>
        </ng-container>

        <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
        <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
      </table>

      <mat-paginator
        [length]="(totalItems$ | async) ?? 0"
        [pageSize]="(pageSize$ | async) ?? 20"
        [pageSizeOptions]="[10, 20, 50, 100]"
        [pageIndex]="((currentPage$ | async) ?? 1) - 1"
        (page)="onPageChange($event)"
        showFirstLastButtons
      />
    }
  `,
  styles: [
    `
      .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
      }
      .page-header h2 {
        margin: 0;
      }
      .filters-card {
        margin-bottom: 16px;
        padding: 16px;
      }
      .filters-form {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
      }
      .filter-name {
        flex: 2 1 280px;
      }
      .filter-price {
        flex: 1 1 140px;
      }
      .products-table {
        width: 100%;
        background: var(--mat-sys-surface);
      }
      .product-link {
        color: var(--mat-sys-primary);
        text-decoration: none;
        font-weight: 500;
      }
      .product-link:hover {
        text-decoration: underline;
      }
      .external-code {
        font-size: 12px;
        color: var(--mat-sys-on-surface-variant);
        margin-top: 2px;
      }
      .discount-badge {
        background: var(--mat-sys-tertiary-container);
        color: var(--mat-sys-on-tertiary-container);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 13px;
      }
      .actions-cell {
        text-align: right;
      }
      .error-banner {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--mat-sys-error-container);
        color: var(--mat-sys-on-error-container);
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
      }
      .empty-state {
        text-align: center;
        padding: 48px 16px;
        color: var(--mat-sys-on-surface-variant);
      }
      .empty-state mat-icon {
        font-size: 48px;
        width: 48px;
        height: 48px;
        margin-bottom: 8px;
      }
    `,
  ],
})
export class ProductsComponent implements OnInit, OnDestroy {
  private readonly store = inject(Store);
  private readonly fb = inject(FormBuilder);

  readonly items$ = this.store.select(selectAllProducts);
  readonly loading$ = this.store.select(selectProductsLoading);
  readonly error$ = this.store.select(selectProductsError);
  readonly currentPage$ = this.store.select(selectCurrentPage);
  readonly pageSize$ = this.store.select(selectPageSize);
  readonly totalItems$ = this.store.select(selectTotalItems);
  readonly totalPages$ = this.store.select(selectTotalPages);

  readonly displayedColumns = ['name', 'price', 'discount', 'actions'];

  readonly filtersForm = this.fb.nonNullable.group({
    name: '',
    priceFrom: null as number | null,
    priceTo: null as number | null,
  });

  private readonly destroy$ = new Subject<void>();
  private currentFilters = { name: '', priceFrom: undefined as number | undefined, priceTo: undefined as number | undefined };

  ngOnInit(): void {
    this.store.dispatch(loadProducts({ page: 1, limit: 20 }));

    this.filtersForm.controls.name.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntil(this.destroy$))
      .subscribe((name) => {
        this.currentFilters.name = name;
        this.reload(1);
      });

    this.filtersForm.controls.priceFrom.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntil(this.destroy$))
      .subscribe((priceFrom) => {
        this.currentFilters.priceFrom = priceFrom ?? undefined;
        this.reload(1);
      });

    this.filtersForm.controls.priceTo.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntil(this.destroy$))
      .subscribe((priceTo) => {
        this.currentFilters.priceTo = priceTo ?? undefined;
        this.reload(1);
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  onPageChange(event: PageEvent): void {
    this.store.dispatch(
      loadProducts({
        page: event.pageIndex + 1,
        limit: event.pageSize,
        ...this.currentFilters,
      }),
    );
  }

  resetFilters(): void {
    this.filtersForm.reset({ name: '', priceFrom: null, priceTo: null });
    this.currentFilters = { name: '', priceFrom: undefined, priceTo: undefined };
    this.reload(1);
  }

  private reload(page: number): void {
    this.store.dispatch(
      loadProducts({
        page,
        limit: 20,
        ...this.currentFilters,
      }),
    );
  }
}
