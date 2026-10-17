<?php

use App\Services\NotificationEmailService;
use CodeIgniter\Test\CIUnitTestCase;

final class NotificationEmailServiceTest extends CIUnitTestCase
{
    public function testSendTestEmailRejectsMissingRecipient(): void
    {
        $service = new NotificationEmailService();

        $result = $service->sendTestEmail([
            'id' => 1,
            'name' => 'Tester',
            'email' => '',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Email pengguna tidak tersedia', $result['message']);
    }

    public function testIsConfiguredReturnsTrueWhenBrevoConfigured(): void
    {
        $service = new NotificationEmailService();
        $this->assertTrue($service->isConfigured());
    }

    public function testSendTestEmailRejectsInvalidEmailFormat(): void
    {
        $service = new NotificationEmailService();

        $result = $service->sendTestEmail([
            'id' => 1,
            'name' => 'Tester',
            'email' => 'invalid-email-format',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Email pengguna tidak tersedia', $result['message']);
    }

    public function testMaskAccountNumberProtectsSensitiveData(): void
    {
        $reflection = new \ReflectionClass(NotificationEmailService::class);
        $method = $reflection->getMethod('maskAccountNumber');
        $method->setAccessible(true);

        $service = new NotificationEmailService();
        $masked = $method->invoke($service, '123456789012');

        $this->assertSame('1234*****012', $masked);
    }
}
