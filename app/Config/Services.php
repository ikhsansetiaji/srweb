<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Services\NotificationEmailService;
use App\Services\WhatsAppNotificationService;
use App\Services\FonnteService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{

    public static function notificationEmail($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('notificationEmail');
        }

        return new NotificationEmailService();
    }

    /**
     * WhatsApp Notification Service via Fonnte API.
     * Gunakan service('whatsappNotification') untuk mendapatkan instance shared.
     */
    public static function whatsappNotification($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('whatsappNotification');
        }

        return new WhatsAppNotificationService();
    }

    /**
     * Fonnte HTTP client service.
     * Jarang digunakan langsung — lebih baik pakai whatsappNotification.
     */
    public static function fonnte($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('fonnte');
        }

        return new FonnteService();
    }

    public static function example($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('example');
        }

        return new \CodeIgniter\Example();
    }

}
