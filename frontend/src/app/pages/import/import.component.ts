import { DecimalPipe } from '@angular/common';
import {
  Component,
  ElementRef,
  inject,
  OnDestroy,
  OnInit,
  ViewChild,
} from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatDividerModule } from '@angular/material/divider';
import { MatChipsModule } from '@angular/material/chips';
import { Subscription } from 'rxjs';

import { ApiError } from '../../models/api-error.model';
import { ImportStatus } from '../../models/import.model';
import { ImportService } from '../../services/import.service';

type UploadState = 'idle' | 'uploading' | 'polling';

@Component({
  selector: 'app-import',
  standalone: true,
  imports: [
    DecimalPipe,
    RouterLink,
    MatButtonModule,
    MatCardModule,
    MatIconModule,
    MatProgressBarModule,
    MatExpansionModule,
    MatDividerModule,
    MatChipsModule,
  ],
  template: `
    <div class="page-header">
      <h2>Импорт товаров</h2>
    </div>

    <!-- ============ Состояние 1: нет активного импорта ============ -->
    @if (state === 'idle') {
      <mat-card class="upload-card">
        <mat-card-content>
          <div class="upload-zone">
            <mat-icon class="upload-icon">upload_file</mat-icon>
            <p class="upload-title">Загрузите файл .xlsx</p>
            <p class="upload-hint">
              Максимум 20 МБ. Файл обрабатывается асинхронно — статус появится ниже.
            </p>

            <input
              #fileInput
              type="file"
              accept=".xlsx"
              hidden
              (change)="onFileSelected($event)"
            />

            <div class="upload-actions">
              <button
                mat-stroked-button
                type="button"
                (click)="fileInput.click()"
                [disabled]="isUploading"
              >
                <mat-icon>attach_file</mat-icon>
                Выбрать файл
              </button>

              @if (selectedFile) {
                <span class="file-info">
                  {{ selectedFile.name }}
                  ({{ selectedFile.size / 1024 / 1024 | number: '1.1-1' }} МБ)
                </span>
              }

              <button
                mat-flat-button
                color="primary"
                type="button"
                (click)="upload()"
                [disabled]="!selectedFile || isUploading"
              >
                @if (isUploading) {
                  <ng-container>Загрузка…</ng-container>
                } @else {
                  <ng-container>Загрузить</ng-container>
                }
              </button>
            </div>
          </div>
        </mat-card-content>
      </mat-card>
    }

    <!-- ============ Состояние 2/3: активный или завершённый импорт ============ -->
    @if (state === 'polling' && status) {
      <mat-card class="status-card">
        <mat-card-content>
          <div class="status-header">
            <div>
              <h3>
                Импорт
                <code class="job-id">{{ status.job_id }}</code>
              </h3>
              <p class="overall-status">
                Статус:
                <strong [class]="'status-' + status.status">
                  {{ statusLabel(status.status) }}
                </strong>
                · Этап: <strong>{{ stageLabel(status.stage) }}</strong>
              </p>
            </div>
            <button mat-stroked-button type="button" (click)="startNewImport()">
              <mat-icon>refresh</mat-icon>
              Новый импорт
            </button>
          </div>

          <!-- Этап 1: Товары -->
          <section class="stage-block">
            <div class="stage-title">
              <span>Этап 1: Товары</span>
              <span class="stage-status status-{{ status.products.status }}">
                {{ statusLabel(status.products.status) }}
              </span>
            </div>

            <mat-progress-bar
              [mode]="status.products.status === 'completed' || status.products.status === 'failed' ? 'determinate' : 'indeterminate'"
              [value]="status.products.progress_percent"
            />
            <div class="progress-meta">
              <span>
                {{ status.products.processed }} / {{ status.products.total }}
                ({{ status.products.progress_percent }}%)
              </span>
              <span class="counters">
                Успешно: {{ status.products.processed - status.products.failed }}
                · Ошибок: {{ status.products.failed }}
              </span>
            </div>

            @if (status.products.errors.length > 0) {
              <mat-expansion-panel class="errors-panel">
                <mat-expansion-panel-header>
                  <mat-panel-title>
                    <mat-icon class="error-icon">warning</mat-icon>
                    Ошибки строк ({{ status.products.errors.length }})
                  </mat-panel-title>
                </mat-expansion-panel-header>
                <ul class="errors-list">
                  @for (err of status.products.errors; track err.row) {
                    <li>
                      <strong>Строка {{ err.row }}:</strong> {{ err.message }}
                    </li>
                  }
                </ul>
              </mat-expansion-panel>
            }
          </section>

          <mat-divider />

          <!-- Этап 2: Изображения -->
          <section class="stage-block">
            <div class="stage-title">
              <span>Этап 2: Изображения</span>
              <span class="stage-status">
                Скачано {{ status.images.completed }} / {{ status.images.total }}
              </span>
            </div>

            <mat-progress-bar
              [mode]="imagesProgressMode(status.images.total, status.images.completed + status.images.failed)"
              [value]="imagesPercent()"
            />
            <div class="progress-meta">
              <span>
                {{ status.images.completed }} / {{ status.images.total }}
                ({{ imagesPercent() }}%)
              </span>
              <span class="counters">
                В очереди: {{ status.images.pending }}
                · В процессе: {{ status.images.processing }}
                · Ошибок: {{ status.images.failed }}
              </span>
            </div>
          </section>

          @if (status.status === 'completed') {
            <div class="completed-actions">
              <mat-icon class="success-icon">check_circle</mat-icon>
              <span>Импорт завершён</span>
              <span class="spacer"></span>
              <button mat-flat-button color="primary" routerLink="/products">
                Перейти к списку товаров
                <mat-icon>arrow_forward</mat-icon>
              </button>
            </div>
          }

          @if (status.status === 'failed') {
            <div class="failed-banner">
              <mat-icon>error</mat-icon>
              <span>Импорт завершился с ошибкой. Проверьте отчёт выше.</span>
            </div>
          }
        </mat-card-content>
      </mat-card>
    }
  `,
  styles: [
    `
      .page-header h2 {
        margin: 0 0 16px 0;
      }
      .upload-card,
      .status-card {
        margin-bottom: 16px;
      }
      .upload-zone {
        text-align: center;
        padding: 48px 16px;
      }
      .upload-icon {
        font-size: 64px;
        width: 64px;
        height: 64px;
        color: var(--mat-sys-primary);
        margin-bottom: 8px;
      }
      .upload-title {
        font-size: 18px;
        font-weight: 500;
        margin: 8px 0 4px;
      }
      .upload-hint {
        color: var(--mat-sys-on-surface-variant);
        margin-bottom: 24px;
      }
      .upload-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
      }
      .file-info {
        color: var(--mat-sys-on-surface-variant);
        font-size: 14px;
      }
      .status-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
      }
      .status-header h3 {
        margin: 0 0 4px 0;
      }
      .job-id {
        font-size: 12px;
        background: var(--mat-sys-surface-container);
        padding: 2px 6px;
        border-radius: 4px;
        color: var(--mat-sys-on-surface-variant);
      }
      .overall-status {
        margin: 0;
        color: var(--mat-sys-on-surface-variant);
        font-size: 14px;
      }
      .stage-block {
        margin: 16px 0;
      }
      .stage-title {
        display: flex;
        justify-content: space-between;
        font-weight: 500;
        margin-bottom: 8px;
      }
      .stage-status {
        font-weight: 400;
        font-size: 14px;
        color: var(--mat-sys-on-surface-variant);
      }
      .progress-meta {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: var(--mat-sys-on-surface-variant);
        margin-top: 6px;
      }
      .errors-panel {
        margin-top: 12px;
      }
      .error-icon {
        color: var(--mat-sys-error);
        margin-right: 8px;
      }
      .errors-list {
        margin: 0;
        padding-left: 20px;
        font-size: 13px;
        max-height: 240px;
        overflow-y: auto;
      }
      .errors-list li {
        margin-bottom: 4px;
      }
      .completed-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 24px;
        padding: 16px;
        background: var(--mat-sys-primary-container);
        color: var(--mat-sys-on-primary-container);
        border-radius: 8px;
      }
      .success-icon {
        color: var(--mat-sys-primary);
      }
      .spacer {
        flex: 1;
      }
      .failed-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 16px;
        background: var(--mat-sys-error-container);
        color: var(--mat-sys-on-error-container);
        border-radius: 8px;
      }
      .status-completed { color: var(--mat-sys-primary); }
      .status-failed { color: var(--mat-sys-error); }
      .status-processing { color: var(--mat-sys-tertiary); }
      .status-pending { color: var(--mat-sys-on-surface-variant); }
    `,
  ],
})
export class ImportComponent implements OnInit, OnDestroy {
  private readonly importService = inject(ImportService);
  private readonly snack = inject(MatSnackBar);
  private readonly router = inject(Router);

  @ViewChild('fileInput') fileInputRef?: ElementRef<HTMLInputElement>;

  state: UploadState = 'idle';
  selectedFile: File | null = null;
  status: ImportStatus | null = null;

  private pollSub?: Subscription;

  ngOnInit(): void {
    const lastJobId = this.importService.getLastJob();
    if (lastJobId) {
      this.startPolling(lastJobId);
    }
  }

  ngOnDestroy(): void {
    this.pollSub?.unsubscribe();
  }

  get isUploading(): boolean {
    return this.state === 'uploading';
  }

  get isPolling(): boolean {
    return this.state === 'polling';
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) {
      this.selectedFile = null;
      return;
    }

    if (!file.name.toLowerCase().endsWith('.xlsx')) {
      this.snack.open('Только файлы .xlsx', 'OK', { duration: 4000 });
      this.selectedFile = null;
      if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
      return;
    }

    if (file.size > 20 * 1024 * 1024) {
      this.snack.open('Файл больше 20 МБ', 'OK', { duration: 4000 });
      this.selectedFile = null;
      if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
      return;
    }

    this.selectedFile = file;
  }

  upload(): void {
    if (!this.selectedFile) return;

    this.state = 'uploading';

    this.importService.upload(this.selectedFile).subscribe({
      next: (response) => {
        this.importService.saveLastJob(response.job_id);
        this.selectedFile = null;
        if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
        this.startPolling(response.job_id);
      },
      error: (err: ApiError) => {
        this.state = 'idle';
        // 422 уже показан в interceptor; для остальных — на всякий случай
        if (!err.fieldErrors && err.status !== 422) {
          this.snack.open(err.message ?? 'Ошибка загрузки', 'OK', { duration: 4000 });
        }
      },
    });
  }

  startNewImport(): void {
    this.pollSub?.unsubscribe();
    this.importService.clearLastJob();
    this.status = null;
    this.state = 'idle';
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      pending: 'В очереди',
      processing: 'В процессе',
      completed: 'Завершён',
      failed: 'Ошибка',
    };
    return map[status] ?? status;
  }

  stageLabel(stage: string): string {
    const map: Record<string, string> = {
      products: 'Товары',
      images: 'Изображения',
      done: 'Готово',
      failed: 'Ошибка',
    };
    return map[stage] ?? stage;
  }

  imagesPercent(): number {
    if (!this.status || this.status.images.total === 0) return 0;
    return Math.round(
      ((this.status.images.completed + this.status.images.failed) /
        this.status.images.total) *
      100,
    );
  }

  imagesProgressMode(
    total: number,
    done: number,
  ): 'determinate' | 'indeterminate' {
    if (total === 0) return 'indeterminate';
    return done >= total ? 'determinate' : 'indeterminate';
  }

  private startPolling(jobId: string): void {
    this.pollSub?.unsubscribe();
    this.state = 'polling';
    this.status = null;

    this.pollSub = this.importService.poll(jobId).subscribe({
      next: (status) => {
        this.status = status;
      },
      error: (err: ApiError) => {
        if (err.status === 404) {
          this.importService.clearLastJob();
          this.status = null;
          this.state = 'idle';
          this.snack.open('Импорт не найден, загрузите новый', 'OK', {
            duration: 5000,
          });
        } else {
          // Прочие ошибки interceptor уже показал. Не сбрасываем состояние —
          // можно попробовать снова (poll сам делает backoff).
          this.snack.open(
            'Не удалось получить статус импорта. Пробуем снова…',
            'OK',
            { duration: 4000 },
          );
        }
      },
      complete: () => {
        // poll завершается на completed/failed — оставляем status на экране
      },
    });
  }
}
