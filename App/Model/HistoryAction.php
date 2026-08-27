<?php

/**
 * Esta clase almacena el historial de las acciones que tiene el usuario
 * Se hace asi para despues evitar problemas de escritura como por ejemplo de
 * VIEW a ViEw
 * */

class HistoryAction
{
  public const VIEW = 'VIEW';
  public const SEARCH = 'SEARCH';
  public const FAVORITE = 'FAVORITE';
  public const BOOKING = 'BOOKING';
  public const PURCHASE = 'PURCHASE';
}
