# 🌿 Campo Fresco — Tienda Ecológica Online

**Projecte Intermodular 2025-2026 | iMES**  
Autor: Alex Valls

🌐 **Ver el proyecto online:** [alexvalls.github.io/campo-fresco](https://alexvalls.github.io/campo-fresco)

---

## Descripción del proyecto

Campo Fresco es una tienda online de productos ecológicos desarrollada como proyecto final del curso. Permite a los usuarios consultar productos, añadirlos a una cesta de la compra, registrarse e iniciar sesión. Los administradores pueden gestionar el catálogo de productos desde un panel de administración con CRUD completo.

La aplicación está hecha con **HTML5, CSS3 y JavaScript puro** (sin frameworks ni librerías externas). Los datos se persisten con `localStorage` del navegador.

---

## Funcionalidades

- ✅ Página principal con hero, características y productos destacados
- ✅ Tienda completa con filtros por categoría y buscador en tiempo real
- ✅ Login y registro de usuarios con validaciones completas (inline, no alert)
- ✅ Panel de administración con CRUD completo de productos
- ✅ Modal de confirmación personalizado antes de eliminar
- ✅ Notificaciones toast de éxito/error (sin `alert()`)
- ✅ Cesta de la compra persistente con localStorage
- ✅ Sidebar de navegación en todas las páginas
- ✅ Diseño responsive

---

## Estructura del repositorio

```
/
├── index.html               ← Redirige automáticamente a Fase6
│
├── Fase1/                   ← Semana 1: estructura base HTML/CSS
├── Fase2/                   ← Semana 2: carrito localStorage, más productos
│   └── img/                 ← Imágenes de todos los productos (usadas en todas las fases)
├── Fase3/                   ← Semana 3: login y registro real
├── Fase4/                   ← Semana 4: panel admin y CRUD
├── Fase5/                   ← Semana 5: sidebar y filtros
├── Fase6/                   ← Semana 6: VERSIÓN FINAL ← entrar aquí
│   ├── index.html
│   ├── productos.html
│   ├── login.html
│   └── admin.html
│
├── backup/                  ← Scripts de copia de seguridad (parte Sistemes)
│   ├── backup.bat
│   ├── restaurar.bat
│   └── verificar_backup.bat
│
├── kanban.html              ← Tablero Kanban del proyecto
├── diario_avance.html       ← Diario de avance semanal
├── documentacion.html       ← Documentación completa
└── README.md
```

---

## Cómo abrir el proyecto localmente

1. Descarga o clona el repositorio:
   ```
   git clone https://github.com/alexvalls/campo-fresco.git
   ```
2. Abre la carpeta descargada
3. Haz doble clic en `index.html` (o en `Fase6/index.html` directamente)

No necesitas instalar nada. Funciona directamente en el navegador.

---

## Despliegue en GitHub Pages (hosting gratuito)

Este proyecto está desplegado en GitHub Pages. Para desplegarlo tú mismo:

1. Sube el proyecto a un repositorio de GitHub
2. Ve a **Settings → Pages**
3. En *Source*, selecciona **Deploy from a branch**
4. Selecciona la rama `main` y la carpeta `/` (root)
5. Guarda — en unos segundos tendrás la URL

---

## Alternativa: Netlify (más fácil todavía)

1. Ve a [netlify.com](https://netlify.com) y crea una cuenta gratuita
2. En el dashboard, haz clic en **"Add new site → Deploy manually"**
3. Arrastra y suelta la carpeta del proyecto
4. Netlify te dará una URL tipo `campo-fresco.netlify.app`

---

## Crear una cuenta de prueba

Como todo va con localStorage, los datos se guardan en tu navegador:

1. Abre la web → menú lateral → **Iniciar Sesión**
2. Pestaña **"Crear Cuenta"** → rellena los datos → crear
3. Ya puedes iniciar sesión y acceder al panel de **Administración**

---

## Historial de commits

| Commit | Descripción |
|--------|-------------|
| `init` | Estructura base del proyecto (Fase 1) |
| `feat: tienda y carrito localStorage` | Fase 2 |
| `feat: login y registro con validaciones` | Fase 3 |
| `feat: panel admin CRUD completo` | Fase 4 |
| `feat: sidebar y filtros categoria` | Fase 5 |
| `feat: version final, toasts y modal confirmacion` | Fase 6 |
| `chore: scripts backup parte sistemas` | Sistemas |
| `docs: documentacion, kanban y diario` | Documentación |

---

## Autor

**Alex Valls**  
iMES — Projecte Intermodular 2025-2026
