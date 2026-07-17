# Guía de Configuración para Nuevo Proyecto

Esta guía explica cómo copiar la plantilla base para crear un nuevo proyecto para la Clínica Santa Bárbara.

## 📋 Requisitos Previos

- Acceso al repositorio de la plantilla
- Git instalado
- PHP 8.3+
- Composer
- Node.js 18+
- pnpm o npm

---

## 🚀 Paso 1: Copiar la Plantilla

### Opción A: Clonar desde Git (Recomendado)
```bash
# 1. Clonar la plantilla
git clone <url-repositorio-plantilla> nombre-nuevo-proyecto
cd nombre-nuevo-proyecto

# 2. Eliminar el origen de la plantilla
git remote remove origin

# 3. Crear nuevo repositorio (en GitHub, GitLab, Bitbucket, etc.)
#    y conectar el proyecto
git remote add origin <url-nuevo-repositorio>
git branch -M main
git push -u origin main
```

**✅ Ventaja:** Los cambios en el nuevo proyecto NO afectan la plantilla original. Es una copia completamente independiente con su propio repositorio.

### Opción B: Copiar carpeta local
```bash
# Copiar la carpeta completa de la plantilla
# Renombrar a nombre-nuevo-proyecto
cd nombre-nuevo-proyecto
```

**⚠️ Importante:** Asegúrate de copiar la carpeta, no trabajar directamente en la plantilla original. Los cambios en la copia no afectarán la original.

---

## 🔧 Paso 2: Cambiar Nombre de Aplicación

### Método Manual

#### 2.1 Actualizar `.env`
```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env`:
```env
APP_NAME="Nombre del Nuevo Proyecto"
VITE_APP_NAME="${APP_NAME}"
```

#### 2.2 Actualizar `package.json`
```json
{
  "name": "nombre-nuevo-proyecto",
  ...
}
```

#### 2.3 Actualizar `composer.json`
```json
{
  "name": "clinica/nombre-nuevo-proyecto",
  "description": "Descripción del nuevo proyecto",
  ...
}
```

### Método Automatizado (Script)

Ejecutar el script de configuración:
```bash
php scripts/setup-project.php "Nombre del Nuevo Proyecto"
```

Este script actualizará automáticamente:
- `.env` - APP_NAME y VITE_APP_NAME
- `package.json` - name
- `composer.json` - name y description
- `config/app.php` - name (si es necesario)

---

## 🧹 Paso 3: Limpiar Base de Datos

Si estás reutilizando una base de datos existente o quieres empezar desde cero:

### 3.1 Opción A: Base de Datos Nueva (Recomendado)
```bash
# SQLite (por defecto)
rm database/database.sqlite
touch database/database.sqlite

# MySQL/PostgreSQL
# Crear nueva base de datos vacía en tu servidor
```

### 3.2 Opción B: Limpiar Base de Datos Existente
```bash
php artisan migrate:fresh --seed
```

Esto:
- Elimina todas las tablas
- Vuelve a ejecutar todas las migraciones
- Ejecuta los seeders (usuarios, roles, permisos, settings)

### 3.3 Verificar Datos Iniciales
Después de `migrate --seed`, deberías tener:
- Usuario super-admin: `superadmin` / `admin123`
- Roles: super-admin, admin, user
- Permisos del sistema
- Tipos de identificación
- Configuración inicial

---

## 📦 Paso 4: Instalar Dependencias

```bash
composer install
pnpm install
```

---

## 🏗️ Paso 5: Compilar Assets

```bash
pnpm run build
```

---

## ✅ Paso 6: Checklist de Personalización

Antes de empezar a desarrollar, revisa este checklist:

### Identidad del Proyecto
- [ ] Nombre de aplicación actualizado en `.env`
- [ ] Nombre de aplicación actualizado en `package.json`
- [ ] Nombre de aplicación actualizado en `composer.json`
- [ ] Descripción del proyecto actualizada

### Base de Datos
- [ ] Base de datos limpia o nueva
- [ ] Migraciones ejecutadas correctamente
- [ ] Seeders ejecutados correctamente
- [ ] Usuario super-admin creado y funcional

### Configuración
- [ ] `.env` configurado correctamente
- [ ] `APP_KEY` generada
- [ ] URL de aplicación configurada
- [ ] Configuración de correo en settings de BD (si se necesita)

### Módulos a Eliminar (si aplica)
- [ ] Eliminar módulos que no se necesiten
- [ ] Eliminar rutas del router Vue
- [ ] Eliminar items del sidebar
- [ ] Eliminar controllers backend (si existen)
- [ ] Eliminar permisos de seeders (si existen)

### Pruebas
- [ ] Servidor inicia correctamente: `php artisan serve`
- [ ] Login funciona con usuario super-admin
- [ ] Navegación funciona correctamente
- [ ] Assets cargan correctamente

---

## 🧪 Paso 7: Verificar Instalación

### 7.1 Iniciar Servidor
```bash
php artisan serve
```

### 7.2 Probar Login
1. Ir a `http://localhost:8000`
2. Iniciar sesión con:
   - Usuario: `superadmin`
   - Contraseña: `admin123`
3. Verificar que puedas acceder al dashboard

### 7.3 Verificar Módulos
- [ ] Dashboard carga correctamente
- [ ] Usuarios (si se necesita)
- [ ] Roles (si se necesita)
- [ ] Configuración (si se necesita)
- [ ] Perfil de usuario

---

## 📝 Paso 8: Configurar Settings del Sistema

Si el proyecto requiere configuración específica:

### 8.1 Configuración de Correo (SMTP)
Ir a Configuración en la aplicación y configurar:
- Host SMTP
- Puerto SMTP
- Usuario SMTP
- Contraseña SMTP
- Encriptación (TLS/SSL)
- From address
- From name

### 8.2 Configuración de Contraseñas
Ir a Configuración y ajustar:
- Longitud mínima
- Requerir mayúsculas
- Requerir números
- Requerir caracteres especiales

---

## 🚀 Paso 9: Iniciar Desarrollo

### 9.1 Servidor de Desarrollo Completo
```bash
composer run dev
```

Esto iniciará:
- Servidor Laravel
- Queue worker
- Logs (Pail)
- Vite (frontend)

### 9.2 Servidor Laravel Solamente
```bash
php artisan serve
```

En otra terminal:
```bash
pnpm run dev
```

---

## 📚 Recursos Adicionales

- `MODULE_GUIDE.md` - Guía para crear nuevos módulos
- `docs/backend-module-guide.md` - Guía de backend
- `docs/frontend-module-guide.md` - Guía de frontend
- `README.md` - Documentación general del proyecto

---

## ⚠️ Notas Importantes

### Branding
- **Logo y colores son fijos** para la Clínica Santa Bárbara
- No modificar el logo
- No modificar la paleta de colores
- Solo el nombre de la aplicación es configurable

### Módulos de Ejemplo
- La plantilla no debe contener módulos de ejemplo
- Si se copió de un proyecto anterior, eliminar módulos específicos
- Mantener solo módulos genéricos (usuarios, roles, configuración, perfil)

### Base de Datos
- SQLite es el default para desarrollo rápido
- Para producción, usar MySQL o PostgreSQL
- Configurar conexión en `.env`

---

## 🆘 Solución de Problemas

### Error: "No se encuentra la clase"
```bash
composer dump-autoload
php artisan clear-compiled
```

### Error: "Vite manifest not found"
```bash
pnpm run build
```

### Error: "Database connection failed"
```bash
# Verificar configuración en .env
php artisan config:clear
php artisan cache:clear
```

### Error: "Permission denied"
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache

# Windows (PowerShell)
icacls storage /grant Users:F
icacls bootstrap\cache /grant Users:F
```

---

**Última actualización:** Julio 2026