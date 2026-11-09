import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    MatToolbarModule,
    MatButtonModule,
  ],
  template: `
    <mat-toolbar color="primary" class="app-toolbar">
      <span class="app-title">Импорт / Экспорт товаров</span>
      <span class="spacer"></span>
      <a
        mat-button
        routerLink="/imports"
        routerLinkActive="active-link"
      >
        Импорт
      </a>
      <a
        mat-button
        routerLink="/products"
        routerLinkActive="active-link"
        [routerLinkActiveOptions]="{ exact: false }"
      >
        Товары
      </a>
    </mat-toolbar>

    <main class="app-content">
      <router-outlet />
    </main>
  `,
  styles: [
    `
      .app-toolbar {
        position: sticky;
        top: 0;
        z-index: 10;
      }
      .app-title {
        font-weight: 500;
      }
      .spacer {
        flex: 1 1 auto;
      }
      .active-link {
        text-decoration: underline;
        text-underline-offset: 4px;
      }
      .app-content {
        padding: 24px;
        max-width: 1200px;
        margin: 0 auto;
      }
    `,
  ],
})
export class AppComponent {}
