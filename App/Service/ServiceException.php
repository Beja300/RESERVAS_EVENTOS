<?php

/**
 * ServiceException — se lanza cuando una operación de negocio falla por un
 * motivo TÉCNICO (error de BD, constraint, escritura inesperada), no por una
 * regla del dominio.
 *
 * A diferencia de BusinessRuleException, su mensaje SIEMPRE es genérico y
 * seguro para el usuario: el detalle real (mensaje + traza) se registra en el
 * log mediante ErrorLogger y se conserva como excepción anterior.
 */
class ServiceException extends \RuntimeException
{
}
