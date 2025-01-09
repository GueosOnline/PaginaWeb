<?php

/* Pantalla... */

require 'config/config.php';

$db = new Database();
$con = $db->conectar();

// añadir una columna para destacar productos a gusto, 
// validaciones para el producto mas comprado 
// validaciones para el producto con mas click en la pagina desde el boton de detalles o el link de la imagen


/*Consulta para productos con descuento mas del 50%*/
$sqldesc = "SELECT id, slug, nombre, precio, descuento FROM productos WHERE descuento >= 0";

$querydesc = $con->prepare($sqldesc);
$querydesc->execute();
$resdesc = $querydesc->fetchAll(PDO::FETCH_ASSOC);

/*Consulta para los 10 productos mas comprados*/

$sqlcompras = "SELECT p.id, p.slug, p.nombre, p.precio, p.descuento, SUM(dc.cantidad) AS total_vendido
FROM productos p
JOIN detalle_compra dc ON p.id = dc.id_producto
GROUP BY p.id
ORDER BY total_vendido DESC
LIMIT 10";

$querycompras = $con->prepare($sqlcompras);
$querycompras->execute();
$rescompras = $querycompras->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tienda en linea</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">

    <style>
        /* Asegura que las imágenes dentro del carrusel ocupen todo el ancho */
        .carousel-item img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        /* Contenedor que habilita el desplazamiento horizontal */
        .productos-scroll-wrapper {
            position: relative;
            overflow: hidden;
            /* Oculta la barra de desplazamiento */
        }

        .productos-scroll {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            flex-wrap: nowrap;
            scroll-behavior: smooth;
            /* Suaviza el desplazamiento */
            overflow-x: scroll;
            /* Permite desplazamiento sin mostrar la barra */
            -ms-overflow-style: none;
            /* Oculta la barra de desplazamiento en IE */
            scrollbar-width: none;
            /* Oculta la barra de desplazamiento en Firefox */
            scroll-behavior: smooth;
        }



        .productos-marco {
            border-radius: 10px;
            padding: 20px;
            background-color: rgb(255, 255, 255);
        }

        .precio {
            margin: 0;
            opacity: 0.5;
        }

        .precio_desc,
        .descuento {
            display: inline-block;
        }

        /* Estilo de los botones de desplazamiento */
        .scroll-button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            font-size: 18px;
        }

        .scroll-button-left {
            left: 10px;
        }

        .scroll-button-right {
            right: 10px;
        }
    </style>

</head>

<body class="d-flex flex-column h-100" style="background-color:rgb(230, 223, 230);">

    <?php include 'header.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container">
        </div>
    </main>

    <main>
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-12 col-md-12 order-md-1">
                    <!--Carrusel-->
                    <div id="carouselImages" class="carousel slide" data-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img class="d-block w-100" src="images/publicidad/quienSomos.png" alt="First slide">
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="images/publicidad/1.png" alt="Second slide">
                            </div>
                            <div class="carousel-item">
                                <img class="d-block w-100" src="images/publicidad/2.png" alt="Third slide">
                            </div>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselImages" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselImages" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenedor de productos con borde y esquinas redondeadas -->
        <div class="container p-3">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="productos-marco mt-4 mb-3">
                        <h3>Productos con un gran DESCUENTO</h3>
                        <div class="productos-scroll-wrapper">
                            <button class="scroll-button scroll-button-left" onclick="scrollProductos('left','productos-desc')">&#60;</button>

                            <div id="productos-desc" class="productos-scroll">
                                <?php foreach ($resdesc as $row) { ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex">
                                        <div class="card w-100 my-2 shadow-2-strong">
                                            <?php
                                            $id = $row['id'];
                                            $descuento = $row['descuento'];
                                            $precio = $row['precio'];
                                            $precio_desc = $precio - (($precio * $descuento) / 100);
                                            $imagen = "images/productos/$id/principal.jpg";
                                            if (!file_exists($imagen)) {
                                                $imagen = "images/no-photo.jpg";
                                            }
                                            ?>
                                            <a href="details/<?php echo $row['slug']; ?>">
                                                <img src="<?php echo $imagen; ?>" class="img-thumbnail" style="max-height: 300px">
                                            </a>

                                            <div class="card-body d-flex flex-column">
                                                <div class="">
                                                    <?php if ($descuento > 0) { ?>
                                                        <p class="precio transparent-text"><del><small><?php echo MONEDA . number_format($precio, 2, '.', ','); ?></small></del></p>

                                                        <h3 class="precio_desc">
                                                            <?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?>
                                                        </h3>
                                                        <h5 class="descuento text-success "> <?php echo $descuento; ?>% OFF</h5>
                                                    <?php } else { ?>
                                                        <h3><?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?></h3>
                                                    <?php } ?>
                                                </div>
                                                <p class="card-text"><?php echo $row['nombre']; ?></p>
                                            </div>

                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <a class="btn btn-success" onClick="addProducto(<?php echo $row['id']; ?>)">Agregar</a>
                                                    <div class="btn-group">
                                                        <a href="details/<?php echo $row['slug']; ?>" class="btn btn-primary">Detalles</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <button class="scroll-button scroll-button-right" onclick="scrollProductos('right', 'productos-desc')">&#62;</button>
                        </div>
                    </div>
                    <div class="productos-marco mt-4 mb-3">
                        <h3>Productos mas vendidos</h3>
                        <div class="productos-scroll-wrapper">
                            <button class="scroll-button scroll-button-left" onclick="scrollProductos('left', 'productos-vendidos')">&#60;</button>

                            <div id="productos-vendidos" class="productos-scroll">
                                <?php foreach ($rescompras as $row) { ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 d-flex">
                                        <div class="card w-100 my-2 shadow-2-strong">
                                            <?php
                                            $id = $row['id'];
                                            $descuento = $row['descuento'];
                                            $precio = $row['precio'];
                                            $precio_desc = $precio - (($precio * $descuento) / 100);
                                            $imagen = "images/productos/$id/principal.jpg";
                                            if (!file_exists($imagen)) {
                                                $imagen = "images/no-photo.jpg";
                                            }
                                            ?>
                                            <a href="details/<?php echo $row['slug']; ?>">
                                                <img src="<?php echo $imagen; ?>" class="img-thumbnail" style="max-height: 300px">
                                            </a>

                                            <div class="card-body d-flex flex-column">
                                                <div class="">
                                                    <?php if ($descuento > 0) { ?>
                                                        <p class="precio transparent-text"><del><small><?php echo MONEDA . number_format($precio, 2, '.', ','); ?></small></del></p>

                                                        <h3 class="precio_desc">
                                                            <?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?>
                                                        </h3>
                                                        <h5 class="descuento text-success "> <?php echo $descuento; ?>% OFF</h5>
                                                    <?php } else { ?>
                                                        <h3><?php echo MONEDA . number_format($precio_desc, 2, '.', ','); ?></h3>
                                                    <?php } ?>
                                                </div>
                                                <p class="card-text"><?php echo $row['nombre']; ?></p>
                                            </div>

                                            <div class="card-footer bg-transparent">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <a class="btn btn-success" onClick="addProducto(<?php echo $row['id']; ?>)">Agregar</a>
                                                    <div class="btn-group">
                                                        <a href="details/<?php echo $row['slug']; ?>" class="btn btn-primary">Detalles</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                            <button class="scroll-button scroll-button-right" onclick="scrollProductos('right', 'productos-vendidos')">&#62;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="social-buttons">
            <a href="https://www.facebook.com/RepGueos" title="Facebook" class="facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://x.com/RepGueosLtda" title="Twitter" class="twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/representaciones.gueos/" title="Instagram" class="instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://www.youtube.com/@gueos-RG" title="youtube" class="youtube"><i class="fab fa-youtube"></i></a>
            <a href="https://www.linkedin.com/company/representacionesgueosltda" title="LinkedIn" class="linkedin"><i class="fab fa-linkedin-in"></i></a>
        </div>

        <a href="https://wa.me/573165814009?text=Hola%2C%20tengo%20una%20consulta" title="Contáctanos por WhatsApp" class="whatsapp-link">
            <div class="sm-shake bottom-center sm-fixed sm-button sm-button-text sm-rounded">
                <i class="fab fa-whatsapp"></i> <!-- Icono de WhatsApp -->
                <span>&nbsp;</span>
                <span>&nbsp;</span>
                <span style="color: white;">Contáctanos por WhatsApp</span>
            </div>
        </a>

    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollProductos(direction, containerId) {
            const scrollContainer = document.getElementById(containerId);

            // Ancho de un producto (considerando que tienes 4 productos visibles)
            const productWidth = document.querySelector(`#${containerId} .col-lg-3`).offsetWidth;

            // Desplazamiento de 4 productos (4 * ancho de un producto)
            const scrollAmount = productWidth * 4;

            if (direction === 'left') {
                scrollContainer.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else if (direction === 'right') {
                scrollContainer.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        }
    </script>

</body>

</html>