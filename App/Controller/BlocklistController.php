<?php

require_once __DIR__ . '/../Service/BlocklistService.php';
require_once __DIR__ . '/../Service/BusinessRuleException.php';
require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../View/_helpers.php';

class BlocklistController
{
  private BlocklistService $blocklistService;

  public function __construct()
  {
    $this->blocklistService = new BlocklistService(DataBase::getConnection());
  }

  public function list(): void
  {
    require_role('owner');

    $owner = $_SESSION['user'];
    $blocked = $this->blocklistService->listForOwner($owner->getIdOwner());
    $suggestions = $this->blocklistService->suggestionsForOwner($owner->getIdOwner());

    require_once __DIR__ . '/../View/Blocklist/List.php';
  }

  public function block(): void
  {
    require_role('owner');
    require_post();

    $owner = $_SESSION['user'];
    $clientId = (int) ($_POST['clientId'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    try {
      if ($clientId > 0) {
        $this->blocklistService->block($owner->getIdOwner(), $clientId, $reason);
      } else {
        if ($email === '') {
          throw new BusinessRuleException('Indica el correo del cliente o selecciona una sugerencia.');
        }
        $this->blocklistService->blockByEmail($owner->getIdOwner(), $email, $reason);
      }

      redirect_to('blocklist', 'list', ['saved' => '1']);
    } catch (BusinessRuleException $e) {
      $blocked = $this->blocklistService->listForOwner($owner->getIdOwner());
      $suggestions = $this->blocklistService->suggestionsForOwner($owner->getIdOwner());
      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Blocklist/List.php';
    }
  }

  public function unblock(): void
  {
    require_role('owner');
    require_post();

    $owner = $_SESSION['user'];
    $blockId = (int) ($_POST['blockId'] ?? 0);

    try {
      $this->blocklistService->unblock($owner->getIdOwner(), $blockId);
      redirect_to('blocklist', 'list', ['removed' => '1']);
    } catch (BusinessRuleException $e) {
      $blocked = $this->blocklistService->listForOwner($owner->getIdOwner());
      $suggestions = $this->blocklistService->suggestionsForOwner($owner->getIdOwner());
      $error = $e->getMessage();
      require_once __DIR__ . '/../View/Blocklist/List.php';
    }
  }
}
