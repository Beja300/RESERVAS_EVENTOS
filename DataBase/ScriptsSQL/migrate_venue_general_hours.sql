-- Horario general del local para reservas de día completo.
-- Ejecutar una vez en una base existente.
ALTER TABLE tbvenue
  ADD COLUMN tbvenuecheckintime TIME NULL,
  ADD COLUMN tbvenuecheckouttime TIME NULL;
