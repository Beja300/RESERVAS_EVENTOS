<?php

require_once __DIR__ . '/BusinessRuleException.php';

/**
 * Almacenamiento físico de archivos subidos (imágenes de perfil, fotos
 * de locales y comprobantes de pago).
 *
 * Centraliza la validación de extensiones, el tamaño máximo, la creación
 * de directorios y el movimiento del archivo, que antes se copiaban en
 * AdminController, ClientController, OwnerController, VenueController y
 * BookingController. Trabaja bajo Public/resource y nunca toca URLs
 * externas.
 */
class ImageStorageService
{
  // Extensiones permitidas para imágenes de la app.
  public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

  // Extensiones permitidas para comprobantes de pago.
  public const TICKET_EXTENSIONS = ['png', 'jpg', 'jpeg', 'pdf'];

  public const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

  /**
   * Guarda un archivo subido en Public/resource/{subdir}/ y devuelve la
   * ruta relativa a Public/ (p. ej. "resource/venues/venue_5_ab12cd34.jpg").
   *
   * @throws BusinessRuleException si la extensión no es válida, supera el
   *                               tamaño máximo o no se puede mover.
   */
  public static function store(
    array $file,
    string $subdir,
    string $prefix,
    array $allowed = self::IMAGE_EXTENSIONS,
    int $maxBytes = self::MAX_IMAGE_BYTES,
    string $invalidMessage = 'Formato de imagen no válido (usa jpg, png, webp o gif).'
  ): string {
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed, true)) {
      throw new BusinessRuleException($invalidMessage);
    }

    $limit = self::humanBytes($maxBytes);
    $sizePhrase = $maxBytes >= 1024 * 1024
      ? ($maxBytes / (1024 * 1024)) . ' MB'
      : $limit;

    if (($file['size'] ?? 0) > $maxBytes) {
      throw new BusinessRuleException("El archivo no puede superar los {$sizePhrase}.");
    }

    $dir = __DIR__ . '/../../Public/resource/' . $subdir . '/';

    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    $filename = $prefix . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
      throw new BusinessRuleException('No se pudo guardar el archivo.');
    }

    return 'resource/' . $subdir . '/' . $filename;
  }

  /**
   * Borra un archivo local guardado bajo Public/resource/.
   * No hace nada con URLs externas.
   */
  public static function deleteLocal(string $storedPath): void
  {
    if (!str_starts_with($storedPath, 'resource/')) {
      return;
    }

    $file = __DIR__ . '/../../Public/' . $storedPath;

    if (is_file($file)) {
      @unlink($file);
    }
  }

  private static function humanBytes(int $bytes): string
  {
    if ($bytes >= 1024 * 1024) {
      return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    return round($bytes / 1024, 1) . ' KB';
  }
}