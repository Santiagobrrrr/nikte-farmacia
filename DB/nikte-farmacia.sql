CREATE DATABASE IF NOT EXISTS nikte_farmacia
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE nikte_farmacia;

CREATE TABLE Rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES Rol(id_rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE Cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE Producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    presentacion VARCHAR(50),
    precio_venta DECIMAL(10,2) NOT NULL,
    stock_minimo INT NOT NULL DEFAULT 0,
    requiere_receta BOOLEAN NOT NULL DEFAULT FALSE,
    uso_terapeutico VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE Proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE Lote (
    id_lote INT AUTO_INCREMENT PRIMARY KEY,
    codigo_lote VARCHAR(50) NOT NULL UNIQUE,
    id_producto INT NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    cantidad_actual INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    CONSTRAINT fk_lote_producto
        FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE Compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    fecha_compra DATE NOT NULL,
    id_proveedor INT NOT NULL,
    id_usuario INT NOT NULL,
    total_compra DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_compra_proveedor
        FOREIGN KEY (id_proveedor) REFERENCES Proveedor(id_proveedor)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_compra_usuario
        FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE Venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    fecha_venta DATETIME NOT NULL,
    id_usuario INT NOT NULL,
    id_cliente INT NULL,
    metodo_pago VARCHAR(30) NOT NULL,
    total_venta DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_venta_usuario
        FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_venta_cliente
        FOREIGN KEY (id_cliente) REFERENCES Cliente(id_cliente)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE DetalleCompra (
    id_detalle_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    id_lote INT NOT NULL,
    cantidad INT NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detallecompra_compra
        FOREIGN KEY (id_compra) REFERENCES Compra(id_compra)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detallecompra_producto
        FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_detallecompra_lote
        FOREIGN KEY (id_lote) REFERENCES Lote(id_lote)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE DetalleVenta (
    id_detalle_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    id_lote INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalleventa_venta
        FOREIGN KEY (id_venta) REFERENCES Venta(id_venta)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detalleventa_producto
        FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_detalleventa_lote
        FOREIGN KEY (id_lote) REFERENCES Lote(id_lote)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE Receta (
    id_receta INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL UNIQUE,
    nombre_medico VARCHAR(100) NOT NULL,
    numero_colegiado VARCHAR(50) NOT NULL,
    nombre_paciente VARCHAR(100) NOT NULL,
    observaciones TEXT,
    CONSTRAINT fk_receta_venta
        FOREIGN KEY (id_venta) REFERENCES Venta(id_venta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW productos_por_vencer AS
SELECT 
    p.id_producto,
    p.nombre AS nombre_producto,
    l.id_lote,
    l.codigo_lote,
    l.fecha_vencimiento,
    l.cantidad_actual
FROM Lote l
INNER JOIN Producto p ON p.id_producto = l.id_producto
WHERE l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND l.cantidad_actual > 0;

INSERT INTO Rol (nombre_rol, descripcion) VALUES
('administradora', 'Acceso total al sistema'),
('vendedora', 'Acceso a ventas y módulos limitados');

INSERT INTO Usuario (nombre, usuario, contrasena, id_rol) VALUES
('Admin', 'admin', '1234', 1),
('Vendedora', 'vendedora', '1234', 2);

INSERT INTO Producto (nombre, descripcion, presentacion, precio_venta, stock_minimo, requiere_receta, uso_terapeutico) VALUES
('Paracetamol 500 mg', 'Analgésico y antipirético', 'Caja x 10 tabletas', 5.00, 20, FALSE, 'Dolor y fiebre'),
('Ibuprofeno 400 mg', 'Antiinflamatorio no esteroideo', 'Caja x 10 tabletas', 8.50, 15, FALSE, 'Dolor e inflamación'),
('Amoxicilina 500 mg', 'Antibiótico de amplio espectro', 'Caja x 12 cápsulas', 15.00, 10, TRUE, 'Infecciones bacterianas');

INSERT INTO Lote (codigo_lote, id_producto, fecha_vencimiento, costo_unitario, cantidad_actual, fecha_ingreso) VALUES
('LOT-PARA-001', 1, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 3.50, 100, CURDATE()),
('LOT-IBU-001', 2, DATE_ADD(CURDATE(), INTERVAL 90 DAY), 6.00, 80, CURDATE()),
('LOT-AMOX-001', 3, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 11.00, 40, CURDATE());