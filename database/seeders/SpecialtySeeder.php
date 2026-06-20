<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialty;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'Medicina General' => 'Atención primaria del adulto. Diagnóstico y tratamiento de enfermedades comunes, control de condiciones crónicas, prevención y derivación a especialistas cuando es necesario.',
            'Medicina Interna' => 'Diagnóstico y tratamiento no quirúrgico de enfermedades complejas del adulto. Patologías del interior del cuerpo que afectan múltiples órganos.',
            'Pediatría' => 'Salud infantil desde el nacimiento hasta la adolescencia. Crecimiento, desarrollo, vaccinations y enfermedades propias de la edad pediátrica.',
            'Cardiología' => 'Enfermedades del corazón y sistema cardiovascular. Diagnóstico y tratamiento de insuficiencia cardíaca, arritmias, hipertensión y enfermedad coronaria.',
            'Ginecología y Obstetricia' => 'Salud reproductiva femenina. Control prenatal, parto, enfermedades del aparato genital femenino, planificación familiar y menopausia.',
            'Dermatología' => 'Enfermedades de la piel, cabello y uñas. Acné, eccema, psoriasis, infecciones cutáneas, cáncer de piel y enfermedades venéreas.',
            'Oftalmología' => 'Salud visual y enfermedades de los ojos. Errores de refracción, glaucoma, cataratas, enfermedades de la retina y cirugía ocular.',
            'Odontología' => 'Salud bucodental. Prevención y tratamiento de caries, enfermedades de las encías, ortodoncia, endodoncia y cirugía oral.',
            'Traumatología y Ortopedia' => 'Sistema musculoesquelético. Huesos, articulaciones, ligamentos, tendones y músculos. Fracturas, luxaciones, artritis y lesiones deportivas.',
            'Cirugía General' => 'Tratamiento quirúrgico de enfermedades del aparato digestivo, vesícula, hernias, appendicitis y otras patologías que requieren intervención.',
            'Neurología' => 'Enfermedades del sistema nervioso central y periférico. Cefaleas, epilepsia, Alzheimer, Parkinson, esclerosis múltiple y accidentes cerebrovasculares.',
            'Neurocirugía' => 'Cirugía del sistema nervioso. Tumores cerebrales, aneurismas, hernia de disco, trauma craneoencefálico y columna vertebral.',
            'Psiquiatría' => 'Salud mental y trastornos psiquiátricos. Depresión, ansiedad, esquizofrenia, trastornos bipolares, adicciones y trastornos alimentarios.',
            'Gastroenterología' => 'Enfermedades del tubo digestivo y órganos anexos. Estómago, intestinos, hígado, vesícula y páncreas. Gastritis, úlceras, hepatitis.',
            'Nefrología' => 'Enfermedades del riñón y vías urinarias. Insuficiencia renal, diálisis, glomerulonefritis, piedras en el riñón e infecciones urinarias.',
            'Neumología' => 'Enfermedades del aparato respiratorio. Asma, EPOC, neumonía, tuberculosis, apnea del sueño y cáncer de pulmón.',
            'Endocrinología' => 'Enfermedades de las glándulas endocrinas. Diabetes, problemas de tiroides, obesidad, osteoporosis y trastornos hormonales.',
            'Reumatología' => 'Enfermedades del tejido conectivo y articulaciones. Artritis reumatoide, lupus, fibromialgia, gota y enfermedades autoinmunes.',
            'Urología' => 'Enfermedades del aparato urinario y reproductor masculino. Próstata, riñones, vejiga, incontinencia, cálculos y cáncer urológico.',
            'Oncología' => 'Diagnóstico y tratamiento del cáncer. Quimioterapia, radioterapia, seguimiento de tumores y cuidados paliativos.',
            'Hematología' => 'Enfermedades de la sangre. Anemia, leucemia, linfomas, trastornos de coagulación',
            'Infectología' => 'Enfermedades infecciosas. VIH/SIDA, malaria, dengue, Zika, COVID-19, hepatitis virales e infecciones hospitalarias.',
            'Alergología e Inmunología' => 'Alergias y enfermedades del sistema inmunológico. Rinitis alérgica, asma alérgica, urticaria, anafilaxia y inmunodeficiencias.',
            'Geriatría' => 'Salud del adulto mayor. Envejecimiento, demencia, fragilidad, полиорганные enfermedades y prevención de caídas.',
            'Medicina de Emergencia y Urgencias' => 'Atención de emergencias y urgencias médicas. Trauma, shock, infarto, ACV, intoxicaciones y situaciones críticas.',
            'Anestesiología' => 'Administración de anestesia para cirugías. Manejo del dolor, medicina perioperatoria y reanimación.',
            'Radiología' => 'Diagnóstico por imágenes. Rayos X, tomografía, resonancia magnética, ecografía y mamografía.',
            'Medicina del Trabajo' => 'Salud laboral y prevención de enfermedades profesionales. Exámenes ocupacionales, riesgos laborales y rehabilitación.',
            'Medicina Familiar' => 'Atención integral de la familia. Salud comunitaria, promoción de la salud y seguimiento longitudinal de pacientes.',
            'Proctología' => 'Enfermedades del recto y ano. Hemorroides, fisuras, abscesos, incontinence y cáncer colorrectal.',
            'Angiología y Cirugía Vascular' => 'Enfermedades de los vasos sanguíneos. Várices, úlceras venosas, enfermedad arterial periférica y aneurismas.',
            'Nutriología' => 'Nutrición y dietética. Obesidad, desnutrición, diabetes, alergias alimentaires y evaluación del estado nutricional.',
        ];

        foreach ($specialties as $name => $description) {
            Specialty::create([
                'id' => Str::uuid(),
                'name' => $name,
                'description' => $description,
            ]);
        }
    }
}
