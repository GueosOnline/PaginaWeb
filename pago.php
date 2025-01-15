<?php
require 'config/config.php';
require 'clases/clienteFunciones.php';
require_once 'config/database.php';

$db = new Database();
$con = $db->conectar();

$productos = isset($_SESSION['carrito']['productos']) ? $_SESSION['carrito']['productos'] : null;

$lista_carrito = array();
$total = 0;  // Inicializamos el total

if ($productos != null) {
    foreach ($productos as $clave => $cantidad) {
        $sql = $con->prepare("SELECT id, nombre, precio, descuento FROM productos WHERE id=? AND activo=1");
        $sql->execute([$clave]);
        $producto = $sql->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            // Calculamos el precio con descuento y el IVA
            $precio = $producto['precio'];
            $descuento = $producto['descuento'];
            $precio_desc = $precio - (($precio * $descuento) / 100);
            $precio_descIva = redondearPrecio($precio_desc * 1.19); // Aplicamos IVA

            $subtotal = $cantidad * $precio_descIva;
            $total += $subtotal;  // Sumamos al total
        }
    }
} else {
    header("Location: index.php");
    exit;
}

if (isset($_SESSION['user_cliente'])) {
    $cliente_id = $_SESSION['user_cliente'];

    // Consulta para obtener el departamento del cliente
    $sql = $con->prepare("SELECT departamento FROM clientes WHERE id = ?");
    $sql->execute([$cliente_id]);
    $cliente = $sql->fetch(PDO::FETCH_ASSOC);

    // Si el cliente existe, obtenemos el departamento
    if ($cliente) {
        $departamento = $cliente['departamento'];
    } else {
        // Si no se encuentra el cliente, redirigimos o mostramos un error
        header('Location: login.php');
        exit;
    }
} else {
    // Si no está logueado, redirigimos al login
    header('Location: login.php');
    exit;
}

$envioSeleccionado = isset($_POST['envio']) ? $_POST['envio'] : null;
$envioCosto = 0;  // Inicializamos el costo de envío

// Verificar si se ha seleccionado una opción de envío
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['envio'])) {
    $envioSeleccionado = $_POST['envio'];  // Capturamos la opción seleccionada

    // Lógica para calcular el costo de envío
    if ($envioSeleccionado === 'mensajeria_gueos') {
        $envioCosto = 10000;  // Costo de la mensajería Gueos
    } elseif ($envioSeleccionado === 'tienda_gueos') {
        $envioCosto = 0;  // Envío gratis con Tienda Gueos
    } elseif ($envioSeleccionado === 'transportadora') {
        // Transportadora: Mostramos la alerta
        echo "<script>alert('El precio del transporte será notificado luego de enviar el pedido');</script>";
        $envioCosto = 0;  // No sumamos costo extra aquí, ya que es dinámico
    }

    // Calculamos el total final con el costo de envío
    $totalFinal = $total + $envioCosto;
    $_SESSION['carrito']['total'] = $totalFinal;
    $_SESSION['envioSeleccionado'] = $envioSeleccionado;  // Tipo de envío seleccionado
    $_SESSION['envioCosto'] = $envioCosto;
} else {
    // Si no se ha enviado el formulario, el costo de envío es 0 por defecto
    $envioCosto = 0;
    $totalFinal = $total;  // Si no se ha seleccionado nada, solo mostramos el total de los productos
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-compatible" content="IEwedge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Online</title>
    <!-- Boostrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="css/estilos.css" rel="stylesheet">
    <style>
        /* Estilo del botón Wompi */
        .wompi-button {
            background-color: #b8f4ac;
            color: black;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.3s;
        }

        .wompi-button:hover {
            background-color: #8bd29f;
        }

        .wompi-logo {
            width: 100px;
            height: auto;
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main>
        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($lista_carrito == null) {
                                    echo '<tr><td colspan="5" class="text-center"><b>Lista vacia</b></td></tr>';
                                } else {
                                    foreach ($lista_carrito as $producto) {
                                        $nombre = $producto['nombre'];
                                        $precio = $producto['precio'];
                                        $precio_desc = $precio - (($precio * $producto['descuento']) / 100);
                                        $precio_descIva = redondearPrecio($precio_desc * 1.19);
                                        $subtotal = $producto['cantidad'] * $precio_descIva;
                                ?>
                                        <tr>
                                            <td><?php echo $nombre; ?></td>
                                            <td><?php echo $producto['cantidad'] . ' x ' . MONEDA . number_format($subtotal, 2, '.', ','); ?></td>
                                        </tr>
                                <?php }
                                } ?>
                                <tr>
                                    <td>
                                        <h4>Pagas</h4>
                                    </td>
                                    <td>
                                        <p class="h3" id="total"><?php echo MONEDA . number_format($total, 2, '.', ','); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <h4>Envío</h4>
                                    </td>
                                    <td>
                                        <p class="h3" id="totalEnvio"><?php echo MONEDA . number_format($envioCosto, 2, '.', ','); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <h4>Total</h4>
                                    </td>
                                    <td>
                                        <p class="h3" id="totalFinal"><?php echo MONEDA . number_format($totalFinal, 2, '.', ','); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Opciones de Envío -->
                <div class="col-3">
                    <h4>Opciones de Envío</h4>
                    <form action="pago.php" method="POST">
                        <!-- Opción 1: Mensajería Gueos -->
                        <?php if (strpos($departamento, 'Bogotá') !== false): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="envio" id="envio_gueos" value="mensajeria_gueos" <?php echo ($envioSeleccionado == 'mensajeria_gueos') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="envio_gueos">Mensajería Gueos + $10.000 COP</label>
                            </div>
                        <?php else: ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="envio" id="envio_gueos" value="mensajeria_gueos" disabled>
                                <label class="form-check-label" for="envio_gueos">Mensajería Gueos (Solo disponible para Bogotá)</label>
                            </div>
                        <?php endif; ?>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="envio" id="envio_tienda" value="tienda_gueos" <?php echo ($envioSeleccionado == 'tienda_gueos') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="envio_tienda">Tienda Gueos + $0 COP</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="envio" id="envio_transportadora" value="transportadora" <?php echo ($envioSeleccionado == 'transportadora') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="envio_transportadora">Transportadora + Dependiendo de la ubicación</label>
                        </div>

                        <!-- Enviar formulario -->
                        <button type="submit" class="btn btn-primary mt-3">Confirmar Envío</button>
                    </form>

                </div>

                <!-- Formulario Wompi -->
                <div class="col-3">
                    <?php
                    $reference = uniqid('gueos_', true);
                    $totalwompi = $totalFinal * 100; // Wompi espera el monto en centavos
                    $data = $reference . '' . $totalwompi . 'COP' . INTEGRITY_WP;
                    $signature = hash('sha256', $data);
                    ?>
                    <form action="https://checkout.wompi.co/p/" method="GET">
                        <input type="hidden" name="public-key" value="<?php echo PUBLIC_KEY_WP; ?>" />
                        <input type="hidden" name="currency" value="COP" />
                        <input type="hidden" name="amount-in-cents" value="<?php echo $totalwompi; ?>" />
                        <input type="hidden" name="reference" value="<?php echo $reference; ?>" />
                        <input type="hidden" name="signature:integrity" value="<?php echo $signature; ?>" />
                        <input type="hidden" name="redirect-url" value="<?php echo SITE_URL; ?>clases/captura_wp.php?status=approved" />
                        <input type="hidden" name="failure-redirect-url" value="<?php echo SITE_URL; ?>clases/fallo.php?status=rejected" />
                        <button type="submit" class="wompi-button">
                            Paga con<br><img src="images/logos/LogoWompi.png" alt="Logo de Wompi" class="wompi-logo" />
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php

        ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>