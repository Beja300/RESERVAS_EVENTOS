-- =========================================================
-- EventHall — Seed de datos de prueba (dbeventhall.sql debe
-- haberse ejecutado antes, sobre una base limpia).

USE dbeventhall;
-- La clave de TODOS los accesos es: Clave123
-- (hash bcrypt: $2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W)
--
-- Conventional FK naming: cada columna que apunta a otra tabla
-- lleva exactamente el nombre de la PK de su tabla madre
-- (ej. tbclient.tblocationid -> tblocation.tblocationid).
-- No hay constraints FOREIGN KEY: la integridad se valida en código.
-- =========================================================

-- =========================================================
-- 1) ROLES BASE (identidad de acceso)
-- =========================================================
INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Administrador',     'admin@eventhall.com',   '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888887', TRUE);
SET @adminRoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('María Fernanda',    'owner@eventhall.com',   '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888888', TRUE);
SET @ownerRoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Carlos El Cliente', 'cliente@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888889', TRUE);
SET @clientRoleId = LAST_INSERT_ID();

-- Clientes adicionales para que los dashboards de estadísticas
-- tengan datos (nuevos del mes / recurrentes / top clientes).
INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Ana Vargas',        'cliente2@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88880001', TRUE);
SET @clientRoleId2 = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Luis Mora',         'cliente3@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88880002', TRUE);
SET @clientRoleId3 = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Sofía Jiménez',     'cliente4@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88880003', TRUE);
SET @clientRoleId4 = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Pedro Rojas',       'cliente5@eventhall.com', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88880004', TRUE);
SET @clientRoleId5 = LAST_INSERT_ID();

-- =========================================================
-- 2) UBICACIONES
-- =========================================================
INSERT INTO tblocation (tblocationprovince, tblocationcanton, tblocationdistrict, tblocationtown, tblocationdescription) VALUES
('San José', 'Escazú',     'San Rafael',    'Centro',          'Zona residencial'),
('Alajuela', 'Alajuela',   'San José',      'Frente al parque','Céntrico'),
('Heredia',  'Heredia',    'San Francisco', 'La Y Griega',     'Al oeste del Mall');
SET @locSanRafael = 1;
SET @locAlajuela  = 2;
SET @locHeredia   = 3;

-- =========================================================
-- 3) PERFILES Y JUNCTIONS (perfil -> junction -> rol)
-- =========================================================
-- Admin
INSERT INTO tbadmin (tbadminname, tbadminimage, tbadminactive) VALUES
('Administrador del Sistema', NULL, TRUE);
SET @adminId = LAST_INSERT_ID();

INSERT INTO tbroleadmin (tbroleid, tbadminid, tbroleadminactive) VALUES
(@adminRoleId, @adminId, TRUE);

-- Owner
INSERT INTO tbowner (tbownerfirstname, tbownerlastname, tbowneralias, tbowneridentificationnumber, tbownerimage, tbowneractive) VALUES
('María Fernanda', 'Rodríguez', 'Mari', '207610598', NULL, TRUE);
SET @ownerId = LAST_INSERT_ID();

INSERT INTO tbroleowner (tbroleid, tbownerid, tbroleowneractive) VALUES
(@ownerRoleId, @ownerId, TRUE);

-- Client 1
INSERT INTO tbclient (tbclientname, tbclientimage, tblocationid, tbclientactive) VALUES
('Carlos El Cliente', NULL, @locSanRafael, TRUE);
SET @clientId = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleid, tbclientid, tbroleclientactive) VALUES
(@clientRoleId, @clientId, TRUE);

-- Client 2
INSERT INTO tbclient (tbclientname, tbclientimage, tblocationid, tbclientactive) VALUES
('Ana Vargas', NULL, @locHeredia, TRUE);
SET @clientId2 = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleid, tbclientid, tbroleclientactive) VALUES
(@clientRoleId2, @clientId2, TRUE);

-- Client 3
INSERT INTO tbclient (tbclientname, tbclientimage, tblocationid, tbclientactive) VALUES
('Luis Mora', NULL, @locSanRafael, TRUE);
SET @clientId3 = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleid, tbclientid, tbroleclientactive) VALUES
(@clientRoleId3, @clientId3, TRUE);

-- Client 4
INSERT INTO tbclient (tbclientname, tbclientimage, tblocationid, tbclientactive) VALUES
('Sofía Jiménez', NULL, @locAlajuela, TRUE);
SET @clientId4 = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleid, tbclientid, tbroleclientactive) VALUES
(@clientRoleId4, @clientId4, TRUE);

-- Client 5
INSERT INTO tbclient (tbclientname, tbclientimage, tblocationid, tbclientactive) VALUES
('Pedro Rojas', NULL, @locHeredia, TRUE);
SET @clientId5 = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleid, tbclientid, tbroleclientactive) VALUES
(@clientRoleId5, @clientId5, TRUE);

-- =========================================================
-- 3b) MINI-TABLAS HISTÓRICAS DE LA IDENTIDAD ROL
--     (historiales de credenciales para validar ataques)
-- =========================================================
-- Password: el "actual" es un hash anterior (ya no usado) y el
-- "nuevo" es el hash vigente; en producción los guarda el servicio
-- de seguridad al momento del cambio.
INSERT INTO tbrolpasswordhistorical (tbroleid, tbrolpasswordhistoricalactualpassword, tbrolpasswordhistoricalnewpassword, tbrolpasswordhistoricaldate) VALUES
(@adminRoleId,       '$2y$12$aBcdEfGhIjKlMnOpQrStUvWxYzAbCdEfGhIjKlMnOpQrStUvWxYz12', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '2026-06-01 09:15:00'),
(@ownerRoleId,       '$2y$12$zZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZzZz12', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '2026-05-15 18:30:00'),
(@clientRoleId,      '$2y$12$qQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQqQq12', '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '2026-07-20 10:05:00');

-- Phone: Carlos cambia de teléfono DOS veces muy seguido en agosto
-- (08-10 y 08-15) -> dispara la "advertencia por cambio excesivo".
INSERT INTO tbrolphonehistorical (tbroleid, tbrolphonehistoricalactualphone, tbrolphonehistoricalnewphone, tbrolphonehistoricaldate) VALUES
(@ownerRoleId,       '88888877', '88888888', '2026-04-02 11:00:00'),
(@clientRoleId,      '88888881', '88888885', '2026-08-10 16:20:00'),
(@clientRoleId,      '88888885', '88888889', '2026-08-15 09:45:00');

INSERT INTO tbrolemailhistorical (tbroleid, tbrolemailhistoricalactualemail, tbrolemailhistoricalnewemail, tbrolemailhistoricaldate) VALUES
(@ownerRoleId,       'mari.antigua@gmail.com', 'owner@eventhall.com', '2026-05-15 18:35:00');

-- =========================================================
-- 4) LOCAL (VENUE) DEL OWNER
-- =========================================================
INSERT INTO tbvenue (tbownerid, tblocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locAlajuela, 'Salón La Quinta', 'Salón de eventos', 120, 120000.00, NULL, TRUE);
SET @venueId = LAST_INSERT_ID();

INSERT INTO tbvenue (tbownerid, tblocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locSanRafael, 'Jardín El Roble', 'Jardín para eventos', 80, 95000.00, NULL, TRUE);
SET @venue2Id = LAST_INSERT_ID();

INSERT INTO tbvenue (tbownerid, tblocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locHeredia, 'Centro de Eventos La Y', 'Salón de eventos', 150, 180000.00, NULL, TRUE);
SET @venue3Id = LAST_INSERT_ID();

-- =========================================================
-- 5) SERVICIOS (estado 'aprobado' para poder reservarse)
-- =========================================================
INSERT INTO tbservice (tbvenueid, tbservicename, tbservicetype, tbserviceprice, tbservicestate, tbroleid, tbserviceapprovedon, tbserviceactive) VALUES
(@venueId, 'Decoración floral',          'Decoración', 150000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venueId, 'Servicio de banquetes',      'Catering',   250000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venueId, 'Sonido y luces',             'Producción',  80000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venue2Id, 'Decoración con jardín',     'Decoración',  90000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venue2Id, 'Mobiliario rústico',        'Mobiliario',  60000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venue3Id, 'Banquetes gourmet',         'Catering',    220000.00, 'aprobado', @adminRoleId, NOW(), TRUE),
(@venue3Id, 'Sonido profesional',        'Producción',   75000.00, 'aprobado', @adminRoleId, NOW(), TRUE);
SET @service1 = 1;
SET @service2 = 2;
SET @service3 = 3;
SET @service4 = 4;
SET @service6 = 6;

-- Histórico de precios del servicio 1 (cambios de precio registrados)
INSERT INTO tbservicehistory (tbserviceid, tbservicehistoryprice, tbservicehistoryvalidfrom, tbservicehistoryactive) VALUES
(@service1, 120000.00, '2026-01-01', FALSE),
(@service1, 140000.00, '2026-05-01', FALSE),
(@service1, 150000.00, '2026-08-01', TRUE);

-- =========================================================
-- 6) PROMOCIÓN + servicios incluidos (junction)
-- =========================================================
INSERT INTO tbpromotion (tbvenueid, tbpromotiondescription, tbpromotionlabel, tbpromotionstart, tbpromotionend, tbpromotionminservices, tbpromotionactive) VALUES
(@venueId, 'Pack boda completo', 'Pack Boda', '2026-01-01', '2026-12-31', 3, TRUE);
SET @promoId = LAST_INSERT_ID();

INSERT INTO tbpromotionservice (tbpromotionid, tbserviceid, tbpromotionserviceactive) VALUES
(@promoId, @service1, TRUE),
(@promoId, @service2, TRUE),
(@promoId, @service3, TRUE);

-- =========================================================
-- 7) MÉTODOS DE PAGO
-- =========================================================
INSERT INTO tbpaymentmethod (tbpaymentmethodtype, tbpaymentmethodactive) VALUES
('Efectivo', TRUE),
('Tarjeta',  TRUE),
('Transferencia', TRUE);
SET @pmEfectivo       = 1;
SET @pmTarjeta       = 2;
SET @pmTransferencia = 3;

-- =========================================================
-- 7b) DATOS DE COBRO DEL PROPIETARIO (tbownerpayment)
-- =========================================================
INSERT INTO tbownerpayment (tbownerid, tbpaymentmethodid, tbownerpaymentholder, tbownerpaymentaccount, tbownerpaymentinstructions, tbownerpaymentactive) VALUES
(@ownerId, @pmEfectivo,       'María Fernanda Rodríguez',       NULL,      'Realizar el pago en efectivo el día del evento.', TRUE),
(@ownerId, @pmTarjeta,        'María Fernanda Rodríguez',       NULL,      'Aceptamos tarjetas Visa y Mastercard.', TRUE),
(@ownerId, @pmTransferencia,  'Salón La Quinta S.A.',           'CR12 1234 5678 9012 3456 7', 'Transferencia SINPE a la cuenta indicada.', TRUE);

-- =========================================================
-- 8) CONFIGURACIÓN DE COMISIÓN (5% + IVA 13%)
-- =========================================================
INSERT INTO tbcommissionconfig (tbcommissionconfigpercentage, tbcommissionconfigtax, tbcommissionconfigactive) VALUES
(5.00, 13.00, TRUE);

-- =========================================================
-- 9) RESERVAS
--    Booking 1: evento futura (Catalina, Octubre)
--    Bookings con fecha EN EL MES PASADO (agosto 2026) para que
--    los estadísticos "nuevos del mes / recurrentes / top"
--    tengan datos.
-- =========================================================
-- booking 1 (futura)
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId, @venueId, '2026-10-10', 'Boda', 'pendiente', TRUE);
SET @bookingId = LAST_INSERT_ID();

-- booking 2 (agosto, pagada) -> Carlos
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId, @venueId, '2026-08-05', 'Cumpleaños', 'confirmado', TRUE);
SET @booking2Id = LAST_INSERT_ID();

-- booking 3 (agosto, confirmada) -> Ana
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId2, @venue2Id, '2026-08-08', 'Boda', 'confirmado', TRUE);
SET @booking3Id = LAST_INSERT_ID();

-- booking 4 (agosto, pendiente) -> Luis
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId3, @venue3Id, '2026-08-15', 'Corporativo', 'confirmado', TRUE);
SET @booking4Id = LAST_INSERT_ID();

-- booking 5 (agosto, confirmada) -> Sofía
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId4, @venueId, '2026-08-22', 'Cumpleaños', 'confirmado', TRUE);
SET @booking5Id = LAST_INSERT_ID();

-- booking 6 (agosto, pagada) -> Pedro
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId5, @venue2Id, '2026-08-26', 'Boda', 'confirmado', TRUE);
SET @booking6Id = LAST_INSERT_ID();

-- booking 7 (agosto, confirmada) -> Pedro (segunda del mes => recurrente)
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId5, @venue3Id, '2026-08-30', 'Bautizo', 'confirmado', TRUE);
SET @booking7Id = LAST_INSERT_ID();

-- booking 8 (agosto, pagada) -> Carlos (segunda del mes => recurrente)
INSERT INTO tbbooking (tbclientid, tbvenueid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId, @venue2Id, '2026-08-20', 'Boda', 'confirmado', TRUE);
SET @booking8Id = LAST_INSERT_ID();

-- =========================================================
-- 10) DETALLES + JUNCTIONS POR RESERVA
--     Cada reserva lleva su línea base (renta del local) y, cuando
--     aplica, una línea de servicio.
-- =========================================================

-- ---- Booking 1 (futura): renta + decoración ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 1, 120000.00, 0.00, TRUE);
SET @detailBase = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@bookingId, @detailBase, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service1, NULL, 1, 150000.00, 0.00, TRUE);
SET @detail1 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail1, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service2, NULL, 2, 250000.00, 0.00, TRUE);
SET @detail2 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail2, TRUE);

-- ---- Booking 2 (Carlos, pagada): renta + decoración ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 1, 120000.00, 0.00, TRUE);
SET @d2base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking2Id, @d2base, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service1, NULL, 1, 150000.00, 0.00, TRUE);
SET @d2s1 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking2Id, @d2s1, TRUE);

-- ---- Booking 3 (Ana, confirmada): renta + jardín ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue2Id, 1, 95000.00, 0.00, TRUE);
SET @d3base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking3Id, @d3base, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service4, NULL, 1, 90000.00, 0.00, TRUE);
SET @d3s4 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking3Id, @d3s4, TRUE);

-- ---- Booking 4 (Luis, confirmada): renta + banquetes ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue3Id, 1, 180000.00, 0.00, TRUE);
SET @d4base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking4Id, @d4base, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service6, NULL, 1, 220000.00, 0.00, TRUE);
SET @d4s6 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking4Id, @d4s6, TRUE);

-- ---- Booking 5 (Sofía, confirmada): renta ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 1, 120000.00, 0.00, TRUE);
SET @d5base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking5Id, @d5base, TRUE);

-- ---- Booking 6 (Pedro, pagada): renta + jardín ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue2Id, 1, 95000.00, 0.00, TRUE);
SET @d6base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking6Id, @d6base, TRUE);

INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service4, NULL, 1, 90000.00, 0.00, TRUE);
SET @d6s4 = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking6Id, @d6s4, TRUE);

-- ---- Booking 7 (Pedro, confirmada): renta ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue3Id, 1, 180000.00, 0.00, TRUE);
SET @d7base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking7Id, @d7base, TRUE);

-- ---- Booking 8 (Carlos, pagada): renta ----
INSERT INTO tbdetail (tbserviceid, tbvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue2Id, 1, 95000.00, 0.00, TRUE);
SET @d8base = LAST_INSERT_ID();
INSERT INTO tbbookingdetail (tbbookingid, tbdetailid, tbbookingdetailactive) VALUES
(@booking8Id, @d8base, TRUE);

-- =========================================================
-- 10b) HISTORIAL DE LAS RESERVAS (tbboookinghistory)
-- =========================================================
INSERT INTO tbbookinghistory (tbbookingid, tbroleid, tbbookinghistoryaction, tbbookinghistorydetail, tbbookinghistorydate, tbbookinghistoryactive) VALUES
(@booking2Id, @clientRoleId, 'creada',   'Cliente crea la reserva.',        '2026-08-01 10:00:00', TRUE),
(@booking2Id, @adminRoleId,  'confirmada','Admin confirma la reserva.',      '2026-08-02 09:30:00', TRUE),
(@booking2Id, @adminRoleId,  'pagada',   'Pago aprobado.',                   '2026-08-03 14:00:00', TRUE),
(@booking3Id, @clientRoleId2,'creada',   'Cliente crea la reserva.',         '2026-08-04 11:20:00', TRUE),
(@booking3Id, @adminRoleId,  'confirmada','Admin confirma la reserva.',       '2026-08-05 08:10:00', TRUE),
(@booking4Id, @clientRoleId3,'creada',   'Cliente crea la reserva.',         '2026-08-10 15:45:00', TRUE),
(@booking4Id, @adminRoleId,  'confirmada','Admin confirma la reserva.',       '2026-08-11 10:00:00', TRUE),
(@booking5Id, @clientRoleId4,'creada',   'Cliente crea la reserva.',         '2026-08-12 09:00:00', TRUE),
(@booking5Id, @adminRoleId,  'confirmada','Admin confirma la reserva.',       '2026-08-13 16:30:00', TRUE),
(@booking6Id, @clientRoleId5,'creada',   'Cliente crea la reserva.',         '2026-08-18 12:00:00', TRUE),
(@booking6Id, @adminRoleId,  'pagada',   'Pago aprobado.',                   '2026-08-19 17:20:00', TRUE),
(@booking7Id, @clientRoleId5,'creada',   'Cliente crea la reserva.',         '2026-08-25 10:40:00', TRUE),
(@booking7Id, @adminRoleId,  'confirmada','Admin confirma la reserva.',       '2026-08-26 09:15:00', TRUE),
(@booking8Id, @clientRoleId, 'creada',   'Cliente crea la reserva.',         '2026-08-16 13:25:00', TRUE),
(@booking8Id, @adminRoleId,  'pagada',   'Pago aprobado.',                   '2026-08-17 11:50:00', TRUE);

-- =========================================================
-- 10c) SOLICITUD DE REEMBOLSO de la reserva 4 (Luis)
-- =========================================================
INSERT INTO tbbookingrefund (tbbookingid, tbroleid, tbbookingrefunddetail, tbbookingrefundstate, tbbookingrefunddate, tbbookingrefundactive) VALUES
(@booking4Id, @clientRoleId3, 'No podré asistir por motivos de salud.', 'pendiente', '2026-08-28 18:00:00', TRUE);

-- =========================================================
-- 11) PAGOS / FACTURAS / EARNINGS de reservas pagadas
-- =========================================================
-- Booking 2: total = 120000 + 150000 = 270000
INSERT INTO tbbookingticket (tbbookingid, tbbookingticketfile, tbbookingtickettype, tbpaymentmethodid, tbbookingticketstate, tbbookingticketactive) VALUES
(@booking2Id, 'ticket_b2_transferencia.pdf', 'pdf', @pmTransferencia, 'aprobado', TRUE);
SET @ticket2Id = LAST_INSERT_ID();

INSERT INTO tbinvoice (tbbookingid, tbpaymentmethodid, tbinvoicedate, tbinvoicestatus, tbinvoiceactive) VALUES
(@booking2Id, @pmTransferencia, '2026-08-03 14:00:00', 'pagada', TRUE);
SET @invoice2Id = LAST_INSERT_ID();

-- Comisión 5% (13500) + IVA 13% (35100); al owner: 270000-13500-35100 = 221400
INSERT INTO tbeearning (tbbookingid, tbeearningtotal, tbeearningcommission, tbeearningtax, tbeearningowneramount, tbroleid, tbeearningdate, tbeearningactive) VALUES
(@booking2Id, 270000.00, 13500.00, 35100.00, 221400.00, @adminRoleId, '2026-08-03 14:05:00', TRUE);

-- Booking 6: total = 95000 + 90000 = 185000
INSERT INTO tbbookingticket (tbbookingid, tbbookingticketfile, tbbookingtickettype, tbpaymentmethodid, tbbookingticketstate, tbbookingticketactive) VALUES
(@booking6Id, 'ticket_b6_sinpe.png', 'png', @pmTransferencia, 'aprobado', TRUE);

INSERT INTO tbinvoice (tbbookingid, tbpaymentmethodid, tbinvoicedate, tbinvoicestatus, tbinvoiceactive) VALUES
(@booking6Id, @pmTransferencia, '2026-08-19 17:20:00', 'pagada', TRUE);

-- Comisión 5% (9250) + IVA 13% (24050); al owner: 185000-9250-24050 = 151700
INSERT INTO tbeearning (tbbookingid, tbeearningtotal, tbeearningcommission, tbeearningtax, tbeearningowneramount, tbroleid, tbeearningdate, tbeearningactive) VALUES
(@booking6Id, 185000.00, 9250.00, 24050.00, 151700.00, @adminRoleId, '2026-08-19 17:25:00', TRUE);

-- Booking 8: total = 95000
INSERT INTO tbbookingticket (tbbookingid, tbbookingticketfile, tbbookingtickettype, tbpaymentmethodid, tbbookingticketstate, tbbookingticketactive) VALUES
(@booking8Id, 'ticket_b8_efectivo.pdf', 'pdf', @pmEfectivo, 'aprobado', TRUE);

INSERT INTO tbinvoice (tbbookingid, tbpaymentmethodid, tbinvoicedate, tbinvoicestatus, tbinvoiceactive) VALUES
(@booking8Id, @pmEfectivo, '2026-08-17 11:50:00', 'pagada', TRUE);

-- Comisión 5% (4750) + IVA 13% (12350); al owner: 95000-4750-12350 = 77900
INSERT INTO tbeearning (tbbookingid, tbeearningtotal, tbeearningcommission, tbeearningtax, tbeearningowneramount, tbroleid, tbeearningdate, tbeearningactive) VALUES
(@booking8Id, 95000.00, 4750.00, 12350.00, 77900.00, @adminRoleId, '2026-08-17 11:55:00', TRUE);

-- =========================================================
-- 12) CALIFICACIONES (local y servicio)
-- =========================================================
INSERT INTO tbvenuerating (tbvenueid, tbroleid, tbvenueratingstars, tbvenueratingcomment, tbvenueratingactive) VALUES
(@venueId, @clientRoleId, 5, 'Excelente lugar para celebrar.', TRUE),
(@venue2Id, @clientRoleId2, 4, 'El jardín es precioso.', TRUE),
(@venue3Id, @clientRoleId5, 5, 'Muy amplio y bien ubicado.', TRUE);

INSERT INTO tbservicerating (tbserviceid, tbroleid, tbserviceratingstars, tbserviceratingcomment, tbserviceratingactive) VALUES
(@service1, @clientRoleId, 5, 'La decoración fue espectacular.', TRUE),
(@service4, @clientRoleId2, 4, 'Muy buena atención.', TRUE);

-- =========================================================
-- 13) MOVIMIENTOS DE USUARIOS (tbuserhistory)
--      Registro de accesos y acciones por rol.
-- =========================================================
INSERT INTO tbuserhistory (tbroleid, tbuserhistoryaction, tbuserhistoryentity, tbuserhistoryentityid, tbuserhistorydate) VALUES
(@clientRoleId, 'login_success',  'tbrole', @clientRoleId, '2026-08-05 09:55:00'),
(@clientRoleId, 'booking_created','tbbooking', @booking2Id, '2026-08-01 10:00:00'),
(@clientRoleId, 'login_failed',   'tbrole', @clientRoleId, '2026-08-06 22:10:00'),
(@clientRoleId, 'login_success',  'tbrole', @clientRoleId, '2026-08-06 22:11:00'),
(@adminRoleId,  'login_success',  'tbrole', @adminRoleId, '2026-08-02 09:00:00'),
(@ownerRoleId,  'login_success',  'tbrole', @ownerRoleId, '2026-08-03 08:30:00'),
(@clientRoleId2,'login_success',  'tbrole', @clientRoleId2, '2026-08-04 11:15:00'),
(@clientRoleId3,'login_success',  'tbrole', @clientRoleId3, '2026-08-10 15:40:00');

-- =========================================================
-- 14) MOVIMIENTOS DEL PROPIETARIO (tbownerhistory)
-- =========================================================
INSERT INTO tbownerhistory (tbownerid, tbownerhistoryaction, tbownerhistorydetail, tbownerhistorydate, tbownerhistoryactive) VALUES
(@ownerId, 'venue_created', 'Registro del local "Salón La Quinta".', '2026-03-10 10:00:00', TRUE),
(@ownerId, 'venue_created', 'Registro del local "Jardín El Roble".', '2026-03-12 11:00:00', TRUE),
(@ownerId, 'earnings_viewed', 'Consultó sus ganancias.', '2026-08-04 09:00:00', TRUE);

-- =========================================================
-- 15) NOTIFICACIONES (tbnotification)
--      Demo: una notificación de actividad sospechosa al cliente
--      (por cambiar de teléfono dos veces seguidas) y su copia al
--      admin, más notificaciones normales de estado.
-- =========================================================
INSERT INTO tbnotification (tbroleid, tbnotificationmessage, tbnotificationlink, tbnotificationdate, tbnotificationread, tbnotificationactive) VALUES
(@clientRoleId, 'Se detectó un cambio de teléfono frecuente. Verifique su cuenta.', 'client/profile.php', '2026-08-15 09:46:00', FALSE, TRUE),
(@adminRoleId,  'ALERTA: el rol cliente@eventhall.com cambió su teléfono 2 veces en 5 días.', 'admin/user-history.php?role=3', '2026-08-15 09:46:00', FALSE, TRUE),
(@clientRoleId, 'Su reserva fue confirmada.', 'booking/detail.php?id=2', '2026-08-02 09:31:00', TRUE, TRUE),
(@clientRoleId2,'Su reserva fue confirmada.', 'booking/detail.php?id=3', '2026-08-05 08:11:00', FALSE, TRUE),
(@clientRoleId5,'Su reserva fue confirmada.', 'booking/detail.php?id=7', '2026-08-26 09:16:00', FALSE, TRUE);