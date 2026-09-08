<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Service/OwnerService.php';
require_once __DIR__ . '/../Service/ProfileImageService.php';
require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Service/RoleSecurityService.php';
require_once __DIR__ . '/../Service/OwnerPaymentService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../Model/Owner.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';

class OwnerController extends BaseController
{
  private OwnerService $ownerService;
  private AuthService $authService;
  private RoleSecurityService $roleSecurityService;
  private OwnerPaymentService $ownerPaymentService;
  private ProfileImageService $profileImageService;

  public function __construct()
  {
    $connection = DataBase::getConnection();

    $this->ownerService = new OwnerService($connection);
    $this->authService = new AuthService();
    $this->roleSecurityService = new RoleSecurityService();
    $this->ownerPaymentService = new OwnerPaymentService($connection);
    $this->profileImageService = new ProfileImageService();
  }

  // =========================================================
  // DASHBOARD (sus locales + sus reservas)
  // =========================================================
  public function dashboard(): void
  {
    $this->requireOwner();

    $owner = $this->currentUser();

    $yearMonth = trim($_POST['month'] ?? $_GET['month'] ?? date('Y-m'));

    if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
      $yearMonth = date('Y-m');
    }

    $summary = $this->ownerService->dashboardSummary($owner->getIdOwner(), $yearMonth);

    $venues = $summary['venues'];
    $bookings = $summary['bookings'];
    $stats = $summary['stats'];
    $topVenue = $summary['topVenue'];
    $topServices = $summary['topServices'];

    $prevMonth = date('Y-m', strtotime($yearMonth . '-01 first day of last month'));
    $nextMonth = date('Y-m', strtotime($yearMonth . '-01 first day of next month'));

    require_once __DIR__ . '/../View/Owner/Dashboard.php';
  }

  // =========================================================
  // VER PERFIL
  // =========================================================
  public function profile(): void
  {
    $this->requireOwner();

    $owner = $this->currentUser();

    require_once __DIR__ . '/../View/Owner/Form.php';
  }

  // =========================================================
  // ACTUALIZAR PERFIL (todos los datos del propietario).
  // Para cambiar la contraseña se exige confirmar la actual.
  // =========================================================
  public function updateProfile(): void
  {
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $this->currentUser();

    $currentEmail = $owner->getEmail();
    $currentPhone = $owner->getPhoneNumber();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $ownerLastName = trim($_POST['ownerLastName'] ?? '');
    $ownerAlias = trim($_POST['ownerAlias'] ?? '');
    $ownerIdentification = trim($_POST['ownerIdentification'] ?? '');
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';

    if ($phoneNumber === '') {
      $phoneNumber = null;
    }

    try {

      if ($name === '') {
        $name = $owner->getName();
      }

      if (strtolower($email) !== strtolower($owner->getEmail())) {
        $this->authService->validateEmailIsUnique($email);
      }

      $this->authService->validatePhoneFormat($phoneNumber);

      if ($ownerIdentification !== ''
          && strtolower($ownerIdentification) !== strtolower($owner->getIdentificationNumberOwner())) {
        $this->authService->validateIdentificationIsUnique($ownerIdentification);
      }

      // Cambio de contraseña: solo con confirmación de la contraseña actual.
      $hasCurrent = trim($currentPassword) !== '';
      $hasNew = trim($newPassword) !== '';

      if ($hasCurrent || $hasNew) {
        if (!$hasCurrent || !$hasNew) {
          throw new BusinessRuleException('Para cambiar tu contraseña debes escribir la contraseña actual y la nueva.');
        }

        if (!password_verify($currentPassword, $owner->getPassword())) {
          throw new BusinessRuleException('La contraseña actual no es correcta.');
        }

        $this->authService->validatePasswordStrength($newPassword);
      }

      $owner->setName($name);
      $owner->setEmail($email);
      $owner->setPhoneNumber($phoneNumber);
      $owner->setLastNameOwner($ownerLastName);
      $owner->setAliasOwner($ownerAlias);
      $owner->setIdentificationNumberOwner($ownerIdentification);

      $newImage = $this->profileImageService->resolveAndPersist(
        $owner->getImageOwner(),
        $_POST,
        $_FILES,
        'resource/owners/',
        'owner_' . $owner->getIdOwner() . '_'
      );
      $owner->setImageOwner($newImage);

      $this->ownerService->saveProfile($owner);

      if ($hasCurrent && $hasNew) {
        $this->roleSecurityService->changePassword($owner->getIdRol(), $newPassword);
      }

      // Seguridad: auditar y alertar ante cambios de credenciales.
      $this->roleSecurityService->recordPhoneChange($owner->getIdRol(), $currentPhone, $phoneNumber);
      $this->roleSecurityService->recordEmailChange($owner->getIdRol(), $currentEmail, $email);

      $_SESSION['user'] = $owner;

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Perfil actualizado correctamente.']);
      }

      $this->redirect('owner', 'profile', ['updated' => 1]);
    } catch (BusinessRuleException $e) {

      $error = $e->getMessage();

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $error], 422);
      }

      require_once __DIR__ . '/../View/Owner/Form.php';
    }
  }

  // =========================================================
  // ELIMINAR FOTO DE PERFIL
  // =========================================================
  public function removePhoto(): void
  {
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $this->currentUser();

    $this->profileImageService->deleteStoredFile($owner->getImageOwner(), 'resource/owners/');
    $this->ownerService->removeProfilePhoto($owner);

    $_SESSION['user'] = $owner;

    if (is_ajax()) {
      respond_json(['ok' => true, 'message' => 'Foto de perfil eliminada.']);
    }

    $this->redirect('owner', 'profile', ['removed' => 1]);
  }

  // =========================================================
  // DESACTIVAR CUENTA (borrado lógico de tbrole).
  // Solo se permite si el propietario NO tiene reservas futuras
  // (hoy o después) pendientes o confirmadas en sus locales.
  // =========================================================
  public function deactivateAccount(): void
  {
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->profile();
      return;
    }

    $owner = $this->currentUser();

    try {

      $this->ownerService->deactivateProfile($owner->getIdOwner(), $owner->getIdRol());

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Tu perfil fue desactivado.']);
      }

    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Owner/Form.php';
      return;
    }

    session_unset();
    session_destroy();

    header('Location: ../../Public/index.php?controller=auth&action=showLogin');
    exit;
  }

  // =========================================================
  // CONFIGURAR DATOS DE COBRO (métodos que acepta el owner).
  // Estos datos los ve el cliente al elegir cómo pagar.
  // =========================================================
  public function paymentData(): void
  {
    $this->requireOwner();

    $owner = $this->currentUser();

    $ownerPayments = $this->ownerPaymentService->findByOwner($owner->getIdOwner());
    $paymentMethods = $this->ownerService->getActivePaymentMethods();

    require_once __DIR__ . '/../View/Owner/PaymentData.php';
  }

  // =========================================================
  // GUARDAR / ACTUALIZAR UN MÉTODO DE COBRO (AJAX)
  // =========================================================
  public function savePayment(): void
  {
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->paymentData();
      return;
    }

    $owner = $this->currentUser();

    $idPaymentMethod = (int) ($_POST['paymentMethodId'] ?? 0);
    $ownerPaymentPk = (int) ($_POST['idOwnerPayment'] ?? 0);
    $holder = trim($_POST['holder'] ?? '');
    $account = trim($_POST['account'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $active = isset($_POST['active']);

    try {

      $this->ownerPaymentService->save(
        $owner->getIdOwner(),
        $idPaymentMethod,
        $holder,
        $account,
        $instructions,
        $active,
        $ownerPaymentPk
      );

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Método de cobro guardado.']);
      }

    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Owner/PaymentData.php';
      return;
    }

    $this->redirect('owner', 'paymentData', ['saved' => 1]);
  }

  // =========================================================
  // ELIMINAR UN MÉTODO DE COBRO (AJAX)
  // =========================================================
  public function removePayment(): void
  {
    $this->requireOwner();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      $this->paymentData();
      return;
    }

    $owner = $this->currentUser();
    $idOwnerPayment = (int) ($_POST['idOwnerPayment'] ?? 0);

    try {

      $this->ownerPaymentService->remove($owner->getIdOwner(), $idOwnerPayment);

      if (is_ajax()) {
        respond_json(['ok' => true, 'message' => 'Método de cobro eliminado.']);
      }

    } catch (BusinessRuleException $e) {

      if (is_ajax()) {
        respond_json(['ok' => false, 'message' => $e->getMessage()], 422);
      }

      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Owner/PaymentData.php';
      return;
    }

    $this->redirect('owner', 'paymentData', ['removed' => 1]);
  }
}