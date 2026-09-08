<?php

require_once __DIR__ . '/BusinessRuleException.php';

/**
 * ProfileImageService — Manejo de imágenes de perfil (Owner/Client/Admin).
 *
 * Centraliza las reglas compartidas de subida/validación/borrado de fotos
 * que antes estaban duplicadas (~100 líneas por controlador):
 *  - Validación de extensión y tamaño.
 *  - Persistencia bajo Public/resource/<entidad>/ con nombre único.
 *  - Borrado del archivo local (nunca URLs externas).
 *
 * El controlador conserva la mutación del modelo (setter del campo de
 * imagen); este servicio solo resuelve y persiste el archivo.
 */
class ProfileImageService
{
  private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  private const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB

  /** Raíz de archivos públicos servidos (Public/). */
  private const PUBLIC_DIR = '/../../Public';

  /**
   * Resuelve la imagen de perfil según la petición y la persiste.
   *
   * @param string   $currentPath  Ruta almacenada actual ('' si no hay).
   * @param array    $post         $_POST (clave 'removePhoto', 'imageUrl').
   * @param array    $files        $_FILES (clave 'image').
   * @param string   $relativeDir  Directorio bajo Public/, ej. 'resource/owners/'.
   * @param string   $filePrefix   Prefijo del nombre, ej. 'owner_'.
   *
   * @return string Nueva ruta de imagen ('' si se eliminó).
   * @throws BusinessRuleException si el archivo/URL no es válido.
   */
  public function resolveAndPersist(
    string $currentPath,
    array $post,
    array $files,
    string $relativeDir,
    string $filePrefix
  ): string {
    $newImage = $currentPath;

    if (isset($post['removePhoto'])) {
      $newImage = '';
    } elseif (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
      $newImage = $this->storeUpload($files['image'], $relativeDir, $filePrefix);
    } else {
      $url = trim($post['imageUrl'] ?? '');

      if ($url !== '') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
          throw new BusinessRuleException("URL de imagen no válida.");
        }

        $newImage = $url;
      }
    }

    if ($newImage !== $currentPath) {
      $this->deleteStoredFile($currentPath, $relativeDir);
    }

    return $newImage;
  }

  /**
   * Elimina el archivo local de una ruta almacenada (recortando el
   * prefijo propio del directorio). Nunca borra URLs externas.
   */
  public function deleteStoredFile(string $storedPath, string $relativeDir): void
  {
    if ($storedPath === '' || strpos($storedPath, $relativeDir) !== 0) {
      return;
    }

    $file = __DIR__ . self::PUBLIC_DIR . '/' . $storedPath;
    if (is_file($file)) {
      @unlink($file);
    }
  }

  private function storeUpload(array $file, string $relativeDir, string $filePrefix): string
  {
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
      throw new BusinessRuleException("Formato de imagen no válido (usa jpg, png, webp o gif).");
    }

    if (($file['size'] ?? 0) > self::MAX_SIZE_BYTES) {
      throw new BusinessRuleException("La imagen no puede superar los 2 MB.");
    }

    $dir = __DIR__ . self::PUBLIC_DIR . '/' . $relativeDir;
    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    $filename = $filePrefix . bin2hex(random_bytes(4)) . '.' . $extension;

    if (!isset($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $dir . $filename)) {
      throw new BusinessRuleException("No se pudo guardar la imagen.");
    }

    return $relativeDir . $filename;
  }
}