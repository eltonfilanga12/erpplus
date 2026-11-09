<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

final class EmailService
{
    public static function enviarRecibo(
        array $recibo,
        string $destinatario
    ): void {
        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'O funcionário não possui um endereço de e-mail válido.'
            );
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) getenv('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = (string) getenv('MAIL_USERNAME');
            $mail->Password = (string) getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = (string) (
                getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS
            );
            $mail->Port = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->CharSet = 'UTF-8';

            $remetente = (string) getenv('MAIL_FROM_ADDRESS');
            $nomeRemetente = (string) (
                getenv('MAIL_FROM_NAME')
                ?: ($recibo['empresa_nome'] ?? 'ERP Plus')
            );

            if (!filter_var($remetente, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(
                    'O e-mail remetente não foi configurado corretamente.'
                );
            }

            $mail->setFrom($remetente, $nomeRemetente);
            $mail->addAddress(
                $destinatario,
                (string) ($recibo['funcionario_nome'] ?? '')
            );

            if (
                !empty($recibo['empresa_email'])
                && filter_var(
                    $recibo['empresa_email'],
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $mail->addReplyTo(
                    $recibo['empresa_email'],
                    (string) ($recibo['empresa_nome'] ?? '')
                );
            }

            $codigo = htmlspecialchars(
                (string) ($recibo['codigo'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            );

            $funcionario = htmlspecialchars(
                (string) ($recibo['funcionario_nome'] ?? ''),
                ENT_QUOTES,
                'UTF-8'
            );

            $periodo = htmlspecialchars(
                (string) (
                    $recibo['periodo_descricao']
                    ?: $recibo['periodo_codigo']
                    ?: ''
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $moeda = (string) ($recibo['moeda'] ?: 'MZN');
            $liquido = number_format(
                (float) ($recibo['salario_liquido'] ?? 0),
                2,
                ',',
                '.'
            );

            $mail->isHTML(true);
            $mail->Subject = "Recibo de salário {$codigo}";
            $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="pt">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#1f2937">
    <div style="max-width:680px;margin:20px auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
        <div style="background:#1d4ed8;color:#fff;padding:22px">
            <h2 style="margin:0">Recibo de salário</h2>
        </div>

        <div style="padding:24px">
            <p>Olá, <strong>{$funcionario}</strong>.</p>

            <p>
                O seu recibo salarial foi emitido e enviado pela
                <strong>{$nomeRemetente}</strong>.
            </p>

            <table style="width:100%;border-collapse:collapse;margin:20px 0">
                <tr>
                    <td style="padding:10px;border:1px solid #e5e7eb"><strong>Código</strong></td>
                    <td style="padding:10px;border:1px solid #e5e7eb">{$codigo}</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #e5e7eb"><strong>Período</strong></td>
                    <td style="padding:10px;border:1px solid #e5e7eb">{$periodo}</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #e5e7eb"><strong>Salário líquido</strong></td>
                    <td style="padding:10px;border:1px solid #e5e7eb"><strong>{$liquido} {$moeda}</strong></td>
                </tr>
            </table>

            <p>
                Para consultar ou imprimir o documento completo, contacte o departamento de Recursos Humanos.
            </p>

            <p style="margin-top:28px;color:#6b7280;font-size:12px">
                Esta é uma mensagem automática. Não partilhe este e-mail, pois contém informação salarial confidencial.
            </p>
        </div>
    </div>
</body>
</html>
HTML;

            $mail->AltBody =
                "Olá, {$funcionario}.\n\n"
                . "O seu recibo salarial {$codigo}, referente ao período {$periodo}, foi emitido.\n"
                . "Salário líquido: {$liquido} {$moeda}.\n\n"
                . "Mensagem enviada por {$nomeRemetente}.";

            $mail->send();
        } catch (MailException $e) {
            throw new RuntimeException(
                'Não foi possível enviar o recibo por e-mail: '
                . $mail->ErrorInfo,
                0,
                $e
            );
        }
    }
}