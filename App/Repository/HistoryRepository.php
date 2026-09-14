<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/History.php';

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
                tbroleid,
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
        $sql = "SELECT * FROM tbuserhistory WHERE tbroleid = :roleId ORDER BY tbuserhistorydate DESC";
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
                WHERE tbroleid = :roleId AND tbuserhistoryaction = :action
                ORDER BY tbuserhistorydate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId, ':action' => $action]);
        return array_map([$this, 'mapearFila'], $stmt->fetchAll());
    }

    /**
     * Locales que un rol ha revisado MÁS veces (acción VIEW sobre entidad
     * 'Venue'), ordenados de mayor a menor número de visitas -- alimenta la
     * sección "Locales más frecuentes" del panel del cliente.
     *
     * @return array<int, int> mapa entityId => nº de visitas, en orden de frecuencia
     */
    public function mostViewedVenueIdsByRole(int $roleId, int $limit): array
    {
        $sql = "SELECT tbuserhistoryentityid AS venueId, COUNT(*) AS visits
                FROM tbuserhistory
                WHERE tbroleid = :roleId
                  AND tbuserhistoryaction = 'VIEW'
                  AND tbuserhistoryentity = 'Venue'
                  AND tbuserhistoryentityid IS NOT NULL
                GROUP BY tbuserhistoryentityid
                ORDER BY visits DESC, MAX(tbuserhistorydate) DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':roleId', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll() as $fila) {
            $result[(int) $fila['venueId']] = (int) $fila['visits'];
        }

        return $result;
    }

    private function mapearFila(array $fila): History
    {
        return new History(
            roleId: $fila['tbroleid'],
            action: $fila['tbuserhistoryaction'],
            entity: $fila['tbuserhistoryentity'],
            entityId: $fila['tbuserhistoryentityid'],
            id: $fila['tbuserhistoryid'],
            date: $fila['tbuserhistorydate']
        );
    }

    /**
     * Historial global de TODOS los usuarios (panel del Admin), con el
     * nombre del responsable y el nombre legible de la entidad
     * (local/servicio/ubicación) resuelto según el tipo de acción.
     */
    public function listAll(): array
    {
        $sql = "
            SELECT
                h.tbuserhistoryid,
                h.tbroleid,
                r.tbrolename AS responsibleName,
                h.tbuserhistoryaction,
                h.tbuserhistoryentity,
                h.tbuserhistoryentityid,
                v.tbvenuename         AS venueName,
                l.tblocationprovince  AS locationProvince,
                l.tblocationcanton    AS locationCanton,
                l.tblocationdistrict  AS locationDistrict,
                s.tbservicename       AS serviceName,
                h.tbuserhistorydate
            FROM tbuserhistory h
            LEFT JOIN tbrole r ON r.tbroleid = h.tbroleid
            LEFT JOIN tbvenue v ON h.tbuserhistoryentity = 'Venue' AND v.tbvenueid = h.tbuserhistoryentityid
            LEFT JOIN tblocation l ON h.tbuserhistoryentity = 'Venue' AND l.tblocationid = h.tbuserhistoryentityid
            LEFT JOIN tbservice s ON h.tbuserhistoryentity = 'Service' AND s.tbserviceid = h.tbuserhistoryentityid
            ORDER BY h.tbuserhistorydate DESC, h.tbuserhistoryid DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
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

    /**
     * Puntaje de interacción GLOBAL ponderado por venue (señal colaborativa
     * del sistema híbrido): suma la cantidad de acciones de todos los usuarios
     * multiplicada por el peso de cada acción. Las acciones con peso negativo
     * (ej. CANCEL) restan popularidad.
     *
     * @param string[] $weights mapa accion => peso (ej. ['VIEW' => 2, 'CANCEL' => -4])
     * @return array<int, float> mapa entityId => puntaje ponderado
     */
    public function interactionWeightedScores(string $entity, array $weights): array
    {
        $placeholders = [];
        $params = [':entity' => $entity];
        foreach ($weights as $action => $weight) {
            $key = ":a" . count($placeholders);
            $placeholders[] = "(tbuserhistoryaction = {$key}) * " . (int) $weight;
            $params[$key] = $action;
        }

        if (empty($placeholders)) {
            return [];
        }

        $sumExpr = implode(' + ', $placeholders);

        $sql = "SELECT tbuserhistoryentityid, SUM({$sumExpr}) AS score
                FROM tbuserhistory
                WHERE tbuserhistoryentity = :entity
                  AND tbuserhistoryentityid IS NOT NULL
                GROUP BY tbuserhistoryentityid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = [];
        foreach ($stmt->fetchAll() as $fila) {
            $result[(int) $fila['tbuserhistoryentityid']] = (float) $fila['score'];
        }

        return $result;
    }

    public function hasFavorite(int $roleId, int $venueId): bool
    {
        $sql = "SELECT tbuserhistoryid
                FROM tbuserhistory
                WHERE tbroleid = :roleId
                  AND tbuserhistoryaction = 'FAVORITE'
                  AND tbuserhistoryentity = 'Venue'
                  AND tbuserhistoryentityid = :venueId
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId, ':venueId' => $venueId]);

        return $stmt->fetchColumn() !== false;
    }

    public function deleteFavorite(int $roleId, int $venueId): void
    {
        $sql = "DELETE FROM tbuserhistory
                WHERE tbroleid = :roleId
                  AND tbuserhistoryaction = 'FAVORITE'
                  AND tbuserhistoryentity = 'Venue'
                  AND tbuserhistoryentityid = :venueId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId, ':venueId' => $venueId]);
    }

    /**
     * ids de locales favoritos de un rol, los más recientes primero.
     * @return int[]
     */
    public function favoriteVenueIdsByRole(int $roleId): array
    {
        $sql = "SELECT tbuserhistoryentityid
                FROM tbuserhistory
                WHERE tbroleid = :roleId
                  AND tbuserhistoryaction = 'FAVORITE'
                  AND tbuserhistoryentity = 'Venue'
                  AND tbuserhistoryentityid IS NOT NULL
                ORDER BY tbuserhistorydate DESC, tbuserhistoryid DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':roleId' => $roleId]);

        return array_map(fn($fila) => (int) $fila['tbuserhistoryentityid'], $stmt->fetchAll());
    }
}
