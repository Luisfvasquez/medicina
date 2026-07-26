<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ProviderService;
use App\Models\User;
use App\Models\Clinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // =========================================================================
            // 1. CONSULTAS MÉDICAS (CONSULTATION)
            // =========================================================================
            [
                'name' => 'Consulta de Medicina General',
                'category' => 'CONSULTATION',
                'code' => 'MED-GEN-01',
                'description' => 'Evaluación médica integral, diagnóstico inicial, orientación preventiva y prescripción de tratamientos.',
                'base_price' => 30.00,
            ],
            [
                'name' => 'Consulta de Pediatría Integral',
                'category' => 'CONSULTATION',
                'code' => 'PED-INT-02',
                'description' => 'Control de crecimiento y desarrollo del lactante, niño y adolescente, esquema de vacunación y patologías pediátricas.',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Consulta de Cardiología y Evaluación Cardiovascular',
                'category' => 'CONSULTATION',
                'code' => 'CAR-EVAL-03',
                'description' => 'Evaluación de hipertensión arterial, riesgo cardiovascular, arritmias y cardiopatías con auscultación avanzada.',
                'base_price' => 50.00,
            ],
            [
                'name' => 'Consulta de Ginecología y Obstetricia',
                'category' => 'CONSULTATION',
                'code' => 'GIN-OBS-04',
                'description' => 'Control prenatal, evaluación ginecológica preventiva, salud reproductiva y asesoría anticonceptiva.',
                'base_price' => 45.00,
            ],
            [
                'name' => 'Consulta de Traumatología y Ortopedia',
                'category' => 'CONSULTATION',
                'code' => 'TRA-ORT-05',
                'description' => 'Evaluación de fracturas, esguinces, lesiones articulares, dolor de columna y deformidades óseas.',
                'base_price' => 40.00,
            ],
            [
                'name' => 'Consulta de Dermatología Clínica',
                'category' => 'CONSULTATION',
                'code' => 'DER-CLI-06',
                'description' => 'Diagnóstico y tratamiento de acné, dermatitis, psoriasis, alopecia, revisión de lunares y lesiones cutáneas.',
                'base_price' => 45.00,
            ],
            [
                'name' => 'Consulta de Oftalmología y Refracción',
                'category' => 'CONSULTATION',
                'code' => 'OFT-REF-07',
                'description' => 'Examen de agudeza visual, prescripción de lentes, toma de presión intraocular y revisión de fondo de ojo.',
                'base_price' => 40.00,
            ],
            [
                'name' => 'Consulta de Neurología Clínica',
                'category' => 'CONSULTATION',
                'code' => 'NEU-CLI-08',
                'description' => 'Evaluación de cefaleas, migrañas, neuropatías, mareos, convulsiones y trastornos del sueño.',
                'base_price' => 55.00,
            ],
            [
                'name' => 'Consulta de Endocrinología y Metabolismo',
                'category' => 'CONSULTATION',
                'code' => 'END-MET-09',
                'description' => 'Manejo especializado de diabetes mellitus, patologías de la tiroides, obesidad y alteraciones hormonales.',
                'base_price' => 50.00,
            ],
            [
                'name' => 'Consulta de Gastroenterología',
                'category' => 'CONSULTATION',
                'code' => 'GAS-CLI-10',
                'description' => 'Evaluación de reflujo, gastritis, colon irritable, enfermedad hepática y trastornos digestivos.',
                'base_price' => 45.00,
            ],
            [
                'name' => 'Consulta de Urología',
                'category' => 'CONSULTATION',
                'code' => 'URO-CLI-11',
                'description' => 'Revisión prostática, infección de vías urinarias, cálculos renales y disfunción eréctil.',
                'base_price' => 50.00,
            ],
            [
                'name' => 'Consulta de Psiquiatría y Salud Mental',
                'category' => 'CONSULTATION',
                'code' => 'PSI-CLI-12',
                'description' => 'Evaluación de cuadros ansiosos, depresivos, manejo farmacológico y salud emocional.',
                'base_price' => 50.00,
            ],
            [
                'name' => 'Consulta de Nutrición y Dietética Clínica',
                'category' => 'CONSULTATION',
                'code' => 'NUT-CLI-13',
                'description' => 'Plan de alimentación personalizado, evaluación antropométrica y bioimpedancia.',
                'base_price' => 30.00,
            ],
            [
                'name' => 'Consulta de Otorrinolaringología',
                'category' => 'CONSULTATION',
                'code' => 'ORL-CLI-14',
                'description' => 'Evaluación de otitis, sinusitis, rinitis alérgica, vértigo y afonía con otoscopia y rinoscopia.',
                'base_price' => 45.00,
            ],

            // =========================================================================
            // 2. IMÁGENES Y ULTRASONIDO (IMAGING)
            // =========================================================================
            [
                'name' => 'Ecocardiograma Doppler Color Adulto',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-DOP',
                'description' => 'Ultrasonido transtorácico para evaluar anatomía valvular, fracción de eyección y función cardíaca.',
                'base_price' => 60.00,
            ],
            [
                'name' => 'Ecografía Abdominal Superior y Pelviana',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-ABD',
                'description' => 'Ultrasonido de hígado, vesícula, vías biliares, páncreas, bazo, riñones y cavidad pélvica.',
                'base_price' => 45.00,
            ],
            [
                'name' => 'Ecografía Tiroidea y Partes Blandas',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-TIR',
                'description' => 'Evaluación ecográfica de la glándula tiroides, nódulos y cadenas ganglionares cervicales.',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Ecografía Mamaria Bilateral',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-MAM',
                'description' => 'Ultrasonido de tejido mamario y huecos axilares para detección de quistes o nódulos.',
                'base_price' => 40.00,
            ],
            [
                'name' => 'Ecografía Obstétrica de Alta Resolución',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-OBS',
                'description' => 'Valoración del desarrollo fetal, placenta, líquido amniótico y biometría fetal.',
                'base_price' => 40.00,
            ],
            [
                'name' => 'Ecografía Renal y Vías Urinarias',
                'category' => 'IMAGING',
                'code' => 'IMG-ECO-REN',
                'description' => 'Ultrasonido enfocado en parénquima renal, vejiga y descarte de hidronefrosis o litiasis.',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Radiografía de Tórax (Posteroanterior y Lateral)',
                'category' => 'IMAGING',
                'code' => 'IMG-RX-TOR',
                'description' => 'Estudio radiológico convencional de los campos pulmonares, silueta cardíaca y tórax óseo.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Radiografía de Columna Lumbar (AP y Lateral)',
                'category' => 'IMAGING',
                'code' => 'IMG-RX-LUM',
                'description' => 'Radiografía digital para descarte de discopatías, escoliosis o cambios artrósicos.',
                'base_price' => 30.00,
            ],
            [
                'name' => 'Tomografía Axial Computarizada (TAC) de Cráneo',
                'category' => 'IMAGING',
                'code' => 'IMG-TAC-CRA',
                'description' => 'TAC simple de cerebro para descarte de ACV, hemorragias o lesiones ocupantes de espacio.',
                'base_price' => 120.00,
            ],
            [
                'name' => 'Resonancia Magnética Nuclear (RMN) Cerebral',
                'category' => 'IMAGING',
                'code' => 'IMG-RMN-CER',
                'description' => 'RMN de alta definición para caracterización de tejido nervioso cerebral y tronco encefálico.',
                'base_price' => 180.00,
            ],
            [
                'name' => 'Mamografía Digital Bilateral',
                'category' => 'IMAGING',
                'code' => 'IMG-MAM-DIG',
                'description' => 'Estudio radiológico mamario de tamizaje para prevención y diagnóstico temprano.',
                'base_price' => 45.00,
            ],

            // =========================================================================
            // 3. LABORATORIO CLÍNICO (LAB)
            // =========================================================================
            [
                'name' => 'Perfil Veinte Completo (Lab Perfil 20)',
                'category' => 'LAB',
                'code' => 'LAB-PER-20',
                'description' => 'Panel sanguíneo integral: Hemograma, Glicemia, Urea, Creatinina, Ácido Úrico, Colesterol, Triglicéridos, TGO, TGP y VDRL.',
                'base_price' => 20.00,
            ],
            [
                'name' => 'Hematología Completa y Plaquetas',
                'category' => 'LAB',
                'code' => 'LAB-HEM-COM',
                'description' => 'Conteo de glóbulos rojos, glóbulos blancos, fórmula leucocitaria, hemoglobina, hematocrito y conteo plaquetario.',
                'base_price' => 8.00,
            ],
            [
                'name' => 'Glicemia en Ayunas y Posprandial',
                'category' => 'LAB',
                'code' => 'LAB-GLI-AYU',
                'description' => 'Determinación de niveles de glucosa basal en sangre y respuesta glicémica post-comida.',
                'base_price' => 5.00,
            ],
            [
                'name' => 'Perfil Lipídico Completo',
                'category' => 'LAB',
                'code' => 'LAB-LIP-COM',
                'description' => 'Colesterol Total, Colesterol HDL, Colesterol LDL, VLDL y Triglicéridos.',
                'base_price' => 12.00,
            ],
            [
                'name' => 'Perfil Tiroideo (T3, T4 Libre y TSH Ultrasensible)',
                'category' => 'LAB',
                'code' => 'LAB-TIR-COM',
                'description' => 'Valoración hormonal completa de la función tiroidea.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Examen General de Orina (EGO)',
                'category' => 'LAB',
                'code' => 'LAB-EGO-ORI',
                'description' => 'Análisis físico, químico y examen microscópico del sedimento urinario.',
                'base_price' => 5.00,
            ],
            [
                'name' => 'Urocultivo con Antibiograma',
                'category' => 'LAB',
                'code' => 'LAB-URO-CUL',
                'description' => 'Aislamiento microbiológico bacteriano en orina y sensibilidad antibiótica.',
                'base_price' => 15.00,
            ],
            [
                'name' => 'Examen Coprológico (Heces por Concentración)',
                'category' => 'LAB',
                'code' => 'LAB-COP-HEC',
                'description' => 'Análisis de muestra fecal para detección de parásitos, quistes, protozoarios y moco.',
                'base_price' => 5.00,
            ],
            [
                'name' => 'Pruebas de Función Hepática (TGO, TGP, Bilirrubinas, FA)',
                'category' => 'LAB',
                'code' => 'LAB-HEP-FUN',
                'description' => 'Enzimas hepáticas, bilirrubina total, directa e indirecta y fosfatasa alcalina.',
                'base_price' => 15.00,
            ],
            [
                'name' => 'Pruebas de Función Renal (Urea, Creatinina, Ácido Úrico)',
                'category' => 'LAB',
                'code' => 'LAB-REN-FUN',
                'description' => 'Evaluación de filtrado y excreción nitrogenada renal.',
                'base_price' => 10.00,
            ],
            [
                'name' => 'Electrolitos Séricos (Sodio, Potasio, Cloro)',
                'category' => 'LAB',
                'code' => 'LAB-ELE-SER',
                'description' => 'Medición cuantitativa de electrolitos en plasma sanguíneo.',
                'base_price' => 12.00,
            ],
            [
                'name' => 'Prueba de Embarazo Cuantitativa (Beta-HCG)',
                'category' => 'LAB',
                'code' => 'LAB-EMB-HCG',
                'description' => 'Cuantificación de Subunidad Beta de Gonadotropina Coriónica Humana en suero.',
                'base_price' => 10.00,
            ],
            [
                'name' => 'Antígeno Prostático Específico (PSA Total y Libre)',
                'category' => 'LAB',
                'code' => 'LAB-PSA-TOT',
                'description' => 'Marcador serológico para tamizaje y seguimiento prostático.',
                'base_price' => 20.00,
            ],

            // =========================================================================
            // 4. PROCEDIMIENTOS MÉDICOS (PROCEDURE)
            // =========================================================================
            [
                'name' => 'Electrocardiograma de 12 Derivaciones con Reporte',
                'category' => 'PROCEDURE',
                'code' => 'PRC-EKG-12D',
                'description' => 'Trazado eléctrico cardíaco en reposo con informe médico cardiológico.',
                'base_price' => 20.00,
            ],
            [
                'name' => 'Sutura Simple de Herida Superficial (< 5cm)',
                'category' => 'PROCEDURE',
                'code' => 'PRC-SUT-SIM',
                'description' => 'Afrontamiento de bordes de herida con anestesia local, sutura monofilamento y curación.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Retiro de Puntos de Sutura y Curación Plana',
                'category' => 'PROCEDURE',
                'code' => 'PRC-RET-SUT',
                'description' => 'Retiro antiséptico de material de sutura, evaluación de cicatrización y limpieza de herida.',
                'base_price' => 15.00,
            ],
            [
                'name' => 'Lavado Ótico Unilateral o Bilateral',
                'category' => 'PROCEDURE',
                'code' => 'PRC-LAV-OTI',
                'description' => 'Irrigación tibia con solución estéril para extracción de tapón de cerumen desimpactado.',
                'base_price' => 20.00,
            ],
            [
                'name' => 'Espirometría Simple con Prueba Broncodilatadora',
                'category' => 'PROCEDURE',
                'code' => 'PRC-ESP-SIM',
                'description' => 'Prueba de función pulmonar para medición de capacidad vital forzada (FVC y FEV1).',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Infiltración Articular o Peritendinosa',
                'category' => 'PROCEDURE',
                'code' => 'PRC-INF-ART',
                'description' => 'Aplicación intraarticular o periarticular de corticosteroide/anestésico local para alivio del dolor.',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Drenaje de Absceso o Hematoma Superficial',
                'category' => 'PROCEDURE',
                'code' => 'PRC-DRE-ABS',
                'description' => 'Incisón, vaciamiento, curación y colocación de drenaje bajo anestesia local.',
                'base_price' => 30.00,
            ],
            [
                'name' => 'Colposcopia y Toma de Citología Vaginal (Papanicolaou)',
                'category' => 'PROCEDURE',
                'code' => 'PRC-COL-PAP',
                'description' => 'Inspección magnificada del cérvix con ácido acético / lugol y toma de fijación citológica.',
                'base_price' => 35.00,
            ],
            [
                'name' => 'Extracción de Cuerpo Extraño (Oído, Nariz o Cutáneo)',
                'category' => 'PROCEDURE',
                'code' => 'PRC-EXT-CUE',
                'description' => 'Extracción instrumental segura de cuerpo extraño en cavidades accesibles.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Colocación / Retiro de Dispositivo Intrauterino (DIU / Implante)',
                'category' => 'PROCEDURE',
                'code' => 'PRC-DIU-INS',
                'description' => 'Procedimiento ginecológico estéril para inserción o extracción de anticonceptivo subdérmico o DIU.',
                'base_price' => 45.00,
            ],

            // =========================================================================
            // 5. TERAPIAS Y REHABILITACIÓN (THERAPY)
            // =========================================================================
            [
                'name' => 'Sesión de Fisioterapia y Rehabilitación Física (45 min)',
                'category' => 'THERAPY',
                'code' => 'TER-FIS-IND',
                'description' => 'Tratamiento personalizado con agente físico, electroterapia, ultrasonido y cinesiterapia.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Fisioterapia Respiratoria y Manejo de Secreciones',
                'category' => 'THERAPY',
                'code' => 'TER-FIS-RES',
                'description' => 'Técnicas de expansión pulmonar, drenaje postural y aspiración de secreciones en adultos o niños.',
                'base_price' => 30.00,
            ],
            [
                'name' => 'Nebulización Pediátrica o Adulto con Fármacos',
                'category' => 'THERAPY',
                'code' => 'TER-NEB-FAR',
                'description' => 'Administración inhalatoria de broncodilatadores / esteroides con mascarilla u oxígeno.',
                'base_price' => 10.00,
            ],
            [
                'name' => 'Terapia del Lenguaje y Foniatría Pediátrica',
                'category' => 'THERAPY',
                'code' => 'TER-LEN-PED',
                'description' => 'Estimulación del habla, articulación, deglución y comunicación infantil.',
                'base_price' => 25.00,
            ],
            [
                'name' => 'Psicoterapia Individual (50 min)',
                'category' => 'THERAPY',
                'code' => 'TER-PSI-IND',
                'description' => 'Sesión clínica de apoyo psicológico cognitivo-conductual o humanista.',
                'base_price' => 35.00,
            ],

            // =========================================================================
            // 6. OTROS SERVICIOS (OTHER)
            // =========================================================================
            [
                'name' => 'Certificado Médico de Salud y Aptitud Física',
                'category' => 'OTHER',
                'code' => 'OTH-CER-SAL',
                'description' => 'Evaluación física general y emisión de certificado oficial de salud para trámites laborales o universitarios.',
                'base_price' => 15.00,
            ],
            [
                'name' => 'Chequeo Médico Preventivo Escolar / Prevacacional',
                'category' => 'OTHER',
                'code' => 'OTH-CHE-ESC',
                'description' => 'Examen clínico integral para ingreso escolar, deportivo o actividades de campo.',
                'base_price' => 20.00,
            ],
            [
                'name' => 'Valoración Cardiovascular Preoperatoria',
                'category' => 'OTHER',
                'code' => 'OTH-VAL-PRE',
                'description' => 'Evaluación de riesgo quirúrgico (Goldman / ASA) con informe para equipo quirúrgico.',
                'base_price' => 45.00,
            ],
            [
                'name' => 'Consulta de Segunda Opinión Médica Especializada',
                'category' => 'OTHER',
                'code' => 'OTH-SEG-OPI',
                'description' => 'Revisión minuciosa de historia previa, estudios de laboratorio y biopsias para confirmación diagnóstica.',
                'base_price' => 60.00,
            ],
            [
                'name' => 'Teleconsulta Médica On Demand',
                'category' => 'OTHER',
                'code' => 'OTH-TEL-MED',
                'description' => 'Atención médica remota por videollamada cifrada con emisión de receta digital.',
                'base_price' => 25.00,
            ],
        ];

        // 1. Crear o actualizar Servicios en el Catálogo Maestro
        foreach ($services as $data) {
            Service::updateOrCreate(
                ['code' => $data['code']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'base_price' => $data['base_price'],
                ]
            );
        }

        // 2. Limpiar ofertas automáticas de proveedores (los médicos/clínicas las agregarán desde su panel)
        ProviderService::query()->forceDelete();
    }
}
