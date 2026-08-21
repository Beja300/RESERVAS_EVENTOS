<?php

/**
 * BusinessRuleException — se lanza cuando una regla de negocio se viola
 * (no un error de BD, no un error de sintaxis: una regla del dominio,
 * ej. "no se puede reservar en el pasado").
 *
 * El Controller la captura y decide cómo mostrarla al usuario, en vez de
 * que el error se propague como un fatal error genérico.
 */
class BusinessRuleException extends \Exception
{
}
