<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/NotificationRepository.php';
require_once __DIR__ . '/../Model/Notification.php';

/**
 * Service class for managing notifications.
 */
class NotificationService
{
    private NotificationRepository $notificationRepo;

    public function __construct(NotificationRepository $notificationRepo)
    {
        $this->notificationRepo = $notificationRepo;
    }

    public function notify(int $rolePk, string $message): int
    {
        $notification = new Notification(
            idNotification: 0,
            idRol: $rolePk,
            messageNotification: $message,
            dateNotification: date('Y-m-d H:i:s'),
            isActive: true,
            isRead: false
        );

        return $this->notificationRepo->save($notification);
    }

    public function notifyBookingConfirmed(int $clientRolePk): int
    {
        return $this->notify(
            $clientRolePk,
            "Tu reserva fue confirmada."
        );
    }

    public function notifyNewBookingReceived(int $ownerRolePk): int
    {
        return $this->notify(
            $ownerRolePk,
            "Recibiste una nueva reserva en tu local."
        );
    }

    public function notifyAdmins(string $message): void
    {
        // Se corrige el nombre del método a findAdminRoleIds()
        $adminRolePks = $this->notificationRepo->findAdminRoleIds();

        foreach ($adminRolePks as $adminRolePk) {
            $this->notify($adminRolePk, $message);
        }
    }

    public function notifyNewBookingToAdmins(): void
    {
        $this->notifyAdmins(
            "Se ha creado una nueva reserva."
        );
    }

    public function notifyServiceReviewed(
        int $ownerRolePk,
        bool $approved
    ): int {
        return $this->notify(
            $ownerRolePk,
            $approved
                ? "Tu servicio fue aprobado."
                : "Tu servicio fue rechazado."
        );
    }

    public function markAsRead(
        int $notificationPk,
        int $requestingRolePk
    ): void {
        $notification = $this->notificationRepo->findById(
            $notificationPk
        );

        // Se corrige getRoleFk() por getIdRol()
        if (
            $notification === null ||
            $notification->getIdRol() !== $requestingRolePk
        ) {
            throw new BusinessRuleException(
                "No puedes marcar como leída una notificación que no es tuya."
            );
        }

        $this->notificationRepo->markRead($notificationPk);
    }

    public function markAllAsRead(int $requestingRolePk): void
    {
        $this->notificationRepo->markAllRead(
            $requestingRolePk
        );
    }

    public function countUnread(int $rolePk): int
    {
        return $this->notificationRepo->countUnread($rolePk);
    }

    public function listForRole(int $rolePk): array
    {
        return $this->notificationRepo->findByRole($rolePk);
    }

    public function notifyPaymentVerification(int $ownerRolePk): int
    {
        return $this->notify(
            $ownerRolePk,
            "Tienes una nueva reserva pendiente de verificación de pago."
        );
    }

    public function notifyPaymentApproved(int $clientRolePk): int
    {
        return $this->notify(
            $clientRolePk,
            "Tu pago fue verificado y tu reserva ha sido aprobada."
        );
    }

    public function notifyPaymentRejected(int $clientRolePk): int
    {
        return $this->notify(
            $clientRolePk,
            "Tu pago no pudo ser verificado. Revisa la información de pago y contacta al propietario."
        );
    }
}