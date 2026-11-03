<?php

/**
 * ErrorLogger — registro centralizado y seguro de errores técnicos.
 *
 * Escribe en el log del servidor (error_log) el detalle completo de una
 * excepción: contexto (método/flujo), clase, mensaje, archivo:línea y traza.
 * NUNCA se muestra al usuario: los servicios lanzan ServiceException con un
 * mensaje genérico y aquí queda el detalle real para diagnóstico.
 */
final class ErrorLogger
{
    public static function log(\Throwable $e, string $context): void
    {
        error_log(sprintf(
            '[EventHall] %s | %s: %s en %s:%d | traza: %s',
            $context,
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
    }
}
