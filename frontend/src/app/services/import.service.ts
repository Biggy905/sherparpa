import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { EMPTY, Observable, expand, throwError, timer } from 'rxjs';
import { catchError, switchMap, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { ApiError } from '../models/api-error.model';
import { ImportStartResponse, ImportStatus } from '../models/import.model';

@Injectable({ providedIn: 'root' })
export class ImportService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;
  private readonly LAST_JOB_KEY = 'last_import_job_id';

  upload(file: File): Observable<ImportStartResponse> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ImportStartResponse>(
      `${this.baseUrl}/imports`,
      formData,
    );
  }

  status(jobId: string): Observable<ImportStatus> {
    return this.http.get<ImportStatus>(`${this.baseUrl}/imports/${jobId}`);
  }

  poll(jobId: string, baseMs = 1500, maxMs = 30000): Observable<ImportStatus> {
    let attempt = 0;

    const tick$ = (delay: number): Observable<ImportStatus> =>
      timer(delay).pipe(
        switchMap(() => this.status(jobId)),
        tap(() => (attempt = 0)),
        catchError((err: ApiError) => {
          if (err.status === 404) {
            return throwError(() => err);
          }
          attempt++;
          const nextDelay = Math.min(baseMs * 2 ** attempt, maxMs);
          return tick$(nextDelay);
        }),
        expand((status) =>
          status.status === 'pending' || status.status === 'processing'
            ? tick$(baseMs)
            : EMPTY,
        ),
      );

    return tick$(0);
  }

  saveLastJob(jobId: string): void {
    localStorage.setItem(this.LAST_JOB_KEY, jobId);
  }

  getLastJob(): string | null {
    return localStorage.getItem(this.LAST_JOB_KEY);
  }

  clearLastJob(): void {
    localStorage.removeItem(this.LAST_JOB_KEY);
  }
}
