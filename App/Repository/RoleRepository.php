<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/Role.php';

/**
 * REPOSITORIO: RoleRepository
 *
 * Traduce entre objetos Role y filas de la tabla tbrol.
 * Es la base que usarán los repositorios de los subtipos
 * (AdminRepository, ClientRepository, OwnerRepository)
 * cuando necesiten crear primero el registro base en tbrol.
 */
class RoleRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = DataBase::getConnection();
  }

  /**
   * Inserta un nuevo registro en tbrol y devuelve el id generado.
   * Se devuelve el id (en vez de bool) porque los subtipos lo
   * necesitan para insertar su propia fila con la FK correcta.
   */
  public function guardar(Role $rol): int
  {
    $sql = "INSERT INTO tbrol (tbrolnombre, tbrolcorreo, tbrolcontrasena, tbroltelefono, tbrolactivo)
                VALUES (:nombre, :correo, :contrasena, :telefono, :activo)";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
      ':nombre'     => $rol->getNombre(),
      ':correo'     => $rol->getCorreo(),
      ':contrasena' => password_hash($rol->getContrasena(), PASSWORD_DEFAULT),
      ':telefono'   => $rol->getTelefono(),
      ':activo'     => $rol->isActivo() ? 1 : 0,
    ]);

    // lastInsertId() devuelve el AUTO_INCREMENT que MySQL acaba
    // de generar para esta fila -- es el tbrolpk del nuevo registro.
    return (int) $this->db->lastInsertId();
  }

  public function buscarPorCorreo(string $correo): ?Role
  {
    $sql = "SELECT * FROM tbrol WHERE tbrolcorreo = :correo LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':correo' => $correo]);
    $fila = $stmt->fetch();

    if (!$fila) {
      return null;
    }

    return $this->mapearFila($fila);
  }

  public function buscarPorId(int $pk): ?Role
  {
    $sql = "SELECT * FROM tbrol WHERE tbrolpk = :pk LIMIT 1";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':pk' => $pk]);
    $fila = $stmt->fetch();

    return $fila ? $this->mapearFila($fila) : null;
  }

  /**
   * Método auxiliar privado: convierte una fila (array) en un
   * objeto Role. Se separó en su propio método porque tanto
   * buscarPorCorreo() como buscarPorId() necesitan hacer lo mismo
   * -- así no se repite el mismo "new Role(...)" dos veces.
   */
  private function mapearFila(array $fila): Role
  {
    return new Role(
      nombre: $fila['tbrolnombre'],
      correo: $fila['tbrolcorreo'],
      contrasena: $fila['tbrolcontrasena'],
      activo: (bool) $fila['tbrolactivo'],
      pk: $fila['tbrolpk'],
      telefono: $fila['tbroltelefono']
    );
  }

  public function eliminar(int $pk): bool
  {
    $stmt = $this->db->prepare("DELETE FROM tbrol WHERE tbrolpk = :pk");
    return $stmt->execute([':pk' => $pk]);
  }

  /**
   * Activa o desactiva la cuenta base (controla si puede iniciar sesión).
   * La usan AdminService/ClientService/OwnerService antes de tocar sus
   * propias tablas de subtipo.
   */
  public function setActive(int $pk, bool $active): bool
  {
    $stmt = $this->db->prepare("UPDATE tbrol SET tbrolactivo = :activo WHERE tbrolpk = :pk");
    return $stmt->execute([':activo' => $active ? 1 : 0, ':pk' => $pk]);
  }

  /**
   * Actualiza los campos base de identidad (nombre, correo, telefono).
   * NO toca la contraseña -- eso se maneja aparte, con su propio flujo
   * de verificación, para no exponerlo en un formulario de perfil normal.
   */
  public function actualizar(Role $role): bool
  {
    $sql = "UPDATE tbrol SET tbrolnombre = :nombre, tbrolcorreo = :correo, tbroltelefono = :telefono
                WHERE tbrolpk = :pk";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
      ':pk'       => $role->getPk(),
      ':nombre'   => $role->getNombre(),
      ':correo'   => $role->getCorreo(),
      ':telefono' => $role->getTelefono(),
    ]);
  }
}
