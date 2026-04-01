CREATE DATABASE farmacia;
USE farmacia;

CREATE TABLE Rol (
    id_rol INT PRIMARY KEY IDENTITY(1,1),
    nombre_rol VARCHAR(100) NOT NULL,
    descripcion VARCHAR(150)
);

CREATE TABLE Usuario (
    id_usuario INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(100) NOT NULL,
    id_rol INT,

    CONSTRAINT FK_Usuario_Rol
    FOREIGN KEY (id_rol) REFERENCES Rol(id_rol)
);

CREATE TABLE Proveedor (
    id_proveedor INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion VARCHAR(150)
);

CREATE TABLE Producto (
    id_producto INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    presentacion VARCHAR(50),
    precio_venta DECIMAL(10,2),
    stock_minimo INT,
    requiere_receta BIT,
    uso_terapeutico VARCHAR(150)
);

CREATE TABLE Lote (
    id_lote INT IDENTITY(1,1) PRIMARY KEY,
    codigo_lote VARCHAR(50) NOT NULL,
    id_producto INT,
    fecha_vencimiento DATE,
    costo_unitario DECIMAL(10,2),
    cantidad_actual INT,
    fecha_ingreso DATE,

    CONSTRAINT FK_Lote_Producto
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto)
);

CREATE TABLE Cliente (
    id_cliente INT IDENTITY(1,1) PRIMARY KEY,
    nombre VARCHAR(100),
    telefono VARCHAR(20),
    direccion VARCHAR(150)
);

CREATE TABLE Venta (
    id_venta INT IDENTITY(1,1) PRIMARY KEY,
    fecha_venta DATETIME,
    id_usuario INT,
    id_cliente INT,
    metodo_pago VARCHAR(30),
    total_venta DECIMAL(10,2),

    CONSTRAINT FK_Venta_Usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),

    CONSTRAINT FK_Venta_Cliente
    FOREIGN KEY (id_cliente) REFERENCES Cliente(id_cliente)
);


CREATE TABLE DetalleVenta (
    id_detalle_venta INT IDENTITY(1,1) PRIMARY KEY,
    id_venta INT,
    id_producto INT,
    id_lote INT,
    cantidad INT,
    precio_unitario DECIMAL(10,2),
    subtotal DECIMAL(10,2),

    CONSTRAINT FK_DetalleVenta_Venta
    FOREIGN KEY (id_venta) REFERENCES Venta(id_venta),

    CONSTRAINT FK_DetalleVenta_Producto
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),

    CONSTRAINT FK_DetalleVenta_Lote
    FOREIGN KEY (id_lote) REFERENCES Lote(id_lote)
);

CREATE TABLE Receta (
    id_receta INT IDENTITY(1,1) PRIMARY KEY,
    id_venta INT,
    nombre_medico VARCHAR(100),
    numero_colegiado VARCHAR(50),
    nombre_paciente VARCHAR(100),
    observaciones TEXT,

    CONSTRAINT FK_Receta_Venta
    FOREIGN KEY (id_venta) REFERENCES Venta(id_venta)
);

CREATE TABLE Compra (
    id_compra INT IDENTITY(1,1) PRIMARY KEY,
    fecha_compra DATE,
    id_proveedor INT,
    id_usuario INT,
    total_compra DECIMAL(10,2),

    CONSTRAINT FK_Compra_Proveedor
    FOREIGN KEY (id_proveedor) REFERENCES Proveedor(id_proveedor),

    CONSTRAINT FK_Compra_Usuario
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

CREATE TABLE DetalleCompra (
    id_detalle_compra INT IDENTITY(1,1) PRIMARY KEY,
    id_compra INT,
    id_producto INT,
    id_lote INT,
    cantidad INT,
    costo_unitario DECIMAL(10,2),
    subtotal DECIMAL(10,2),

    CONSTRAINT FK_DetalleCompra_Compra
    FOREIGN KEY (id_compra) REFERENCES Compra(id_compra),

    CONSTRAINT FK_DetalleCompra_Producto
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),

    CONSTRAINT FK_DetalleCompra_Lote
    FOREIGN KEY (id_lote) REFERENCES Lote(id_lote)
);

CREATE VIEW productos_por_vencer AS
SELECT 
    p.nombre,
    l.codigo_lote,
    l.fecha_vencimiento,
    l.cantidad_actual
FROM Lote l
JOIN Producto p 
ON p.id_producto = l.id_producto
WHERE l.fecha_vencimiento <= DATEADD(DAY, 30, GETDATE());