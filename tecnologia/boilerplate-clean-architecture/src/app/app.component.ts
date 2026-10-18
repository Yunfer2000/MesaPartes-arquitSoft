// =============================================================
// PRESENTACION · Componente raiz
// =============================================================
import { Component } from '@angular/core';

@Component({
  selector: 'app-root',
  standalone: true,
  template: `
    <main>
      <header class="encabezado">
        <h1>Mesa de Partes Virtual</h1>
        <p>Instituto de Educación Superior Tecnológico Público
          Manuel Antonio Hierro Pozo – Ayacucho</p>
      </header>

      <section class="contenido">
        <h2>Gestión de trámites y expedientes</h2>
        <p>
          Bienvenido al sistema de Mesa de Partes Virtual.
        </p>
      </section>
    </main>
  `,
  styles: [`
    main {
      font-family: Arial, sans-serif;
      max-width: 960px;
      margin: 0 auto;
      padding: 24px;
    }

    .encabezado {
      border-bottom: 2px solid #1f4e79;
      padding-bottom: 12px;
      margin-bottom: 20px;
    }

    h1 {
      color: #1f4e79;
      font-size: 22px;
      margin: 0 0 12px;
    }

    .contenido {
      padding: 16px 0;
    }
  `],
})
export class AppComponent {}