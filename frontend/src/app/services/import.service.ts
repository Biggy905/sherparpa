import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { EMPTY, Observable, expand, throwError, timer } from 'rxjs';
import { catchError, switchMap, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { ApiError } from '../models/api-error.model';
import { ImportStartResponse, ImportStatus } from '../models/import.model';

const STORAGE_KEY = 'import_jobs';

@Injectable({ providedIn: 'root' })
export class ImportService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

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

    const fetch$ = (): Observable<ImportStatus> =>
      this.status(jobId).pipe(
        tap(() => (attempt = 0)),
        catchError((err: ApiError) => {
          if (err.status === 404) return throwError(() => err);
          attempt++;
          const delay = Math.min(baseMs * 2 ** attempt, maxMs);
          return timer(delay).pipe(switchMap(() => fetch$()));
        }),
      );

    return timer(0).pipe(
      switchMap(() => fetch$()),
      expand((status) =>
        status.status === 'pending' || status.status === 'processing'
          ? timer(baseMs).pipe(switchMap(() => fetch$()))
          : EMPTY,
      ),
    );
  }

  getJobs(): string[] {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed.filter((x) => typeof x === 'string') : [];
    } catch {
      return [];
    }
  }

  addJob(jobId: string): void {
    const jobs = this.getJobs();
    if (!jobs.includes(jobId)) {
      jobs.unshift(jobId); // новые — сверху
      localStorage.setItem(STORAGE_KEY, JSON.stringify(jobs));
    }
  }

  removeJob(jobId: string): void {
    const jobs = this.getJobs().filter((id) => id !== jobId);
    if (jobs.length === 0) {
      localStorage.removeItem(STORAGE_KEY);
    } else {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(jobs));
    }
  }

  clearJobs(): void {
    localStorage.removeItem(STORAGE_KEY);
  }
}
