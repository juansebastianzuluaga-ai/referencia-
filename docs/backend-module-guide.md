# Guía para Crear un Nuevo Módulo Backend

Este documento describe paso a paso cómo crear un nuevo módulo funcional dentro de este proyecto Laravel. Está orientado a que cualquier IA o desarrollador pueda replicar el patrón exacto que ya existe en el código base, sin inventar estructuras nuevas.

---

## Índice

1. [Arquitectura general](#1-arquitectura-general)
2. [Convenciones de nomenclatura](#2-convenciones-de-nomenclatura)
3. [Paso 1 — Migración](#3-paso-1--migración)
4. [Paso 2 — Modelo](#4-paso-2--modelo)
5. [Paso 3 — Factory](#5-paso-3--factory)
6. [Paso 4 — Seeder](#6-paso-4--seeder)
7. [Paso 5 — Form Requests (Store y Update)](#7-paso-5--form-requests-store-y-update)
8. [Paso 6 — API Resource](#8-paso-6--api-resource)
9. [Paso 7 — Controlador](#9-paso-7--controlador)
10. [Paso 8 — Rutas](#10-paso-8--rutas)
11. [Paso 9 — Permisos](#11-paso-9--permisos)
12. [Ejemplo completo: Módulo `products`](#12-ejemplo-completo-módulo-products)
13. [Checklist final](#13-checklist-final)

---

## 1. Arquitectura general

El backend es una **API REST pura** construida sobre Laravel 13. No hay vistas Blade para los módulos de negocio. Toda la comunicación es JSON.

### Estructura de directorios relevante

```
app/
├── Actions/
│   └── Fortify/              # Solo acciones de autenticación. No tocar.
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # ← Aquí van todos los controladores de módulos
│   │   │   ├── BaseController.php        # Padre de todos los controladores API
│   │   │   └── Concerns/
│   │   │       └── FiltersPaginatedResults.php  # Trait de filtrado/paginación
│   │   └── Controller.php
│   ├── Middleware/           # Middlewares de autenticación y autorización
│   ├── Requests/             # Form Requests de validación y autorización
│   └── Resources/            # API Resources (transformación de respuestas JSON)
├── Models/
│   ├── Concerns/
│   │   └── HasAuditableTags.php  # Trait obligatorio para modelos auditables
│   └── *.php                 # Modelos Eloquent
├── Services/
│   ├── ActivityLogger.php    # Log de actividad manual
│   └── AuditLogger.php       # Auditoría personalizada sobre modelos Auditable
database/
├── factories/                # Factories para tests y seeders
├── migrations/               # Migraciones de BD
└── seeders/                  # Seeders de datos iniciales
routes/
└── web.php                   # Todas las rutas (incluyendo las del prefijo /api)
```

### Flujo de una petición típica

```
HTTP Request
    → Middleware auth:sanctum  (verifica token Sanctum)
    → Middleware active        (verifica is_active del usuario)
    → Form Request authorize() (verifica permiso específico)
    → Form Request rules()     (valida el body)
    → Controller               (lógica de negocio)
    → API Resource             (formatea la respuesta JSON)
    → BaseController::sendResponse() / sendError()
```

---

## 2. Convenciones de nomenclatura

| Artefacto | Convención | Ejemplo |
|---|---|---|
| Tabla en BD | `snake_case` plural | `products` |
| Modelo | `PascalCase` singular | `Product` |
| Migración | `create_{tabla}_table` | `create_products_table` |
| Factory | `{Modelo}Factory` | `ProductFactory` |
| Seeder | `{Módulo}Seeder` | `ProductSeeder` |
| Store Request | `Store{Modelo}Request` | `StoreProductRequest` |
| Update Request | `Update{Modelo}Request` | `UpdateProductRequest` |
| API Resource | `{Modelo}Resource` | `ProductResource` |
| Controlador | `{Modelo}Controller` | `ProductController` |
| Permisos | `{modulo}.{acción}` (kebab-case) | `products.view`, `products.create` |
| Prefijo de ruta | `{modulo-kebab}` | `/api/products` |
| Nombre de ruta | `api.{modulos-kebab}.*` | `api.products.store` |

---

## 3. Paso 1 — Migración

### Comando

```bash
php artisan make:migration create_{tabla}_table --no-interaction
```

### Reglas a seguir

- La tabla de **identificación/catálogo** va **antes** de la tabla principal si existe FK entre ellas (igual que `identification_types` se crea antes que `users` en la misma migración).
- Siempre agregar `$table->timestamps()`.
- Si el modelo puede eliminarse lógicamente, agregar `$table->softDeletes()`.
- Si el modelo es auditable, no se necesita nada extra en la migración; la tabla `audits` ya existe.
- Documentar cada columna relevante con `->comment('...')`.
- Las FK deben usar `->constrained()->cascadeOnUpdate()->restrictOnDelete()` por defecto, salvo que la lógica del negocio requiera otra cosa.
- Columnas booleanas de estado usan `->default(true)` o `->default(false)` según corresponda.

### Ejemplo de migración para un módulo de productos

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Codigo unico del producto');
            $table->string('name', 120)->comment('Nombre del producto');
            $table->text('description')->nullable()->comment('Descripcion del producto');
            $table->decimal('price', 10, 2)->comment('Precio del producto');
            $table->boolean('is_active')->default(true)->comment('Indica si el producto esta activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

---

## 4. Paso 2 — Modelo

### Comando

```bash
php artisan make:model Product --no-interaction
```

### Reglas a seguir

- Usar el atributo PHP 8 `#[Fillable([...])]` (no la propiedad `$fillable`). Ver todos los modelos existentes.
- Usar el atributo `#[Hidden([...])]` para ocultar campos sensibles (como `password`).
- Siempre declarar el `use HasFactory<NombreFactory>` con el genérico correcto en el docblock.
- Si el modelo debe auditarse (cambios rastreados automáticamente), implementar `Auditable` y usar el trait `HasAuditableTags` (que ya incluye el trait de Owen-IT internamente).
- Si tiene campos a excluir de la auditoría, declarar `$auditExclude`.
- Declarar todas las relaciones Eloquent con sus tipos de retorno explícitos.
- Declarar el método `casts()` (no la propiedad `$casts`) para todos los campos con tipo especial.
- Si usa `SoftDeletes`, importar `Illuminate\Database\Eloquent\SoftDeletes`.

### Estructura del modelo

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasAuditableTags;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'code',
    'name',
    'description',
    'price',
    'is_active',
])]
class Product extends Model implements Auditable
{
    /** @use HasFactory<ProductFactory> */
    use HasAuditableTags, HasFactory, SoftDeletes;

    /**
     * Campos excluidos de la auditoría automática.
     *
     * @var array<int, string>
     */
    protected $auditExclude = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
```

> **Nota:** Si el modelo **no** requiere auditoría (p. ej. tablas de log o catálogos muy simples), omitir `implements Auditable`, el trait `HasAuditableTags` y usar solo `HasFactory`.

---

## 5. Paso 3 — Factory

### Comando

```bash
php artisan make:factory ProductFactory --model=Product --no-interaction
```

### Reglas a seguir

- El docblock `@extends Factory<NombreModelo>` es obligatorio.
- `definition()` debe devolver datos realistas usando `fake()` (no `$this->faker`).
- Crear **estados** (`public function nombreEstado(): static`) para variaciones que los tests necesiten frecuentemente (p. ej. `inactive()`, `superAdmin()`).
- Si el modelo tiene FK, referenciar la factory del modelo relacionado: `'product_category_id' => ProductCategory::factory()`.

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'        => strtoupper(fake()->unique()->bothify('PROD-###')),
            'name'        => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price'       => fake()->randomFloat(2, 1, 9999),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

---

## 6. Paso 4 — Seeder

### Comando

```bash
php artisan make:seeder ProductSeeder --no-interaction
```

### Reglas a seguir

- Usar `updateOrCreate()` en lugar de `create()` para que el seeder sea idempotente (re-ejecutable sin duplicar datos).
- Si el seeder es de datos iniciales del sistema (catálogos, permisos base), registrarlo en `DatabaseSeeder::run()` en el orden correcto.
- Los seeders de datos de prueba masivos usan factories: `Product::factory(50)->create()`.

```php
<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['LAPTOP-001', 'Laptop Estándar', 'Laptop para uso administrativo', 1500.00],
            ['MOUSE-001',  'Mouse Inalámbrico', null, 25.00],
        ])->each(fn (array $product) => Product::query()->updateOrCreate(
            ['code' => $product[0]],
            [
                'name'        => $product[1],
                'description' => $product[2],
                'price'       => $product[3],
                'is_active'   => true,
            ],
        ));
    }
}
```

Registrar en `DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        IdentificationTypeSeeder::class,
        PermissionSeeder::class,
        RoleSeeder::class,
        ProductSeeder::class, // ← agregar aquí en el orden adecuado
    ]);
    // ...
}
```

---

## 7. Paso 5 — Form Requests (Store y Update)

### Comando

```bash
php artisan make:request StoreProductRequest --no-interaction
php artisan make:request UpdateProductRequest --no-interaction
```

### Reglas a seguir

- El método `authorize()` **siempre** verifica el permiso correspondiente usando `$this->user()?->hasPermission('modulo.accion') ?? false`.
- El docblock de `rules()` debe ser `@return array<string, array<int, mixed>>`.
- En `StoreRequest`: las reglas de unicidad usan `'unique:tabla,columna'`.
- En `UpdateRequest`: las reglas de unicidad usan `Rule::unique('tabla', 'columna')->ignore($id)` donde `$id = $this->route('modelo')?->id`.
- En `StoreRequest` los campos obligatorios usan `'required'`; en `UpdateRequest` los mismos campos usan `'sometimes'` (actualización parcial).
- Los campos opcionales siempre usan `'nullable'` en ambos requests.

### StoreProductRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:30', 'unique:products,code'],
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
```

### UpdateProductRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'code'        => ['sometimes', 'string', 'max:30', Rule::unique('products', 'code')->ignore($productId)],
            'name'        => ['sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
```

---

## 8. Paso 6 — API Resource

### Comando

```bash
php artisan make:resource ProductResource --no-interaction
```

### Reglas a seguir

- Extender `JsonResource` (no `ResourceCollection`; las colecciones se crean con `ProductResource::collection($paginator)`).
- El docblock de `toArray()` debe ser `@return array<string, mixed>`.
- Siempre exponer `id`, `created_at`, `updated_at`.
- Las relaciones cargadas se exponen con `$this->whenLoaded('nombreRelacion')` envueltas en su propio Resource.
- El campo `full_name` o campos computados similares se calculan directamente en el resource (ver `UserResource`).
- No exponer campos sensibles ni campos internos que el cliente no necesite.

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => $this->price,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
```

---

## 9. Paso 7 — Controlador

### Comando

```bash
php artisan make:controller Api/ProductController --no-interaction
```

### Reglas a seguir

- **Siempre** extender `BaseController` (no el `Controller` base).
- **Siempre** usar el trait `FiltersPaginatedResults` para el método `getAll`.
- Declarar tres arrays protegidos: `$searchable`, `$filterable`, `$sortable`.
- El método `getAll` recibe `Request $request` y devuelve `JsonResponse`.
- Los métodos `store`, `show`, `update`, `destroy` siguen el patrón de `apiResource`.
- La autorización en `show` y `destroy` se hace con `abort_unless(request()->user()->hasPermission(...), 403)`.
- La autorización en `store` y `update` se delega completamente al Form Request (método `authorize()`).
- Las operaciones que modifican múltiples tablas se envuelven en `DB::transaction()`.
- Las respuestas de éxito usan `$this->sendResponse(Resource, 'Mensaje', HttpCode)`.
- Las respuestas de error usan `$this->sendError('Mensaje', [], HttpCode)`.
- El código HTTP 201 se usa solo en `store`. El resto usa 200 implícitamente.
- La eliminación retorna `new \stdClass` como `data` (objeto vacío, no array).
- Lógica auxiliar (p. ej. sync de relaciones) va en métodos `private` al fondo del controlador.

### Estructura del controlador

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends BaseController
{
    use FiltersPaginatedResults;

    /**
     * Columnas en las que se aplica búsqueda general (LIKE %term%).
     *
     * @var array<int, string>
     */
    protected array $searchable = [
        'code',
        'name',
        'description',
    ];

    /**
     * Columnas que se pueden filtrar individualmente.
     *
     * @var array<int, string>
     */
    protected array $filterable = [
        'code',
        'name',
        'is_active',
    ];

    /**
     * Columnas por las que se puede ordenar.
     *
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'code',
        'name',
        'price',
        'created_at',
    ];

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('products.view'), Response::HTTP_FORBIDDEN);

        $products = $this->filterPaginated(
            query: Product::query(),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            ProductResource::collection($products),
            'Productos consultados correctamente',
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return $this->sendResponse(
            ProductResource::make($product),
            'Producto creado correctamente',
            Response::HTTP_CREATED,
        );
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('products.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse(ProductResource::make($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return $this->sendResponse(
            ProductResource::make($product),
            'Producto actualizado correctamente',
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('products.delete'), Response::HTTP_FORBIDDEN);

        $product->delete();

        return $this->sendResponse(new \stdClass, 'Producto eliminado correctamente');
    }
}
```

> **Tip — Relaciones many-to-many:** Si el módulo requiere sincronizar relaciones (como roles → permisos o usuarios → roles), agregar un método `private` al final del controlador y llamarlo dentro de `DB::transaction()`. Ver `UserController::syncRoles()` como referencia.

---

## 10. Paso 8 — Rutas

Las rutas de todos los módulos van en `routes/web.php`, dentro del grupo ya existente:

```php
Route::prefix('api')->name('api.')->group(function (): void {
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        // ... rutas existentes ...
    });
});
```

### Patrón a replicar exactamente

```php
Route::post('products/get-all', [ProductController::class, 'getAll'])->name('products.get-all');
Route::apiResource('products', ProductController::class)->except(['index']);
```

### Por qué `except(['index'])`

El endpoint de listado/paginado se expone como `POST /api/products/get-all` (no como `GET /api/products`) para permitir enviar filtros complejos en el body de la petición. Por eso siempre se excluye `index` del `apiResource` y se agrega `getAll` manualmente como `POST`.

### Rutas generadas por `apiResource` (sin `index`)

| Método HTTP | URI | Nombre de ruta | Acción |
|---|---|---|---|
| `POST` | `/api/products/get-all` | `api.products.get-all` | `getAll` |
| `POST` | `/api/products` | `api.products.store` | `store` |
| `GET` | `/api/products/{product}` | `api.products.show` | `show` |
| `PUT/PATCH` | `/api/products/{product}` | `api.products.update` | `update` |
| `DELETE` | `/api/products/{product}` | `api.products.destroy` | `destroy` |

### Import en web.php

```php
use App\Http\Controllers\Api\ProductController;
```

---

## 11. Paso 9 — Permisos

### Convención de nombres

Los permisos siguen el patrón `{modulo}.{accion}` en kebab-case. Las acciones estándar son `view`, `create`, `update`, `delete`.

### Agregar al PermissionSeeder

Abrir `database/seeders/PermissionSeeder.php` y agregar las entradas al array:

```php
collect([
    // ... permisos existentes ...
    ['products.view',   'Ver productos',        'Permite listar y consultar productos'],
    ['products.create', 'Crear productos',       'Permite crear productos'],
    ['products.update', 'Actualizar productos',  'Permite actualizar productos'],
    ['products.delete', 'Eliminar productos',    'Permite eliminar productos'],
])->each(fn (array $permission) => Permission::query()->updateOrCreate(
    ['name' => $permission[0], 'guard_name' => 'web'],
    ['display_name' => $permission[1], 'description' => $permission[2]],
));
```

### Asignar permisos a roles en RoleSeeder

Abrir `database/seeders/RoleSeeder.php` y agregar los permisos del nuevo módulo a los roles que correspondan:

```php
'admin' => [
    // ...permisos existentes...
    'permissions' => [
        // ...
        'products.view',
        'products.create',
        'products.update',
    ],
],
```

> **Importante:** El rol `super-admin` NO necesita actualización. El método `User::hasPermission()` devuelve `true` para ese rol sin importar qué permiso se verifique.

### Re-ejecutar seeders

```bash
php artisan db:seed --class=PermissionSeeder --no-interaction
php artisan db:seed --class=RoleSeeder --no-interaction
```

---

## 12. Ejemplo completo: Módulo `products`

### Orden de ejecución de comandos

```bash
# 1. Migración
php artisan make:migration create_products_table --no-interaction

# 2. Modelo
php artisan make:model Product --no-interaction

# 3. Factory
php artisan make:factory ProductFactory --model=Product --no-interaction

# 4. Seeder
php artisan make:seeder ProductSeeder --no-interaction

# 5. Form Requests
php artisan make:request StoreProductRequest --no-interaction
php artisan make:request UpdateProductRequest --no-interaction

# 6. API Resource
php artisan make:resource ProductResource --no-interaction

# 7. Controlador
php artisan make:controller Api/ProductController --no-interaction

# 8. Ejecutar migración
php artisan migrate --no-interaction

# 9. Ejecutar seeders de permisos
php artisan db:seed --class=PermissionSeeder --no-interaction
php artisan db:seed --class=RoleSeeder --no-interaction

# 10. Formatear código con Pint
vendor/bin/pint --dirty --format agent
```

### Formato de respuesta JSON estándar (éxito)

```json
{
    "code": 200,
    "success": true,
    "message": "Productos consultados correctamente",
    "data": {
        "current_page": 1,
        "data": [ { "id": 1, "code": "PROD-001", "name": "...", "..." : "..." } ],
        "total": 1,
        "per_page": 10
    }
}
```

### Formato de respuesta JSON estándar (error)

```json
{
    "code": 403,
    "success": false,
    "message": "This action is unauthorized.",
    "data": {}
}
```

### Parámetros de la petición `getAll` (POST)

| Parámetro | Tipo | Descripción |
|---|---|---|
| `general` | `string` | Búsqueda libre sobre todas las columnas `$searchable` |
| `{columna}` | `string\|bool\|array` | Filtro exacto sobre columnas `$filterable` |
| `per_page` | `int` | Registros por página (máx. 100, por defecto 10) |
| `page` | `int` | Número de página |
| `sort_by` | `string` | Columna de ordenamiento (debe estar en `$sortable`) |
| `sort_direction` | `asc\|desc` | Dirección del ordenamiento |

---

## 13. Checklist final

Antes de dar por terminado un módulo, verificar que:

- [ ] La migración tiene `->comment()` en cada columna relevante
- [ ] El modelo usa `#[Fillable([...])]` (atributo PHP 8, no propiedad)
- [ ] El modelo implementa `Auditable` y usa `HasAuditableTags` si requiere trazabilidad
- [ ] El modelo tiene `casts()` como método (no como propiedad `$casts`)
- [ ] La factory tiene el docblock `@extends Factory<Modelo>` y estados útiles
- [ ] Los seeders usan `updateOrCreate()` (idempotentes)
- [ ] El `StoreRequest::authorize()` usa `hasPermission('modulo.create')`
- [ ] El `UpdateRequest::authorize()` usa `hasPermission('modulo.update')`
- [ ] El `UpdateRequest` usa `Rule::unique(...)->ignore($id)` para unicidad
- [ ] El `UpdateRequest` usa `'sometimes'` en lugar de `'required'` para campos opcionales en update
- [ ] El API Resource expone solo los campos que el cliente necesita
- [ ] El API Resource usa `$this->whenLoaded('relacion')` para relaciones
- [ ] El controlador extiende `BaseController`
- [ ] El controlador usa el trait `FiltersPaginatedResults`
- [ ] El `show` y `destroy` verifican permiso con `abort_unless(...hasPermission(...))`
- [ ] La ruta sigue el patrón `POST .../get-all` + `apiResource()->except(['index'])`
- [ ] Los permisos están agregados en `PermissionSeeder`
- [ ] Los permisos están asignados a los roles correctos en `RoleSeeder`
- [ ] Se ejecutó `vendor/bin/pint --dirty --format agent` al final
