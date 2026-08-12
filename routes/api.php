<?php

use Illuminate\Support\Facades\Route;

$apiRoutes = [
    __DIR__ . '/api/auth.php',
    __DIR__ . '/api/public.php',
    __DIR__ . '/api/schedules.php',
    __DIR__ . '/api/appointments.php',
    __DIR__ . '/api/consultations.php',
    __DIR__ . '/api/patients.php',
    __DIR__ . '/api/prescriptions.php',
    __DIR__ . '/api/documents.php',
    __DIR__ . '/api/quotes.php',
    __DIR__ . '/api/billing.php',
    __DIR__ . '/api/pharmacy.php',
    __DIR__ . '/api/laboratory.php',
    __DIR__ . '/api/medical_supply.php',
    __DIR__ . '/api/clinics.php',
    __DIR__ . '/api/patient_portal.php',
    __DIR__ . '/api/misc.php',
];

foreach ($apiRoutes as $routeFile) {
    if (file_exists($routeFile)) {
        require $routeFile;
    }
}
