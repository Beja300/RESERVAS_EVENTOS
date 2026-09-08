<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/OwnerHistory.php';

/**
 * OwnerHistoryRepository — Acceso a tbownerhistory (historial de acciones
 * del propietario). Tabla de solo registro: las filas se insertan y se
 * leen, nunca se actualizan ni se borran en operación normal.
 */
class OwnerHistoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DataBase::getConnection();
    }

    public function save(OwnerHistory $history): int
    {
        $sql = "
            INSERT INTO tbownerhistory
            (
                tbownerid,
                tbownerhistoryaction,
                tbownerhistorydetail
            )
            VALUES
            (
                :ownerId,
                :action,
                :detail
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ownerId' => $history->getOwnerId(),
            ':action'  => $history->getAction(),
            ':detail'  => $history->getDetail(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Historial reciente de un propietario, más reciente primero.
     *
     * @return OwnerHistory[]
     */
    public function findRecentByOwner(int $ownerId, int $limit = 20): array
    {
        $sql = "
            SELECT *
            FROM tbownerhistory
            WHERE tbownerid = :ownerId
            ORDER BY tbownerhistorydate DESC, tbownerhistoryid DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':ownerId', $ownerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    private function mapRow(array $row): OwnerHistory
    {
        return new OwnerHistory(
            ownerId: (int) $row['tbownerid'],
            action: $row['tbownerhistoryaction'],
            detail: $row['tbownerhistorydetail'] ?? null,
            id: (int) $row['tbownerhistoryid'],
            date: $row['tbownerhistorydate'] ?? null,
            active: (bool) $row['tbownerhistoryactive']
        );
    }
}