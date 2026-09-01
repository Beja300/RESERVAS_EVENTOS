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

    public function notify(int $rolePk, string $message, ?string $link = null): int
    {
        try {
            $notification = new Notification(
                idNotification: 0,
                idRol: $rolePk,
                messageNotification: $message,
                link: $link,
                dateNotification: date('Y-m-d H:i:s'),
                isActive: true,
                isRead: false
            );

            return $this->notificationRepo->save($notification);
        } catch (\Throwable $e) {
            return 0;
        }
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

    public function notifyAdmins(string $message, ?string $link = null): void
    {
        $adminRolePks = $this->notificationRepo->findAdminRoleIds();

        foreach ($adminRolePks as $adminRolePk) {
            $this->notify($adminRolePk, $message, $link);
        }
    }

    public function notifyNewBookingToAdmins(int $bookingPk): void
    {
        $this->notifyAdmins(
            "Se ha creado una nueva reserva.",
            $this->bookingAdminUrl($bookingPk)
        );
    }

    public function notifyServiceReviewed(
        int $ownerRolePk,
        bool $approved,
        ?string $link = null
    ): int {
        return $this->notify(
            $ownerRolePk,
            $approved
                ? "Tu servicio fue aprobado."
                : "Tu servicio fue rechazado.",
            $link
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

    public function open(
        int $notificationPk,
        int $requestingRolePk
    ): ?string {
        $notification = $this->notificationRepo->findById(
            $notificationPk
        );

        if (
            $notification === null ||
            $notification->getIdRol() !== $requestingRolePk
        ) {
            throw new BusinessRuleException(
                "No puedes abrir una notificación que no es tuya."
            );
        }

        $this->notificationRepo->markRead($notificationPk);

        return $notification->getLink();
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

    // =========================================================
    // FLUJOS DE NEGOCIO (reciben ids de perfil y resuelven el rol)
    // =========================================================
    public function notifyOwnerOfNewBooking(int $ownerId, string $venueName, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByOwner($ownerId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Recibiste una nueva reserva en tu local: {$venueName}.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyOwnerPaymentVerification(int $ownerId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByOwner($ownerId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tienes una nueva reserva pendiente de verificación de pago en tu local.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyOwnerBookingCancelled(int $ownerId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByOwner($ownerId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Un cliente canceló la reserva de tu local.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyOwnerPaymentReceived(int $ownerId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByOwner($ownerId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Se registró el pago de una reserva de tu local.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientPaymentApproved(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu pago fue verificado y tu reserva fue confirmada.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientPaymentRejected(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu pago no pudo ser verificado. Revisa la información de pago y contacta al propietario.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientBookingCancelled(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu reserva fue cancelada.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientBookingRescheduled(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu reserva fue reprogramada.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientVenueChanged(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu reserva fue asignada a otro local.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientRefundApproved(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu solicitud de reembolso fue aprobada.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyClientRefundRejected(int $clientId, int $bookingPk): void
    {
        $rolePk = $this->notificationRepo->findRoleIdByClient($clientId);

        if ($rolePk !== null) {
            $this->notify(
                $rolePk,
                "Tu solicitud de reembolso fue rechazada.",
                $this->bookingUserUrl($bookingPk)
            );
        }
    }

    public function notifyAdminsRefundRequested(int $bookingPk): void
    {
        $this->notifyAdmins(
            "Un cliente solicitó el reembolso de la reserva #{$bookingPk}.",
            $this->bookingAdminUrl($bookingPk)
        );
    }

    // =========================================================
    // CONSTRUCCIÓN DE URLS HACIA EL MOTIVO DE LA NOTIFICACIÓN
    // =========================================================
    private function appUrl(string $controller, string $action, array $params = []): string
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');
        $query = http_build_query(array_merge(
            ['controller' => $controller, 'action' => $action],
            $params
        ));
        return $base . '/index.php?' . $query;
    }

    private function bookingAdminUrl(int $bookingPk): string
    {
        return $this->appUrl('admin', 'bookingDetail', ['id' => $bookingPk]);
    }

    private function bookingUserUrl(int $bookingPk): string
    {
        return $this->appUrl('booking', 'detail', ['id' => $bookingPk]);
    }

    public function serviceListUrl(int $venuePk): string
    {
        return $this->appUrl('service', 'list', ['venueId' => $venuePk]);
    }
}