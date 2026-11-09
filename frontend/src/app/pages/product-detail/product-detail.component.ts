import { DecimalPipe, NgTemplateOutlet } from '@angular/common';
import {
  Component,
  computed,
  inject,
  OnDestroy,
  OnInit,
  signal,
} from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { Subject, distinctUntilChanged, map, takeUntil } from 'rxjs';

import { environment } from '../../../environments/environment';
import { ApiError } from '../../models/api-error.model';
import { Product, ProductImage } from '../../models/product.model';
import { ProductService } from '../../services/product.service';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [
    DecimalPipe,
    NgTemplateOutlet,
    RouterLink,
    MatButtonModule,
    MatCardModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatTableModule,
  ],
  template: `
    <div class="page-header">
      <button mat-stroked-button routerLink="/products">
        <mat-icon>arrow_back</mat-icon>
        К списку
      </button>
    </div>

    @if (loading()) {
      <div class="center-block">
        <mat-spinner diameter="48" />
      </div>
    } @else if (error()) {
      <mat-card class="error-card">
        <mat-card-content>
          <div class="error-content">
            <mat-icon class="error-icon">error_outline</mat-icon>
            <div>
              <h3>Товар не найден</h3>
              <p>{{ error() }}</p>
            </div>
          </div>
          <button mat-flat-button color="primary" routerLink="/products">
            Вернуться к списку
          </button>
        </mat-card-content>
      </mat-card>
    } @else if (product(); as p) {
      <mat-card class="product-card">
        <mat-card-content>
          <div class="product-header">
            <div class="product-title-block">
              <h2>{{ p.name }}</h2>
              <code class="external-code">{{ p.external_code }}</code>
            </div>
            <div class="price-block">
              <span class="price">{{ p.price | number: '1.2-2' }} ₽</span>
              @if (p.discount > 0) {
                <span class="discount-badge">
                  скидка {{ p.discount | number: '1.0-2' }}%
                </span>
              }
            </div>
          </div>

          @if (p.description) {
            <p class="description">{{ p.description }}</p>
          }
        </mat-card-content>
      </mat-card>

      @if (attributesEntries().length > 0) {
        <mat-card class="section-card">
          <mat-card-header>
            <mat-card-title>Характеристики</mat-card-title>
          </mat-card-header>
          <mat-card-content>
            <table
              mat-table
              [dataSource]="attributesEntries()"
              class="attributes-table"
            >
              <ng-container matColumnDef="key">
                <th mat-header-cell *matHeaderCellDef>Параметр</th>
                <td mat-cell *matCellDef="let row">{{ row.key }}</td>
              </ng-container>

              <ng-container matColumnDef="value">
                <th mat-header-cell *matHeaderCellDef>Значение</th>
                <td mat-cell *matCellDef="let row">{{ row.value }}</td>
              </ng-container>

              <tr mat-header-row *matHeaderRowDef="['key', 'value']"></tr>
              <tr mat-row *matRowDef="let row; columns: ['key', 'value']"></tr>
            </table>
          </mat-card-content>
        </mat-card>
      }

      <mat-card class="section-card">
        <mat-card-header>
          <mat-card-title>Фотографии</mat-card-title>
          <mat-card-subtitle>{{ photoImages().length }} шт.</mat-card-subtitle>
        </mat-card-header>
        <mat-card-content>
          @if (photoImages().length === 0) {
            <p class="empty-note">Фотографии не загружены</p>
          } @else {
            <div class="gallery">
              @for (img of photoImages(); track img.url) {
                <ng-container
                  [ngTemplateOutlet]="imageTile"
                  [ngTemplateOutletContext]="{ $implicit: img }"
                />
              }
            </div>
          }
        </mat-card-content>
      </mat-card>

      @if (packagingImages().length > 0) {
        <mat-card class="section-card">
          <mat-card-header>
            <mat-card-title>Упаковка</mat-card-title>
            <mat-card-subtitle>
              {{ packagingImages().length }} шт.
            </mat-card-subtitle>
          </mat-card-header>
          <mat-card-content>
            <div class="gallery">
              @for (img of packagingImages(); track img.url) {
                <ng-container
                  [ngTemplateOutlet]="imageTile"
                  [ngTemplateOutletContext]="{ $implicit: img }"
                />
              }
            </div>
          </mat-card-content>
        </mat-card>
      }
    }

    <ng-template #imageTile let-img>
      <div class="image-tile" [class.image-placeholder]="!img.path">
        @if (img.path) {
          <img
            [src]="storageUrl(img.path)"
            [alt]="img.type === 'photo' ? 'Фото' : 'Упаковка'"
            loading="lazy"
            (error)="onImageError($event)"
          />
        } @else {
          <div class="placeholder-content">
            <mat-icon>image_not_supported</mat-icon>
            <span>Не скачано</span>
          </div>
        }
      </div>
    </ng-template>
  `,
  styles: [
    `
      .page-header {
        margin-bottom: 16px;
      }
      .center-block {
        display: flex;
        justify-content: center;
        padding: 48px;
      }
      .product-card,
      .section-card,
      .error-card {
        margin-bottom: 16px;
      }
      .error-card {
        border-left: 4px solid var(--mat-sys-error);
      }
      .error-content {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 16px;
      }
      .error-icon {
        font-size: 32px;
        width: 32px;
        height: 32px;
        color: var(--mat-sys-error);
      }
      .product-header {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: flex-start;
        flex-wrap: wrap;
      }
      .product-title-block h2 {
        margin: 0 0 6px 0;
      }
      .external-code {
        font-size: 12px;
        background: var(--mat-sys-surface-container);
        padding: 2px 6px;
        border-radius: 4px;
        color: var(--mat-sys-on-surface-variant);
      }
      .price-block {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
      }
      .price {
        font-size: 24px;
        font-weight: 500;
      }
      .discount-badge {
        background: var(--mat-sys-tertiary-container);
        color: var(--mat-sys-on-tertiary-container);
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 13px;
      }
      .description {
        margin: 16px 0 0 0;
        color: var(--mat-sys-on-surface-variant);
        white-space: pre-line;
      }
      .attributes-table {
        width: 100%;
      }
      .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
      }
      .image-tile {
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        background: var(--mat-sys-surface-container);
        border: 1px solid var(--mat-sys-outline-variant);
      }
      .image-tile img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }
      .image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .placeholder-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: var(--mat-sys-on-surface-variant);
        font-size: 12px;
      }
      .placeholder-content mat-icon {
        font-size: 32px;
        width: 32px;
        height: 32px;
      }
      .empty-note {
        color: var(--mat-sys-on-surface-variant);
        margin: 0;
      }
    `,
  ],
})
export class ProductDetailComponent implements OnInit, OnDestroy {
  private readonly route = inject(ActivatedRoute);
  private readonly productService = inject(ProductService);
  private readonly destroy$ = new Subject<void>();

  readonly loading = signal(true);
  readonly error = signal<string | null>(null);
  readonly product = signal<Product | null>(null);

  readonly attributesEntries = computed(() => {
    const p = this.product();
    if (!p?.attributes) return [];
    return Object.entries(p.attributes).map(([key, value]) => ({ key, value }));
  });

  readonly photoImages = computed<ProductImage[]>(() => {
    return (this.product()?.images ?? []).filter((i) => i.type === 'photo');
  });

  readonly packagingImages = computed<ProductImage[]>(() => {
    return (this.product()?.images ?? []).filter((i) => i.type === 'packaging');
  });

  ngOnInit(): void {
    this.route.paramMap
      .pipe(
        map((params) => Number(params.get('id'))),
        distinctUntilChanged(),
        takeUntil(this.destroy$),
      )
      .subscribe((id) => {
        if (!Number.isInteger(id) || id <= 0) {
          this.loading.set(false);
          this.error.set('Некорректный ID товара');
          return;
        }
        this.loadProduct(id);
      });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  storageUrl(path: string): string {
    return `${environment.storageBaseUrl}/storage/${path}`;
  }

  onImageError(event: Event): void {
    const img = event.target as HTMLImageElement;
    const tile = img.parentElement;
    if (!tile) return;
    img.remove();
    tile.classList.add('image-placeholder');
    const placeholder = document.createElement('div');
    placeholder.className = 'placeholder-content';
    placeholder.innerHTML =
      '<mat-icon>broken_image</mat-icon><span>Ошибка загрузки</span>';
    tile.appendChild(placeholder);
  }

  private loadProduct(id: number): void {
    this.loading.set(true);
    this.error.set(null);
    this.product.set(null);

    this.productService
      .detail(id)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (product) => {
          this.product.set(product);
          this.loading.set(false);
        },
        error: (err: ApiError) => {
          this.loading.set(false);
          this.error.set(
            err.status === 404
              ? `Товар с ID ${id} не найден`
              : err.message ?? 'Ошибка загрузки',
          );
        },
      });
  }
}
