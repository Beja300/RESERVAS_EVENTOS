<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Repository/AdminRepository.php';
require_once __DIR__ . '/../Repository/RoleRepository.php';

class AdminService
{
    private AdminRepository $adminRepo;
    private RoleRepository $roleRepo;

    public function __construct()
    {
        $connection = DataBase::getConnection();

        $this->adminRepo = new AdminRepository($connection);
        $this->roleRepo = new RoleRepository($connection);
    }

    // =========================================================
    // CONTAR ADMINS ACTIVOS
    // =========================================================
    private function countActiveAdmins(): int
    {
        return count(array_filter(
            $this->adminRepo->findAll(),
            fn(Admin $admin) => $admin->getIsActive()
        ));
    }


    // =========================================================
    // DESACTIVAR
    // =========================================================
    public function deactivate(int $idRole, string $targetType): void
    {
        if ($targetType === 'admin' && $this->countActiveAdmins() <= 1) {
            throw new BusinessRuleException(
                "No puedes desactivar esta cuenta: es el único administrador activo."
            );
        }

        $this->roleRepo->setActive($idRole, false);
    }


    // =========================================================
    // ACTIVAR
    // =========================================================
    public function activate(int $idRole): void
    {
        $this->roleRepo->setActive($idRole, true);
    }
}
