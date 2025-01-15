<?php

//Script para capturar detalles de pago de Wompi
require '../config/config.php';
require 'clienteFunciones.php';

$db = new Database();
$con = $db->conectar();

$idTransaccion = isset($_GET['id']) ? $_GET['id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

$envioSeleccionado = isset($_SESSION['envioSeleccionado']) ? $_SESSION['envioSeleccionado'] : '';
$envioCosto = isset($_SESSION['envioCosto']) ? $_SESSION['envioCosto'] : 0;


if ($idTransaccion != '') {

    date_default_timezone_set('America/Bogota'); //comprobar...
    $fecha = date("Y-m-d H:i:s");
    $total = isset($_SESSION['carrito']['total']) ? $_SESSION['carrito']['total'] : 0;
    $idCliente = $_SESSION['user_cliente'];
    $sqlProd = $con->prepare("SELECT email FROM clientes WHERE id=? AND estatus=1");
    $sqlProd->execute([$idCliente]);
    $row_cliente = $sqlProd->fetch(PDO::FETCH_ASSOC);

    $email = $row_cliente['email'];

    // Agregar columnas en la tabla 'compra' para el envío
    $comando = $con->prepare("INSERT INTO compra (fecha, status, email, id_cliente, total, id_transaccion, medio_pago, metodo_envio, costo_envio) VALUES(?,?,?,?,?,?,?,?,?)");
    $comando->execute([$fecha, $status, $email, $idCliente, $total, $idTransaccion, 'Wompi', $envioSeleccionado, $envioCosto]);

    $id = $con->lastInsertId();

    if ($id > 0) {
        $productos = isset($_SESSION['carrito']['productos']) ? $_SESSION['carrito']['productos'] : null;

        if ($productos != null) {
            foreach ($productos as $clave => $cantidad) {

                $sqlProd = $con->prepare("SELECT id, nombre, precio, descuento FROM productos WHERE id=? AND activo=1");
                $sqlProd->execute([$clave]);
                $row_prod = $sqlProd->fetch(PDO::FETCH_ASSOC);

                $precio = $row_prod['precio'];
                $descuento = $row_prod['descuento'];
                $precio_desc = $precio - (($precio * $descuento) / 100);
                $precio_descIva = redondearPrecio($precio_desc * 1.19);

                $sql = $con->prepare("INSERT INTO detalle_compra (id_compra, id_producto, nombre, cantidad, precio) VALUES(?,?,?,?,?)");
                if ($sql->execute([$id, $row_prod['id'], $row_prod['nombre'], $cantidad, $precio_descIva])) {
                    restarStock($row_prod['id'], $cantidad, $con);
                }
            }

            #--------------------------------------------------------------------
            # Envio de correo Electronico por la compra
            #--------------------------------------------------------------------


            $asunto = "Detalles de su pedido - Tienda online";

            $cuerpo = "";
            $cuerpo .= '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                        background-color: #f9f9f9;
                    }
            
                    .container {
                        width: 100%;
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    }
            
                    .header {
                        text-align: center;
                        padding: 20px 0;
                    }
            
                    .header img {
                        max-width: 200px;
                    }
            
                    .content {
                        margin-top: 20px;
                        font-size: 16px;
                        color: #333;
                    }
            
                    .content h4 {
                        font-size: 22px;
                        color: #333;
                        text-align: center; 
                    }
            
                    .content p {
                        color: #555;
                        font-size: 16px;
                        line-height: 1.6;
                    }
            
                    .content .order-id {
                        font-weight: bold;
                        color: #333;
                    }
            
                    .footer {
                        text-align: center;
                        margin-top: 30px;
                        font-size: 14px;
                        color: #888;
                    }
            
                    .footer p {
                        margin: 5px 0;
                    }
            
                    .custom-hr {
                        border: 0;
                        border-top: 2px solid #333;
                        margin: 20px 0;
                    }
            
                    /* Estilos para la tabla del pedido */
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
            
                    table th, table td {
                        padding: 8px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                    }
            
                    table th {
                        background-color: #f2f2f2;
                        font-weight: bold;
                    }
            
                    /* Estilos para dispositivos móviles */
                    @media screen and (max-width: 600px) {
                        .container {
                            padding: 15px;
                        }
            
                        .header img {
                            max-width: 150px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <!-- Logo -->
                    <div class="header">
                    
                        <img src="https://es.digitaltrends.com/wp-content/uploads/2023/12/google-chrome.jpeg?resize=1000%2C600&p=1" alt="Logo de la Tienda">
                    </div>
            
                    <!-- Línea divisoria -->
                    <hr class="custom-hr">
            
                    <!-- Contenido principal -->
                   <div class="content">
                        <h4 style="text-center">¡Gracias por su compra!</h4>
                        <p>Le agradecemos por realizar su compra en <strong>Representaciones Gueos</strong>.</p>
                        <p>El ID de su compra es: <span class="order-id">' . $idTransaccion . '</span></p>
                        <p>Su pedido está siendo procesado y recibirá una notificación cuando haya sido enviado.</p>
                        <p>Detalles de la compra:</p>

                        <!-- Detalles del pedido -->
                        <table>
                            <thead>
                                <tr>
                                    <th>Cantidad</th>
                                    <th>Producto</th>
                                    <th>Descuento</th>
                                    <th>SubTotal</th>
                                </tr>
                            </thead>
                            <tbody>';

            // Recorrer los productos del carrito
            if (isset($_SESSION['carrito']['productos'])) {
                $productos = $_SESSION['carrito']['productos'];
                foreach ($productos as $clave => $cantidad) {
                    $sqlProd = $con->prepare("SELECT nombre, precio, descuento FROM productos WHERE id=? AND activo=1");
                    $sqlProd->execute([$clave]);
                    $row_prod = $sqlProd->fetch(PDO::FETCH_ASSOC);
                    $precio = $row_prod['precio'];
                    $descuento = $row_prod['descuento'];
                    $precio_desc = $precio - (($precio * $descuento) / 100);
                    $precio_descIva = redondearPrecio($precio_desc * 1.19);
                    $subTotal = $cantidad * $precio_descIva;
                    $cuerpo .= '<tr>
                                        <td>' . $cantidad . '</td>
                                        <td>' . $row_prod['nombre'] . '</td>
                                        <td>' . $descuento . "%" . '</td>
                                        <td>$' . number_format($subTotal, 0, '.', ',') . '</td>
                                    </tr>';
                }
            }

            $cuerpo .= '</tbody>
                        </table>
            
                        <!-- Total -->
                        <p style="margin-top: 20px; font-weight: bold;">Total: $' . number_format($total, 0, '.', ',') . '</p>
                    </div>
            
                    <!-- Línea divisoria -->
                    <hr class="custom-hr">
            
                    <!-- Pie de página -->
                    <div class="footer">
                        <p>&copy; 2025 Representaciones Gueos Ltda. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>';

            require 'Mailer.php';
            $mailer = new Mailer();

            $asuntoCopia = "Se ha realizado una VENTA - Tienda Gueos";
            $cuerpoCopia = $cuerpo;
            $emailVentas = MAIL_USER;

            $mailer->enviarEmail($email, $asunto, $cuerpo, $emailVentas, $asuntoCopia, $cuerpoCopia);


            #--------------------------------------------------------------------
            # Envio de correo Electronico a la copia (Administrador)
            #--------------------------------------------------------------------

        }

        unset($_SESSION['carrito']);
        header("Location: " . SITE_URL . "completado.php?key=" . $idTransaccion);
    }
}

function restarStock($id, $cantidad, $con)
{
    $sqlProd = $con->prepare("UPDATE productos SET stock = stock - ? WHERE id=?");
    $sqlProd->execute([$cantidad, $id]);
}
