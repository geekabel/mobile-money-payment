<?php

namespace Geekabel\MobileMoneyPayment\Service;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeGenerator
{
    public function generatePaymentQRCode(string $provider, string $phone, float $amount, string $reference): string
    {
        $paymentData = [
            'provider' => $provider,
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference,
            'timestamp' => time(),
        ];

        $qrCodeContent = json_encode($paymentData);

        $qrCode = QrCode::create($qrCodeContent)
            ->setSize(300)
            ->setMargin(10)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }
}
