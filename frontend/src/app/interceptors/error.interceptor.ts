import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { catchError, throwError } from 'rxjs';
import { ApiError } from '../models/api-error.model';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const snack = inject(MatSnackBar);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      const apiError: ApiError = {
        status: err.status,
        message: 'Неизвестная ошибка',
      };

      if (err.status === 0) {
        apiError.isNetwork = true;
        apiError.message = 'Сеть недоступна';
        snack.open(apiError.message, 'OK', { duration: 4000 });
      } else if (err.status === 500) {
        apiError.message = 'Ошибка сервера';
        snack.open(apiError.message, 'OK', { duration: 4000 });
      } else if (err.status === 422) {
        const body = err.error ?? {};
        if (Array.isArray(body.errors)) {
          apiError.fieldErrors = body.errors;
          apiError.message = 'Проверьте параметры запроса';
        } else if (typeof body.error === 'string') {
          apiError.message = body.error;
          snack.open(apiError.message, 'OK', { duration: 4000 });
        }
      } else if (err.status === 404) {
        apiError.message = err.error?.error ?? 'Не найдено';
        // 404 на /imports/{id} обрабатывает страница импорта — не тостим здесь
      }

      return throwError(() => apiError);
    }),
  );
};
