<?php

namespace App\Services\Otp\Channels;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Email OTP delivery channel.
 */
class EmailChannel implements OtpChannelInterface
{
    public function send(string $email, string $code): void
    {
        Log::info("[OTP Email] Código: $code → $email");

        try {
            $digits = str_split($code);
            $codeBoxes = '';
            foreach ($digits as $index => $digit) {
                $marginRight = ($index === count($digits) - 1) ? '0' : '6px';
                $codeBoxes .= "<div style='display: inline-block; width: 42px; height: 52px; line-height: 52px; text-align: center; border: 1.5px solid #dbeafe; border-radius: 10px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; font-size: 26px; font-weight: 800; color: #23DCE1; background-color: #ffffff; margin-right: {$marginRight}; box-shadow: 0 2px 4px rgba(0, 82, 255, 0.04);'>$digit</div>";
            }

            $disk = Storage::disk('r2_images');

            // Subir PharmakoEmailCodeOtpExtraLarge-WEBP.webp
            $illustrationPath = 'assets/PharmakoEmailCodeOtpExtraLarge-WEBP.webp';
            if (!$disk->exists($illustrationPath)) {
                $localIllustration = public_path('PharmakoEmailCodeOtpExtraLarge-WEBP.webp');
                if (file_exists($localIllustration)) {
                    $disk->put($illustrationPath, file_get_contents($localIllustration));
                }
            }
            $illustrationUrl = $disk->temporaryUrl($illustrationPath, now()->addDays(7));

            // Subir PharmakoLogoOnlyFace-PNG.png
            $logoPath = 'assets/PharmakoLogoOnlyFace-PNG.png';
            if (!$disk->exists($logoPath)) {
                $localLogo = public_path('PharmakoLogoOnlyFace-PNG.png');
                if (file_exists($localLogo)) {
                    $disk->put($logoPath, file_get_contents($localLogo));
                }
            }
            $LogoUrl = $disk->temporaryUrl($logoPath, now()->addDays(7));

            Mail::html(
                "<div style='background-color: #f8fafc; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; min-height: 100%;'>
                    <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 768px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03); overflow: hidden;'>
                        <tr>
                            <td style='padding: 40px 35px;'>
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <!-- Contenido Izquierdo -->
                                        <td width='58%' valign='top' style='padding-right: 20px;'>
                                            <!-- Logo de Pharmako -->
                                            <table border='0' cellpadding='0' cellspacing='0'>
                                                <tr>
                                                    <td valign='middle' style='padding-right: 8px;'>
                                                        <div>
                                                            <img src='{$LogoUrl}' alt='Pharmako Logo' style='width: 40px; height: 40px;' />
                                                        </div>
                                                    </td>
                                                    <td valign='middle' style='font-family: sans-serif; font-size: 24px; font-weight: bold; color: #0f172a; letter-spacing: -0.5px;'>
                                                        pharmako
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan='2' style='font-family: sans-serif; font-size: 11px; color: #64748b; padding-top: 4px; font-weight: 500;'>
                                                        Menos procesos. <span style='color: #23DCE1; font-weight: bold;'>Más cuidado.</span>
                                                    </td>
                                                </tr>
                                            </table>

                                            <!-- Título y Subtítulo -->
                                            <div style='margin-top: 35px;'>
                                                <h1 style='font-family: sans-serif; font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 10px 0; letter-spacing: -0.5px;'>¡Hola! 👋</h1>
                                                <p style='font-family: sans-serif; font-size: 15px; color: #475569; margin: 0 0 25px 0; font-weight: 500;'>Tu código de verificación es:</p>
                                            </div>

                                            <!-- Cajas del Código -->
                                            <div style='margin-bottom: 25px; white-space: nowrap;'>
                                                {$codeBoxes}
                                            </div>

                                            <!-- Nota de Expiración -->
                                            <p style='font-family: sans-serif; font-size: 13.5px; color: #475569; line-height: 1.5; margin: 0 0 25px 0;'>
                                                Usa este código para continuar con tu inicio de sesión. Este código expirará en <strong style='color: #23DCE1;'>5 minutos</strong>.
                                            </p>

                                            <!-- Alerta de Seguridad -->
                                            <table border='0' cellpadding='0' cellspacing='0' style='background-color: #f8fafc; border-radius: 12px; padding: 14px; width: 100%; border: 1px solid #f1f5f9;'>
                                                <tr>
                                                    <td width='30' valign='top' style='font-size: 16px; padding-top: 2px;'>
                                                        🛡️
                                                    </td>
                                                    <td style='font-family: sans-serif; font-size: 12.5px; color: #64748b; line-height: 1.4; font-weight: 500;'>
                                                        Si no solicitaste este código, puedes ignorar este correo de forma segura.
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>

                                        <!-- Ilustración Derecha -->
                                        <td width='42%' valign='middle' align='center'>
                                            <img src='{$illustrationUrl}' alt='Doctor Illustration' width='230' style='display: block; max-width: 100%; height: auto; border: none; outline: none;' />
                                        </td>
                                    </tr>
                                </table>

                                <!-- Línea Divisoria -->
                                <hr style='border: none; border-top: 1px solid #f1f5f9; margin: 35px 0 20px 0;' />

                                <!-- Footer -->
                                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                                    <tr>
                                        <td valign='middle' style='font-family: sans-serif; font-size: 14px; font-weight: bold; color: #0f172a;'>
                                            <img src='{$LogoUrl}' alt='Pharmako Logo' style='width: 40px; height: 40px;' /> PHARMAKO
                                        </td>
                                        <td align='right' valign='middle' style='font-family: sans-serif; font-size: 12px; color: #94a3b8; font-weight: 500;'>
                                            Gracias por confiar en nosotros.
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Tu código de verificación de PHARMAKO');
                }
            );
            Log::info("[OTP Email] Correo enviado exitosamente a $email");
        } catch (\Throwable $e) {
            Log::error("[OTP Email] Error al enviar correo a $email: " . $e->getMessage());
        }
    }
}
