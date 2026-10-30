<?php

namespace App\Console\Commands;

use App\Models\IdentificationType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Importa usuarios del panel interno desde un CSV.
 *
 * Columnas (primera fila = encabezado):
 *   tipo_documento, numero_documento, usuario, primer_nombre,
 *   segundo_nombre, primer_apellido, segundo_apellido, correo,
 *   cargo, rol, contrasena
 *
 * Obligatorias: tipo_documento, numero_documento, usuario,
 * primer_nombre, primer_apellido, correo, rol.
 * El resto son opcionales; sin contrasena queda la temporal
 * "Referencia.2026" con cambio obligatorio al primer ingreso.
 */
class ImportarUsuarios extends Command
{
    protected $signature = 'usuarios:importar {archivo : Ruta del archivo CSV}';

    protected $description = 'Crea usuarios internos en lote desde un CSV';

    private const CONTRASENA_TEMPORAL = 'Referencia.2026';

    public function handle(): int
    {
        $ruta = (string) $this->argument('archivo');

        if (! is_file($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");

            return self::FAILURE;
        }

        $filas = $this->leerCsv($ruta);

        if ($filas === null) {
            return self::FAILURE;
        }

        $tipos = IdentificationType::query()->pluck('id', 'code');
        $rolesDisponibles = Role::query()->pluck('id', 'name');

        $creados = 0;
        $omitidos = [];
        $errores = [];

        foreach ($filas as $indice => $fila) {
            $linea = $indice + 2; // +1 por encabezado, +1 por base 1

            $datos = [
                'tipo_documento' => trim((string) ($fila['tipo_documento'] ?? '')),
                'numero_documento' => trim((string) ($fila['numero_documento'] ?? '')),
                'usuario' => trim((string) ($fila['usuario'] ?? '')),
                'primer_nombre' => trim((string) ($fila['primer_nombre'] ?? '')),
                'segundo_nombre' => trim((string) ($fila['segundo_nombre'] ?? '')),
                'primer_apellido' => trim((string) ($fila['primer_apellido'] ?? '')),
                'segundo_apellido' => trim((string) ($fila['segundo_apellido'] ?? '')),
                'correo' => trim((string) ($fila['correo'] ?? '')),
                'cargo' => trim((string) ($fila['cargo'] ?? '')),
                'rol' => trim((string) ($fila['rol'] ?? '')),
                'contrasena' => trim((string) ($fila['contrasena'] ?? '')),
            ];

            $tipoId = $tipos->get(strtoupper($datos['tipo_documento']));

            $validator = Validator::make($datos, [
                'numero_documento' => ['required', 'string', 'max:30'],
                'usuario' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/'],
                'primer_nombre' => ['required', 'string', 'max:80'],
                'segundo_nombre' => ['nullable', 'string', 'max:80'],
                'primer_apellido' => ['required', 'string', 'max:80'],
                'segundo_apellido' => ['nullable', 'string', 'max:80'],
                'correo' => ['required', 'email', 'max:255'],
                'cargo' => ['nullable', 'string', 'max:250'],
                'contrasena' => ['nullable', Password::min(8)->mixedCase()->numbers()->symbols()],
            ]);

            if (! $tipoId) {
                $validator->after(fn ($v) => $v->errors()->add('tipo_documento', "Tipo de documento desconocido: {$datos['tipo_documento']}"));
            }
            if (! $rolesDisponibles->has($datos['rol'])) {
                $validator->after(fn ($v) => $v->errors()->add('rol', "Rol desconocido: {$datos['rol']} (use: ".$rolesDisponibles->keys()->implode(', ').')'));
            }

            if ($validator->fails()) {
                $errores[] = "Línea {$linea} ({$datos['usuario']}): ".implode(' | ', $validator->errors()->all());

                continue;
            }

            $duplicado = User::query()
                ->where('user_name', $datos['usuario'])
                ->orWhere('email', $datos['correo'])
                ->orWhere(fn ($q) => $q->where('identification_type_id', $tipoId)->where('identification_number', $datos['numero_documento']))
                ->exists();

            if ($duplicado) {
                $omitidos[] = "Línea {$linea} ({$datos['usuario']}): ya existe usuario, correo o documento";

                continue;
            }

            try {
                DB::transaction(function () use ($datos, $tipoId, $rolesDisponibles): void {
                    $user = User::create([
                        'identification_type_id' => $tipoId,
                        'identification_number' => $datos['numero_documento'],
                        'user_name' => $datos['usuario'],
                        'first_name' => $datos['primer_nombre'],
                        'middle_name' => $datos['segundo_nombre'] ?: null,
                        'last_name' => $datos['primer_apellido'],
                        'sur_name' => $datos['segundo_apellido'] ?: null,
                        'email' => $datos['correo'],
                        'job_title' => $datos['cargo'] ?: null,
                        'password' => Hash::make($datos['contrasena'] ?: self::CONTRASENA_TEMPORAL),
                        'is_active' => true,
                        'must_change_password' => true,
                    ]);

                    $user->roles()->sync([$rolesDisponibles->get($datos['rol'])]);
                });
                $creados++;
            } catch (Throwable $e) {
                $errores[] = "Línea {$linea} ({$datos['usuario']}): {$e->getMessage()}";
            }
        }

        $this->info("Usuarios creados: {$creados}");
        foreach ($omitidos as $mensaje) {
            $this->warn("Omitido — {$mensaje}");
        }
        foreach ($errores as $mensaje) {
            $this->error($mensaje);
        }

        if ($creados > 0) {
            $this->line('Los usuarios sin contraseña propia quedaron con la temporal "'.self::CONTRASENA_TEMPORAL.'" y cambio obligatorio al primer ingreso.');
        }

        return $errores === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<int, array<string, string>>|null */
    private function leerCsv(string $ruta): ?array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            $this->error("No se pudo leer el archivo: {$ruta}");

            return null;
        }

        // El Excel en español suele exportar con BOM UTF-8 y separador ";".
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        $lineas = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $contenido)), fn ($l) => $l !== ''));

        if ($lineas === []) {
            $this->error('El archivo está vacío.');

            return null;
        }

        $separador = str_contains($lineas[0], ';') && ! str_contains($lineas[0], ',') ? ';' : ',';
        $encabezado = array_map(fn ($c) => strtolower(trim($c)), str_getcsv($lineas[0], $separador));
        unset($lineas[0]);

        $filas = [];
        foreach ($lineas as $linea) {
            $valores = str_getcsv($linea, $separador);
            $valores = array_pad(array_slice($valores, 0, count($encabezado)), count($encabezado), '');
            $filas[] = array_combine($encabezado, $valores) ?: [];
        }

        return $filas;
    }
}
