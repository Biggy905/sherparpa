import { DecimalPipe } from '@angular/common';
import {
  Component,
  ElementRef,
  inject,
  OnDestroy,
  OnInit,
  signal,
  ViewChild,
} from '@angular/core';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Subscription } from 'rxjs';

import { ApiError } from '../../models/api-error.model';
import { ImportStatus } from '../../models/import.model';
import { ImportService } from '../../services/import.service';

interface JobEntry {
  jobId: string;
  status: ImportStatus | null;
  isPolling: boolean;
  fatalError: string | null;
  createdAt: number;
}

@Component({
  selector: 'app-import',
  standalone: true,
  imports: [
    DecimalPipe,
    RouterLink,
    MatButtonModule,
    MatCardModule,
    MatDividerModule,
    MatExpansionModule,
    MatIconModule,
    MatProgressBarModule,
  ],
  template: `
    <div class="page-header">
      <h2>Импорт товаров</h2>
    </div>

    <!-- ================= Форма загрузки — всегда сверху ================= -->
    <mat-card class="upload-card">
      <mat-card-content>
        <div class="upload-zone">
          <mat-icon class="upload-icon">upload_file</mat-icon>
          <p class="upload-title">Загрузите файл .xlsx</p>
          <p class="upload-hint">
            Максимум 20 МБ. Каждый импорт обрабатывается независимо — можно
            запускать несколько.
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
              [disabled]="isUploading()"
            >
              <mat-icon>attach_file</mat-icon>
              Выбрать файл
            </button>

            @if (selectedFile()) {
              <span class="file-info">
                {{ selectedFile()!.name }}
                ({{ selectedFile()!.size / 1024 / 1024 | number: '1.1-1' }} МБ)
              </span>
            }

            <button
              mat-flat-button
              color="primary"
              type="button"
              (click)="upload()"
              [disabled]="!selectedFile() || isUploading()"
            >
              @if (isUploading()) {
                Загрузка…
              } @else {
                Загрузить
              }
            </button>
          </div>
        </div>
      </mat-card-content>
    </mat-card>

    <!-- ================= Список импортов ================= -->
    @if (jobs().length === 0) {
      <div class="empty-state">
        <mat-icon>inbox</mat-icon>
        <p>Активных импортов нет. Загрузите файл, чтобы начать.</p>
      </div>
    } @else {
      <div class="jobs-list">
        @for (job of jobs(); track job.jobId) {
          <mat-card class="job-card">
            <mat-card-content>
              <div class="job-header">
                <div>
                  <h3>
                    Импорт
                    <code class="job-id">{{ shortId(job.jobId) }}</code>
                  </h3>
                  @if (job.status) {
                    <p class="overall-status">
                      Статус:
                      <strong [class]="'status-' + job.status.status">
                        {{ statusLabel(job.status.status) }}
                      </strong>
                      · Этап:
                      <strong>{{ stageLabel(job.status.stage) }}</strong>
                    </p>
                  } @else if (job.fatalError) {
                    <p class="overall-status status-failed">
                      {{ job.fatalError }}
                    </p>
                  } @else {
                    <p class="overall-status">Получаем статус…</p>
                  }
                </div>
                <div class="job-actions">
                  @if (job.status?.status === 'completed') {
                    <button
                      mat-flat-button
                      color="primary"
                      routerLink="/products"
                    >
                      К товарам
                      <mat-icon>arrow_forward</mat-icon>
                    </button>
                  }
                  @if (
                    job.status?.status === 'completed' ||
                    job.status?.status === 'failed' ||
                    job.fatalError
                    ) {
                    <button
                      mat-stroked-button
                      type="button"
                      (click)="dismiss(job.jobId)"
                    >
                      <mat-icon>close</mat-icon>
                      Убрать
                    </button>
                  }
                </div>
              </div>

              @if (job.status) {
                <!-- Этап 1: Товары -->
                <section class="stage-block">
                  <div class="stage-title">
                    <span>Этап 1: Товары</span>
                    <span class="stage-status status-{{ job.status.products.status }}">
                      {{ statusLabel(job.status.products.status) }}
                    </span>
                  </div>

                  <mat-progress-bar
                    [mode]="
                      job.status.products.status === 'completed' ||
                      job.status.products.status === 'failed'
                        ? 'determinate'
                        : 'indeterminate'
                    "
                    [value]="job.status.products.progress_percent"
                  />
                  <div class="progress-meta">
                    <span>
                      {{ job.status.products.processed }} /
                      {{ job.status.products.total }}
                      ({{ job.status.products.progress_percent }}%)
                    </span>
                    <span>
                      Успешно:
                      {{
                        job.status.products.processed -
                        job.status.products.failed
                      }}
                      · Ошибок: {{ job.status.products.failed }}
                    </span>
                  </div>

                  @if (job.status.products.errors.length > 0) {
                    <mat-expansion-panel class="errors-panel">
                      <mat-expansion-panel-header>
                        <mat-panel-title>
                          <mat-icon class="error-icon">warning</mat-icon>
                          Ошибки строк ({{ job.status.products.errors.length }})
                        </mat-panel-title>
                      </mat-expansion-panel-header>
                      <ul class="errors-list">
                        @for (err of job.status.products.errors; track err.row) {
                          <li>
                            <strong>Строка {{ err.row }}:</strong>
                            {{ err.message }}
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
                      Скачано {{ job.status.images.completed }} /
                      {{ job.status.images.total }}
                    </span>
                  </div>

                  <mat-progress-bar
                    [mode]="
                      job.status.images.total === 0 ||
                      job.status.images.completed + job.status.images.failed <
                        job.status.images.total
                        ? 'indeterminate'
                        : 'determinate'
                    "
                    [value]="imagesPercent(job.status)"
                  />
                  <div class="progress-meta">
                    <span>
                      {{ job.status.images.completed }} /
                      {{ job.status.images.total }}
                      ({{ imagesPercent(job.status) }}%)
                    </span>
                    <span>
                      В очереди: {{ job.status.images.pending }}
                      · В процессе: {{ job.status.images.processing }}
                      · Ошибок: {{ job.status.images.failed }}
                    </span>
                  </div>
                </section>

                @if (job.status.status === 'completed') {
                  <div class="completed-banner">
                    <mat-icon>check_circle</mat-icon>
                    <span>Импорт завершён</span>
                  </div>
                }
                @if (job.status.status === 'failed') {
                  <div class="failed-banner">
                    <mat-icon>error</mat-icon>
                    <span>Импорт завершился с ошибкой</span>
                  </div>
                }
              }
            </mat-card-content>
          </mat-card>
        }
      </div>
    }
  `,
  styles: [
    `
      .page-header h2 {
        margin: 0 0 16px 0;
      }
      .upload-card {
        margin-bottom: 24px;
      }
      .upload-zone {
        text-align: center;
        padding: 32px 16px;
      }
      .upload-icon {
        font-size: 56px;
        width: 56px;
        height: 56px;
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
        margin-bottom: 20px;
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
      .jobs-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .job-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 16px;
      }
      .job-header h3 {
        margin: 0 0 4px 0;
      }
      .job-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
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
      .completed-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 12px 16px;
        background: var(--mat-sys-primary-container);
        color: var(--mat-sys-on-primary-container);
        border-radius: 8px;
      }
      .failed-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 12px 16px;
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

  @ViewChild('fileInput') fileInputRef?: ElementRef<HTMLInputElement>;

  readonly jobs = signal<JobEntry[]>([]);
  readonly selectedFile = signal<File | null>(null);
  readonly isUploading = signal(false);

  private readonly subscriptions = new Map<string, Subscription>();

  ngOnInit(): void {
    const jobIds = this.importService.getJobs();
    for (const jobId of jobIds) {
      this.startPolling(jobId);
    }
  }

  ngOnDestroy(): void {
    for (const sub of this.subscriptions.values()) {
      sub.unsubscribe();
    }
    this.subscriptions.clear();
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) {
      this.selectedFile.set(null);
      return;
    }

    if (!file.name.toLowerCase().endsWith('.xlsx')) {
      this.snack.open('Только файлы .xlsx', 'OK', { duration: 4000 });
      this.selectedFile.set(null);
      if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
      return;
    }

    if (file.size > 20 * 1024 * 1024) {
      this.snack.open('Файл больше 20 МБ', 'OK', { duration: 4000 });
      this.selectedFile.set(null);
      if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
      return;
    }

    this.selectedFile.set(file);
  }

  upload(): void {
    const file = this.selectedFile();
    if (!file || this.isUploading()) return;

    this.isUploading.set(true);

    this.importService.upload(file).subscribe({
      next: (response) => {
        this.isUploading.set(false);
        this.selectedFile.set(null);
        if (this.fileInputRef) this.fileInputRef.nativeElement.value = '';
        this.importService.addJob(response.job_id);
        this.startPolling(response.job_id);
      },
      error: (err: ApiError) => {
        this.isUploading.set(false);
        if (!err.fieldErrors && err.status !== 422) {
          this.snack.open(err.message ?? 'Ошибка загрузки', 'OK', {
            duration: 4000,
          });
        }
      },
    });
  }

  dismiss(jobId: string): void {
    this.subscriptions.get(jobId)?.unsubscribe();
    this.subscriptions.delete(jobId);
    this.importService.removeJob(jobId);
    this.jobs.update((list) => list.filter((j) => j.jobId !== jobId));
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

  shortId(jobId: string): string {
    return jobId.slice(0, 8);
  }

  imagesPercent(status: ImportStatus): number {
    if (status.images.total === 0) return 0;
    return Math.round(
      ((status.images.completed + status.images.failed) /
        status.images.total) *
      100,
    );
  }

  private startPolling(jobId: string): void {
    this.subscriptions.get(jobId)?.unsubscribe();

    // добавляем запись в список, если её ещё нет
    if (!this.jobs().some((j) => j.jobId === jobId)) {
      this.jobs.update((list) => [
        {
          jobId,
          status: null,
          isPolling: true,
          fatalError: null,
          createdAt: Date.now(),
        },
        ...list,
      ]);
    }

    const sub = this.importService.poll(jobId, 3000).subscribe({
      next: (status) => {
        this.jobs.update((list) =>
          list.map((j) =>
            j.jobId === jobId ? { ...j, status, isPolling: true } : j,
          ),
        );
      },
      error: (err: ApiError) => {
        if (err.status === 404) {
          this.importService.removeJob(jobId);
          this.jobs.update((list) =>
            list.map((j) =>
              j.jobId === jobId
                ? {
                  ...j,
                  isPolling: false,
                  fatalError: 'Импорт не найден на сервере',
                }
                : j,
            ),
          );
          this.snack.open('Импорт не найден на сервере', 'OK', {
            duration: 4000,
          });
        }
        // прочие ошибки poll сам ретраит с backoff
      },
      complete: () => {
        this.jobs.update((list) =>
          list.map((j) =>
            j.jobId === jobId ? { ...j, isPolling: false } : j,
          ),
        );
      },
    });

    this.subscriptions.set(jobId, sub);
  }
}
