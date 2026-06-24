# Especificaciones de Sincronización Offline (Push & Pull) para el Backend (Laravel)

Este documento detalla los requerimientos, el diseño técnico y las políticas de sincronización del endpoint unificado que debe implementar el agente de backend para soportar la arquitectura Offline-First de LUCA.

---

## 1. Estrategia General: Push & Pull Unificado (Bulk Sync)

Para garantizar la integridad de los datos en conexiones inestables, utilizaremos un único endpoint de sincronización por lotes en lugar de múltiples llamadas REST individuales:

* **Ruta**: `POST /api/sync`
* **Autenticación**: `Bearer Token` (JWT)
* **Payload**: Un lote de cambios locales (`push`) ordenados y clasificados por entidad, junto con el timestamp de la última sincronización exitosa del cliente (`last_sync_timestamp`) para obtener las actualizaciones del servidor (`pull`).

---

## 2. Requerimientos de Base de Datos y Modelos

### A. Identificadores Únicos (UUID)
* El frontend generará los UUIDs (`uuid`) localmente al crear registros offline (Pacientes, Consultas, Citas, etc.).
* **Mapeo Híbrido**: El backend debe almacenar y exponer estos UUIDs. Puede usar internamente claves primarias secuenciales (`id` autoincrementales) para velocidad de joins, pero todas las API-requests y relaciones externas deben resolverse mediante los UUIDs provistos por el cliente.

### B. Control de Versiones y Auditoría
* Todas las tablas sincronizables deben poseer campos `updated_at` con precisión de milisegundos.
* Se requiere soporte de Soft Delete (`deleted_at`) para poder notificar la eliminación de registros al cliente durante la fase de Pull.

---

## 3. Especificación del Endpoint `POST /api/sync`

### A. Estructura de la Petición (Request JSON)

El cliente envía su cola de cambios y el timestamp de su última sincronización.

```json
{
  "last_sync_timestamp": "2026-06-23T20:00:00.000Z",
  "push": {
    "patients": [
      {
        "uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
        "full_name": "Juan Pérez",
        "email": "juan@gmail.com",
        "phone": "+584121234567",
        "city_id": 1,
        "updated_at": "2026-06-23T21:40:00.000Z"
      }
    ],
    "consultations": [
      {
        "uuid": "e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6",
        "patient_uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
        "reason": "Control de tensión arterial",
        "diagnosis": "Hipertensión controlada",
        "treatment": "Mantener Losartán 50mg",
        "updated_at": "2026-06-23T21:45:00.000Z"
      }
    ]
  }
}
```

### B. Lógica del Servidor (Procesamiento del Push)

1. **Transaccionalidad**: Todo el procesamiento debe ocurrir dentro de una transacción de base de datos (`DB::beginTransaction()`). Si ocurre un error crítico del sistema, se realiza rollback completo.
2. **Orden Topológico de Inserción**: El servidor debe procesar las colecciones del bloque `push` en un orden estricto de dependencias para evitar violaciones de clave foránea (FK):
   * **Primero**: Catálogos e independientes (ej. `patients`).
   * **Segundo**: Entidades dependientes (ej. `consultations` que dependen de `patient_uuid`).
   * **Tercero**: Recetas y archivos (ej. `prescriptions`, `documents` que dependen de `consultation_uuid`).
3. **Upsert con Resolución de Conflictos (Last-Write-Wins)**:
   * Si el registro con ese `uuid` no existe, se inserta.
   * Si existe, se compara el `updated_at` recibido con el de la base de datos:
     * Si la petición es más nueva, se actualiza el registro.
     * Si la base de datos es más nueva, se ignora el cambio del cliente y se reporta en la sección de conflictos del response.

---

## 4. Políticas de Sincronización Detalladas

### A. Manejo de Datos Binarios (Fotos, PDFs, Documentos)
* **Metadata Primero**: En el JSON de `/api/sync`, los archivos solo se envían como registros de metadatos (UUID, nombre, tipo, tamaño) con la bandera `pending_upload: true`.
* **Multipart Secuencial**: Una vez finalizada la sincronización del JSON, el cliente realiza subidas individuales por segundo plano a la ruta `POST /api/documents/upload` enviando el archivo binario real y su `uuid` usando `multipart/form-data`.
* **Confirmación**: Al subirse con éxito, el backend actualiza `pending_upload: false` en la DB, y el cliente borra el binario local del disco para liberar almacenamiento en el dispositivo.

### B. Conflictos de Relaciones (Ej: Claves Foráneas Inexistentes)
* **Falla Selectiva**: Si un registro falla por validación de relaciones (ej. `city_id` hace referencia a una ciudad que fue eliminada del servidor), el backend **NO debe abortar toda la transacción**.
* **Gestión de Errores por Lote**: El backend guarda los registros válidos, pero rechaza el registro conflictivo, devolviendo su UUID, el campo afectado (`field`) y el motivo del fallo en el array de errores del JSON de respuesta:
  ```json
  "push_results": {
    "patients": {
      "success": [],
      "errors": [
        {
          "uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
          "field": "city_id",
          "message": "La ciudad seleccionada no existe en el sistema."
        }
      ]
    }
  }
  ```
* El frontend recibirá esto, mantendrá el registro offline marcado con error y solicitará corrección manual al usuario (resaltando el campo afectado en la UI).

### C. Autenticación y Renovación Offline
* Si el token JWT del médico expira durante los días de trabajo offline:
  * Al volver a estar online, el cliente intentará usar su `refresh_token` en segundo plano para obtener un nuevo token.
  * Si la renovación falla (refresh token expirado), el frontend presentará un prompt de login. Una vez ingresada la contraseña y autenticado por el servidor, los datos locales en IndexedDB se conservan y se gatilla inmediatamente el `/api/sync` pendiente.

### D. Velocidad de Sincronización y Paginación (Pull)
* **Límite de Carga**: En la fase de `pull`, para evitar peticiones masivas lentas, el servidor debe retornar un máximo de **500 registros** por llamada.
* **Paginación por Timestamp**: Si hay más de 500 registros actualizados en el servidor desde la última sincronización del cliente, el backend devolverá la bandera `"has_more": true` y el nuevo `sync_timestamp` correspondiente a la tanda actual. El cliente procesará el lote y realizará llamadas consecutivas automáticas hasta que `"has_more": false`.

---

## 5. Estructura de la Respuesta (Response JSON)

```json
{
  "sync_timestamp": "2026-06-23T22:05:00.000Z",
  "has_more": false,
  "push_results": {
    "patients": {
      "success": ["4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab"],
      "errors": []
    },
    "consultations": {
      "success": ["e93ffdf6-982c-4afc-62c3-a2d9e93ffdf6"],
      "errors": []
    }
  },
  "pull": {
    "patients": [],
    "consultations": [],
    "appointments": [
      {
        "uuid": "727fe296-80bf-a4bf-84b9-e93ffdf6a4bc",
        "patient_uuid": "4afc62c3-982c-47bb-a2d9-e93ffdf6a4ab",
        "date": "2026-06-24T10:00:00.000Z",
        "status": "CONFIRMADA",
        "updated_at": "2026-06-23T21:10:00.000Z"
      }
    ]
  }
}
```
