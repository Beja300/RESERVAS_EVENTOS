-- =========================================================
-- EventHall — Seed de datos de prueba (dbeventhall.sql debe
-- haberse ejecutado antes, sobre una base limpia).
-- Todos los accesos usan la clave: Clave123
-- (hash bcrypt: $2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W)
--
-- Relaciones SIN foreign keys, igual que el esquema:
--   rol <-> perfil  se resuelve por las tablas intermedias
--   reserva <-> detalle por tbbookingdetail (junction pura)
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

-- =========================================================
-- 2) UBICACIONES
-- =========================================================
INSERT INTO tblocation (tblocationprovince, tblocationcanton, tblocationdistrict, tblocationtown, tblocationdescription) VALUES
('San José', 'Escazú',    'San Rafael',  'Centro',        'Zona residencial'),
('Alajuela', 'Alajuela',  'San José',    'Frente al parque', 'Céntrico'),
('Heredia',  'Heredia',   'San Francisco', 'La Y Griega', 'Al oeste del Mall'  );
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

INSERT INTO tbroleadmin (tbroleadminrolid, tbroleadminadminid, tbroleadminactive) VALUES
(@adminRoleId, @adminId, TRUE);

-- Owner
INSERT INTO tbowner (tbownerfirstname, tbownerlastname, tbowneralias, tbowneridentificationnumber, tbownerimage, tbowneractive) VALUES
('María Fernanda', 'Rodríguez', 'Mari', '207610598', NULL, TRUE);
SET @ownerId = LAST_INSERT_ID();

INSERT INTO tbroleowner (tbroleownerrolid, tbroleownerownerid, tbroleowneractive) VALUES
(@ownerRoleId, @ownerId, TRUE);

-- Client
INSERT INTO tbclient (tbclientname, tbclientimage, tbclientlocationid, tbclientactive) VALUES
('Carlos El Cliente', NULL, @locSanRafael, TRUE);
SET @clientId = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleclientrolid, tbroleclientclientid, tbroleclientactive) VALUES
(@clientRoleId, @clientId, TRUE);

-- =========================================================
-- 4) LOCAL (VENUE) DEL OWNER
-- =========================================================
INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locAlajuela, 'Salón La Quinta', 'Salón de eventos', 120, 120000.00, NULL, TRUE);
SET @venueId = LAST_INSERT_ID();

-- Locales extra para probar "Locales cerca de ti" (uno en San José y otro en Heredia)
INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locSanRafael, 'Jardín El Roble', 'Jardín para eventos', 80, 95000.00, NULL, TRUE);
SET @venue2Id = LAST_INSERT_ID();

INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@ownerId, @locHeredia, 'Centro de Eventos La Y', 'Salón de eventos', 150, 180000.00, NULL, TRUE);
SET @venue3Id = LAST_INSERT_ID();

-- =========================================================
-- 5) SERVICIOS (estado 'aprobado' para poder reservarse)
-- =========================================================
INSERT INTO tbservice (tbservicelocalid, tbservicename, tbservicetype, tbserviceprice, tbservicestate, tbserviceapprovedby, tbserviceapprovedon, tbserviceactive) VALUES
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

-- =========================================================
-- 6) PROMOCIÓN + servicios incluidos (junction)
-- =========================================================
INSERT INTO tbpromotion (tbpromotionvenueid, tbpromotiondescription, tbpromotionlabel, tbpromotionstart, tbpromotionend, tbpromotionminservices, tbpromotionactive) VALUES
(@venueId, 'Pack boda completo', 'Pack Boda', '2026-01-01', '2026-12-31', 3, TRUE);
SET @promoId = LAST_INSERT_ID();

INSERT INTO tbpromotionservice (tbpromotionservicepromotionid, tbpromotionserviceserviceid, tbpromotionserviceactive) VALUES
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
-- El cliente elige el método y ve estos datos para pagar al owner.
-- =========================================================
INSERT INTO tbownerpayment (tbownerpaymentownerid, tbownerpaymentpaymentmethodid, tbownerpaymentholder, tbownerpaymentaccount, tbownerpaymentinstructions, tbownerpaymentactive) VALUES
(@ownerId, @pmEfectivo,       'María Fernanda Rodríguez',       NULL,      'Realizar el pago en efectivo el día del evento.', TRUE),
(@ownerId, @pmTarjeta,        'María Fernanda Rodríguez',       NULL,      'Aceptamos tarjetas Visa y Mastercard.', TRUE),
(@ownerId, @pmTransferencia,  'Salón La Quinta S.A.',           'CR12 1234 5678 9012 3456 7', 'Transferencia SINPE a la cuenta indicada.', TRUE);

-- =========================================================
-- 8) CONFIGURACIÓN DE COMISIÓN (5% + IVA 13%)
-- =========================================================
INSERT INTO tbcommissionconfig (tbcommissionconfigpercentage, tbcommissionconfigtax, tbcommissionconfigactive) VALUES
(5.00, 13.00, TRUE);

-- =========================================================
-- 9) UNA RESERVA DE EJEMPLO (cliente -> junction detalle)
-- =========================================================
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingeventtype, tbbookingstate, tbbookingactive) VALUES
(@clientId, @venueId, '2026-10-10', 'Boda', 'pendiente', TRUE);
SET @bookingId = LAST_INSERT_ID();

-- Línea base: renta del local (la factura NUNCA puede ser 0) + junction
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 1, 120000.00, 0.00, TRUE);
SET @detailBase = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detailBase, TRUE);

-- Línea 1: detalle (tbdetail) + junction (tbbookingdetail); servicio
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service1, NULL, 1, 150000.00, 0.00, TRUE);
SET @detail1 = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail1, TRUE);

-- Línea 2 (servicio)
INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service2, NULL, 2, 250000.00, 0.00, TRUE);
SET @detail2 = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@bookingId, @detail2, TRUE);

-- =========================================================
-- 10) UNA CALIFICACIÓN DE EJEMPLO (local y servicio)
-- =========================================================
INSERT INTO tbvenuerating (tbvenueratingvenueid, tbvenueratingroleid, tbvenueratingstars, tbvenueratingcomment, tbvenueratingactive) VALUES
(@venueId, @clientRoleId, 5, 'Excelente lugar para celebrar.', TRUE);

INSERT INTO tbservicerating (tbserviceratingserviceid, tbserviceratingroleid, tbserviceratingstars, tbserviceratingcomment, tbserviceratingactive) VALUES
(@service1, @clientRoleId, 5, 'La decoración fue espectacular.', TRUE);

-- =========================================================
-- 11) SEGUNDO PROPIETARIO Y CLIENTES ADICIONALES
-- (para probar multi-owner / multi-client: reseñas, reservas,
--  dashboards y notificaciones cruzadas)
-- =========================================================
INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Pedro El Propietario', 'pedro@eventhall.com',  '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888810', TRUE);
SET @owner2RoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Daniela La Clienta', 'daniela@eventhall.com',  '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888811', TRUE);
SET @client2RoleId = LAST_INSERT_ID();

INSERT INTO tbrole (tbrolename, tbroleemail, tbrolepassword, tbrolephone, tbroleactive) VALUES
('Sofía La Clienta',  'sofia@eventhall.com',     '$2y$12$LFUzMhzDUs4gyS.qrVzvPuqMyFabRoMokeccN09MOs5sBcwnHy87W', '88888812', TRUE);
SET @client3RoleId = LAST_INSERT_ID();

INSERT INTO tbowner (tbownerfirstname, tbownerlastname, tbowneralias, tbowneridentificationnumber, tbownerimage, tbowneractive) VALUES
('Pedro', 'Hernández', 'Pedrito', '309876542', NULL, TRUE);
SET @owner2Id = LAST_INSERT_ID();

INSERT INTO tbroleowner (tbroleownerrolid, tbroleownerownerid, tbroleowneractive) VALUES
(@owner2RoleId, @owner2Id, TRUE);

INSERT INTO tbclient (tbclientname, tbclientimage, tbclientlocationid, tbclientactive) VALUES
('Daniela La Clienta', NULL, @locHeredia, TRUE);
SET @client2Id = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleclientrolid, tbroleclientclientid, tbroleclientactive) VALUES
(@client2RoleId, @client2Id, TRUE);

INSERT INTO tbclient (tbclientname, tbclientimage, tbclientlocationid, tbclientactive) VALUES
('Sofía La Clienta', NULL, @locAlajuela, TRUE);
SET @client3Id = LAST_INSERT_ID();

INSERT INTO tbroleclient (tbroleclientrolid, tbroleclientclientid, tbroleclientactive) VALUES
(@client3RoleId, @client3Id, TRUE);

-- =========================================================
-- 12) LOCALES DEL SEGUNDO PROPIETARIO (+ uno inactivo)
-- =========================================================
INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@owner2Id, @locSanRafael, 'Terraza Bambú', 'Jardín para eventos', 200, 140000.00, NULL, TRUE);
SET @venue4Id = LAST_INSERT_ID();

INSERT INTO tbvenue (tbvenueownerid, tbvenuelocationid, tbvenuename, tbvenuetype, tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive) VALUES
(@owner2Id, @locAlajuela, 'Bodega El Sur (inactiva)', 'Bodega', 300, 50000.00, NULL, FALSE);
SET @venue5Id = LAST_INSERT_ID();

-- Datos de cobro del segundo propietario
INSERT INTO tbownerpayment (tbownerpaymentownerid, tbownerpaymentpaymentmethodid, tbownerpaymentholder, tbownerpaymentaccount, tbownerpaymentinstructions, tbownerpaymentactive) VALUES
(@owner2Id, @pmEfectivo,      'Pedro Hernández',     NULL, 'Pago en efectivo el día del evento.', TRUE),
(@owner2Id, @pmTarjeta,       'Pedro Hernández',     NULL, 'Visa y Mastercard.', TRUE);

-- =========================================================
-- 13) SERVICIOS DEL SEGUNDO LOCAL
-- (incluye 1 en 'solicitado' para probar aprobación del admin
--  y 1 'rechazado' para ver el historial de revisión)
-- =========================================================
INSERT INTO tbservice (tbservicelocalid, tbservicename, tbservicetype, tbserviceprice, tbservicestate, tbserviceapprovedby, tbserviceapprovedon, tbserviceactive) VALUES
(@venue4Id, 'Iluminación ambiental',        'Producción',  70000.00,  'aprobado',  @adminRoleId, NOW(), TRUE),
(@venue4Id, 'Coctelería y bar abierto',     'Catering',    120000.00, 'aprobado',  @adminRoleId, NOW(), TRUE),
(@venue4Id, 'Mobiliario premium',           'Mobiliario',  80000.00,  'solicitado', NULL, NULL, TRUE),
(@venue4Id, 'Banda en vivo',                'Producción',  200000.00, 'rechazado', @adminRoleId, NOW(), TRUE);
SET @service8  = 8;
SET @service9  = 9;
SET @service10 = 10;
SET @service11 = 11;

-- =========================================================
-- 14) HISTORIAL DE PRECIOS DE SERVICIOS (tbservicehistory)
-- =========================================================
INSERT INTO tbservicehistory (tbservicehistoryserviceid, tbservicehistoryprice, tbservicehistoryvalidfrom, tbservicehistoryactive) VALUES
(@service1, 140000.00, '2025-01-01', FALSE),
(@service1, 150000.00, '2026-01-01', TRUE),
(@service8,  65000.00, '2025-01-01', FALSE),
(@service8,  70000.00, '2026-01-01', TRUE);

-- =========================================================
-- 15) PROMOCIÓN DEL SEGUNDO LOCAL + servicios incluidos
-- =========================================================
INSERT INTO tbpromotion (tbpromotionvenueid, tbpromotiondescription, tbpromotionlabel, tbpromotionstart, tbpromotionend, tbpromotionminservices, tbpromotionactive) VALUES
(@venue4Id, 'Pack celebración al aire libre', 'Pack Terraza', '2026-06-01', '2026-12-31', 2, TRUE);
SET @promo2Id = LAST_INSERT_ID();

INSERT INTO tbpromotionservice (tbpromotionservicepromotionid, tbpromotionserviceserviceid, tbpromotionserviceactive) VALUES
(@promo2Id, @service8, TRUE),
(@promo2Id, @service9, TRUE);

-- =========================================================
-- 16) RESERVAS COMPLETAS (rangos con fin, evento "otro",
--     todos los estados, factura + ticket + ganancia)
-- =========================================================
-- Reserva 2: RANGO DE 3 DÍAS + evento "otro" con descripción
-- (valida acumulación por día: 3 x 120000 + sonido)
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client2Id, @venueId, '2026-11-05', '2026-11-07', 'otro', 'Fiesta sorpresa de cumpleaños con temática tropical.', 'pendiente', TRUE);
SET @booking2 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 3, 120000.00, 0.00, TRUE);
SET @detail2a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking2, @detail2a, TRUE);

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service3, NULL, 1, 80000.00, 0.00, TRUE);
SET @detail2b = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking2, @detail2b, TRUE);

-- Reserva 3: CONFIRMADA + PAGADA + TICKET + GANANCIA
-- (ejemplo completo del flujo: cliente -> factura -> verificación -> ganancia)
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client2Id, @venue2Id, '2026-09-20', '2026-09-21', 'Boda', NULL, 'confirmado', TRUE);
SET @booking3 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue2Id, 2, 95000.00, 0.00, TRUE);
SET @detail3a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking3, @detail3a, TRUE);

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service5, NULL, 1, 60000.00, 0.00, TRUE);
SET @detail3b = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking3, @detail3b, TRUE);

INSERT INTO tbinvoice (tbinvoicebookingid, tbinvoicepaymentmethodid, tbinvoicestatus, tbinvoiceactive) VALUES
(@booking3, @pmTarjeta, 'pagada', TRUE);

INSERT INTO tbbookingticket (tbbookingticketbookingid, tbbookingticketfile, tbbookingtickettype, tbbookingticketpaymentmethodid, tbbookingticketstate, tbbookingticketactive) VALUES
(@booking3, 'uploads/tickets/comprobante-boda.jpg', 'jpg', @pmTarjeta, 'aprobado', TRUE);

-- Repartición: subtotal 250000 -> comisión 5% = 12500, IVA 13% = 32500,
-- total 282500, al propietario 237500 (= subtotal - comisión)
INSERT INTO tbeearning (tbeearningbookingid, tbeearningtotal, tbeearningcommission, tbeearningtax, tbeearningowneramount, tbeearningreviewedbyrole, tbeearningactive) VALUES
(@booking3, 282500.00, 12500.00, 32500.00, 237500.00, @adminRoleId, TRUE);

-- Reserva 4: PASADA Y CANCELADA (prueba estados y lista)
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client3Id, @venue3Id, '2026-08-01', '2026-08-01', 'Conferencia', NULL, 'cancelado', TRUE);
SET @booking4 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue3Id, 1, 180000.00, 0.00, TRUE);
SET @detail4a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking4, @detail4a, TRUE);

-- Reserva 5: RECHAZADA + evento "otro" (4 días)
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client3Id, @venueId, '2026-12-15', '2026-12-18', 'otro', 'Retiro empresarial para 60 personas con coffee break.', 'rechazado', TRUE);
SET @booking5 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venueId, 4, 120000.00, 0.00, TRUE);
SET @detail5a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking5, @detail5a, TRUE);

-- Reserva 6: pendiente con rango (calendario bloquea 01-03 oct)
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client2Id, @venue3Id, '2026-10-01', '2026-10-03', 'Graduación', NULL, 'pendiente', TRUE);
SET @booking6 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue3Id, 3, 180000.00, 0.00, TRUE);
SET @detail6a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking6, @detail6a, TRUE);

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service7, NULL, 1, 75000.00, 0.00, TRUE);
SET @detail6b = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking6, @detail6b, TRUE);

-- Reserva 7: segundo propietario recibe reserva (3 días, evento "otro")
INSERT INTO tbbooking (tbbookingclientid, tbbookinglocalid, tbbookingdate, tbbookingenddate, tbbookingeventtype, tbbookingeventdetail, tbbookingstate, tbbookingactive) VALUES
(@client3Id, @venue4Id, '2026-11-20', '2026-11-22', 'otro', 'Cena de aniversario al aire libre con show musical.', 'pendiente', TRUE);
SET @booking7 = LAST_INSERT_ID();

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(NULL, @venue4Id, 3, 140000.00, 0.00, TRUE);
SET @detail7a = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking7, @detail7a, TRUE);

INSERT INTO tbdetail (tbdetailserviceid, tbdetailvenueid, tbdetailquantity, tbdetailunitprice, tbdetaildiscount, tbdetailactive) VALUES
(@service8, NULL, 1, 70000.00, 0.00, TRUE);
SET @detail7b = LAST_INSERT_ID();

INSERT INTO tbbookingdetail (tbbookingdetailbookingid, tbbookingdetaildetailid, tbbookingdetailactive) VALUES
(@booking7, @detail7b, TRUE);

-- =========================================================
-- 17) AUDITORÍA (tbbookinghistory)
-- =========================================================
INSERT INTO tbbookinghistory (tbbookinghistorybookingid, tbbookinghistoryroleid, tbbookinghistoryaction, tbbookinghistorydetail, tbbookinghistorydate, tbbookinghistoryactive) VALUES
(@booking2, @adminRoleId,  'REPROGRAMAR',          'Rango anterior: 2026-11-04 - 2026-11-07 -> nuevo rango: 2026-11-05 - 2026-11-07', NOW(), TRUE),
(@booking3, @adminRoleId,  'REPROGRAMAR',          'Rango anterior: 2026-09-19 - 2026-09-21 -> nuevo rango: 2026-09-20 - 2026-09-21', NOW(), TRUE),
(@booking4, @client3RoleId, 'CANCELAR',             'Cancelada por el cliente.', NOW(), TRUE),
(@booking2, @client2RoleId,'SOLICITUD_REEMBOLSO',  'Solicitado reembolso de la renta del local.', NOW(), TRUE);

-- =========================================================
-- 18) SOLICITUD DE REEMBOLSO (queda 'pendiente' para el admin)
-- =========================================================
INSERT INTO tbbookingrefund (tbbookingrefundbookingid, tbbookingrefundclientroleid, tbbookingrefunddetail, tbbookingrefundstate, tbbookingrefunddate, tbbookingrefundactive) VALUES
(@booking2, @client2RoleId, 'Por imprevistos laborales no podré llevar a cabo el evento; solicito el reembolso de la renta del local.', 'pendiente', NOW(), TRUE);

-- =========================================================
-- 19) MÁS CALIFICACIONES (reseñas visibles y promediadas)
-- =========================================================
INSERT INTO tbvenuerating (tbvenueratingvenueid, tbvenueratingroleid, tbvenueratingstars, tbvenueratingcomment, tbvenueratingactive) VALUES
(@venueId,  @client2RoleId, 4, 'Muy buen salón, la ubicación es excelente.', TRUE),
(@venue2Id, @client2RoleId, 5, 'Jardín hermoso, ideal para bodas.', TRUE),
(@venue3Id, @client2RoleId, 5, 'Amplio y bien iluminado.', TRUE),
(@venue4Id, @client3RoleId, 4, 'Bonita terraza para eventos nocturnos.', TRUE);

INSERT INTO tbservicerating (tbserviceratingserviceid, tbserviceratingroleid, tbserviceratingstars, tbserviceratingcomment, tbserviceratingactive) VALUES
(@service3, @client2RoleId, 5, 'El sonido estuvo impecable.', TRUE),
(@service8, @client3RoleId, 4, 'La iluminación dio un gran ambiente.', TRUE);

-- =========================================================
-- 20) NOTIFICACIONES (para ver el campanario con contenido)
-- =========================================================
INSERT INTO tbnotification (tbnotificationroleid, tbnotificationmessage, tbnotificationlink, tbnotificationdate, tbnotificationread, tbnotificationactive) VALUES
(@ownerRoleId,    'Recibiste una nueva reserva en tu local: Salón La Quinta.',       'index.php?controller=booking&action=detail&id=2', NOW(), FALSE, TRUE),
(@adminRoleId,    'Se ha creado una nueva reserva.',                                  'index.php?controller=admin&action=bookingDetail&id=2', NOW(), FALSE, TRUE),
(@client2RoleId,  'Tu pago fue verificado y tu reserva ha sido aprobada.',           'index.php?controller=booking&action=detail&id=3', NOW(), TRUE, TRUE),
(@ownerRoleId,    'Recibiste una nueva reserva en tu local: Centro de Eventos La Y.','index.php?controller=booking&action=detail&id=6', NOW(), FALSE, TRUE),
(@owner2RoleId,   'Recibiste una nueva reserva en tu local: Terraza Bambú.',         'index.php?controller=booking&action=detail&id=7', NOW(), FALSE, TRUE),
(@adminRoleId,    'Un cliente solicitó un reembolso.',                               'index.php?controller=admin&action=bookingDetail&id=2', NOW(), FALSE, TRUE);

-- =========================================================
-- 21) HISTORIAL DE USUARIOS Y DE PROPIETARIOS
-- =========================================================
INSERT INTO tbuserhistory (tbuserhistoryroleid, tbuserhistoryaction, tbuserhistoryentity, tbuserhistoryentityid, tbuserhistorydate) VALUES
(@adminRoleId,    'VIEW',    'Venue', @venueId,  NOW()),
(@clientRoleId,   'BOOKING', 'Venue', @venueId,  NOW()),
(@client2RoleId,  'BOOKING', 'Venue', @venueId,  NOW()),
(@client2RoleId,  'VIEW',    'Venue', @venue4Id, NOW()),
(@client2RoleId,  'BOOKING', 'Venue', @venue3Id, NOW()),
(@client3RoleId,  'VIEW',    'Venue', @venue3Id, NOW()),
(@client3RoleId,  'BOOKING', 'Venue', @venueId,  NOW()),
(@client3RoleId,  'BOOKING', 'Venue', @venue4Id, NOW()),
(@ownerRoleId,    'VIEW',    'Venue', @venueId,  NOW());

INSERT INTO tbownerhistory (tbownerhistoryownerid, tbownerhistoryaction, tbownerhistorydetail, tbownerhistorydate, tbownerhistoryactive) VALUES
(@ownerId,  'CREAR_LOCAL',  'Creado "Salón La Quinta".', NOW(), TRUE),
(@ownerId,  'CREAR_LOCAL',  'Creado "Centro de Eventos La Y".', NOW(), TRUE),
(@ownerId,  'APROBAR_SERVICIO', 'Aprobado "Sonido y luces".', NOW(), TRUE),
(@owner2Id, 'CREAR_LOCAL',  'Creado "Terraza Bambú".', NOW(), TRUE);
