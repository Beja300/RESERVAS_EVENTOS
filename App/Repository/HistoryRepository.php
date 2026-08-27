<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/History.php';

class HistoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DataBase::getConnection();
    }

    public function save(History $history): int
    {
        $sql = "
            INSERT INTO tbuserhistory
            (
                tbuserhistoryroleid,
                tbuserhistoryaction,
                tbuserhistoryentity,
                tbuserhistoryentityid
            )
            VALUES
            (
                :roleId,
                :action,
                :entity,
                :entityId
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':roleId'   => $history->getRoleId(),
            ':action'   => $history->getAction(),
            ':entity'   => $history->getEntity(),
            ':entityId' => $history->getEntityId(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Historial completo de un usuario, más reciente primero -- es la
     * materia prima que va a usar HistoryService para recomendar.
     */
    public function listByRole(int $roleId): array
    {
        $sql = "SELECT * FROM tbuserhistory WHERE tbuserhistoryroleid = :roleId ORDER BY tbuserhistorydate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId]);
        return array_map([$this, 'mapearFila'], $stmt->fetchAll());
    }

    /**
     * Historial filtrado por tipo de acción (ej. solo 'VIEW') -- útil para
     * un algoritmo de recomendación que solo mira, por ejemplo, qué vio
     * el usuario recientemente.
     */
    public function listByRoleAndAction(int $roleId, string $action): array
    {
        $sql = "SELECT * FROM tbuserhistory
                WHERE tbuserhistoryroleid = :roleId AND tbuserhistoryaction = :action
                ORDER BY tbuserhistorydate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId, ':action' => $action]);
        return array_map([$this, 'mapearFila'], $stmt->fetchAll());
    }

    private function mapearFila(array $fila): History
    {
        return new History(
            roleId: $fila['tbuserhistoryroleid'],
            action: $fila['tbuserhistoryaction'],
            entity: $fila['tbuserhistoryentity'],
            entityId: $fila['tbuserhistoryentityid'],
            id: $fila['tbuserhistoryid'],
            date: $fila['tbuserhistorydate']
        );
    }

    /**
     * Devuelve los ids de entidad (ej. venuePk) MÁS interactuados,
     * ordenados de mayor a menor, filtrando por tipo de entidad y por
     * cuáles acciones cuentan como "interacción" -- es la base del
     * ranking de popularidad para el respaldo del sistema híbrido.
     *
     * @param string[] $actions ej. ['VIEW', 'BOOKING', 'PURCHASE']
     * @return int[] lista de entityId, ya ordenada por popularidad
     */
    public function mostInteractedEntityIds(string $entity, array $actions, int $limit): array
    {
        // Construimos los placeholders (:a0, :a1, ...) dinámicamente porque
        // la cantidad de acciones a filtrar puede variar según quien llame.
        $placeholders = [];
        $params = [':entity' => $entity, ':limit' => $limit];
        foreach ($actions as $i => $action) {
            $key = ":a{$i}";
            $placeholders[] = $key;
            $params[$key] = $action;
        }
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT tbuserhistoryentityid, COUNT(*) AS interactions
                FROM tbuserhistory
                WHERE tbuserhistoryentity = :entity
                  AND tbuserhistoryaction IN ($inClause)
                  AND tbuserhistoryentityid IS NOT NULL
                GROUP BY tbuserhistoryentityid
                ORDER BY interactions DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT); // LIMIT necesita bind explícito como INT
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();

        return array_map(fn($fila) => (int) $fila['tbuserhistoryentityid'], $stmt->fetchAll());
    }
}
