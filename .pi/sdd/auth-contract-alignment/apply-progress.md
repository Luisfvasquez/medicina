# SDD Apply Progress — Auth Contract Alignment

**Change**: auth-contract-alignment
**Date**: 2026-06-30
**Status**: apply_complete

## Implemented Work Units

- [x] WU-1: Infraestructura — config/otp.php, migración, OtpCode, OtpAttempt
- [x] WU-2: Excepciones — ApiException + 8 excepciones auth + handler RFC 7807 en bootstrap/app.php
- [x] WU-3: Servicios — OtpService, AuthResponseService, WhatsAppChannel, EmailChannel, Interface
- [x] WU-4: Requests — SendOtpRequest, VerifyOtpRequest, LoginPasswordRequest
- [x] WU-5: Controladores — AuthController, OtpController, UserResource, PatientResource + refactor UserAuthController y PatientAuthController
- [x] WU-6: Routes + Middleware — nuevas rutas planas, deprecated en rutas antiguas, DeprecatedRoute middleware
- [x] WU-7: Tests — OtpFlowTest, LoginPasswordTest, AuthContractTest
- [x] WU-8: Docs — doc-integrations/api-auth.md

## Verification

- 16 rutas de auth registradas y verificadas (`php artisan route:list`)
- 21 archivos PHP sin errores de sintaxis
- LSP diagnostics: solo hints menores (imports no usados, corregidos)
- Routes deprecated con Warning header funcionando
- Handler global RFC 7807 en bootstrap/app.php

## Pending

- Tests no pudieron ejecutarse (timeout en environment)
- Migración no corrida (requiere DB disponible)
