<?php
// Inicializamos la base de datos
require 'config/config.php';
require 'clases/clienteFunciones.php';

$db = new Database();
$con = $db->conectar();

// Recibimos los parámetros de filtrado
$idCategoria = $_GET['cat'] ?? '';
$idSubcategoria = $_GET['subcategoria'] ?? '';
$idSubsubcategoria = $_GET['subsubcategoria'] ?? '';
$orden = $_GET['orden'] ?? '';
$buscar = $_GET['q'] ?? '';

// Opciones de orden
$orders = [
    'asc' => 'nombre ASC',
    'desc' => 'nombre DESC',
    'precio_alto' => 'precio DESC',
    'precio_bajo' => 'precio ASC',
];
$order = $orders[$orden] ?? '';

// Preparamos la consulta SQL
$sql = "SELECT id, slug, nombre, precio, descuento FROM productos WHERE activo = 1";
$params = [];

// Filtro de búsqueda
if (!empty($buscar)) {
    $sql .= " AND (id LIKE ? OR nombre LIKE ? OR descripcion LIKE ? )";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

// Filtro de categoría, subcategoría y sub-subcategoría
if (!empty($idCategoria)) {
    $subcategorias = getSubcategorias($con, $idCategoria);
    if (!empty($subcategorias)) {
        $sql .= " AND id_categoria IN (" . implode(',', $subcategorias) . ")";
    }
}

if (!empty($idSubcategoria)) {
    $subsubcategorias = getSubcategorias($con, $idSubcategoria);
    if (!empty($subsubcategorias)) {
        $sql .= " AND id_categoria IN (" . implode(',', $subsubcategorias) . ")";
    } else {
        $sql .= " AND id_categoria = :subcategoria";
        $params[':subcategoria'] = $idSubcategoria;
    }
}

if (!empty($idSubsubcategoria)) {
    $sql .= " AND id_categoria = :subsubcategoria";
    $params[':subsubcategoria'] = $idSubsubcategoria;
}

// Filtro de orden
if (!empty($order)) {
    $sql .= " ORDER BY $order";
}

// Ejecutamos la consulta
$query = $con->prepare($sql);
$query->execute($params);
$resultado = $query->fetchAll(PDO::FETCH_ASSOC);
$totalRegistros = count($resultado);

// Aquí vamos a cargar las categorías y subcategorías (sin cambios)
$categoriaSql = $con->prepare("SELECT id, nombre, id_padre FROM categorias WHERE activo=1");
$categoriaSql->execute();
$categorias = $categoriaSql->fetchAll(PDO::FETCH_ASSOC);

// Aquí asignamos las subcategorías a las categorías principales
$categoriasArray = [];
foreach ($categorias as $categoria) {
    if ($categoria['id_padre'] === NULL) {
        $categoriasArray[$categoria['id']] = [
            'nombre' => $categoria['nombre'],
            'subcategorias' => []
        ];
    } else {
        if (isset($categoriasArray[$categoria['id_padre']])) {
            $categoriasArray[$categoria['id_padre']]['subcategorias'][] = [
                'nombre' => $categoria['nombre'],
                'id' => $categoria['id'],
                'subsubcategorias' => [] // Creamos el array para subsubcategorías
            ];
        }
    }
}

// Obtener subsubcategorías
foreach ($categorias as $categoria) {
    if ($categoria['id_padre'] !== NULL) {
        $parentId = $categoria['id_padre'];
        $subsubcategoriaSql = $con->prepare("SELECT id, nombre FROM categorias WHERE id_padre = ? AND activo = 1");
        $subsubcategoriaSql->execute([$categoria['id']]);
        $subsubcategorias = $subsubcategoriaSql->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categoriasArray as $key => &$parentCategoria) {
            foreach ($parentCategoria['subcategorias'] as &$subcategoria) {
                if ($subcategoria['id'] == $categoria['id']) {
                    $subcategoria['subsubcategorias'] = $subsubcategorias;
                }
            }
        }
    }
}

// Función para obtener subcategorías (sin cambios)
function getSubcategorias($con, $idCategoria)
{
    $subcategorias = [];
    $sql = "SELECT id FROM categorias WHERE id_padre = ? AND activo = 1";
    $stmt = $con->prepare($sql);
    $stmt->execute([$idCategoria]);
    $subcategorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($subcategorias as $subcategoria) {
        $subsubcategorias = getSubcategorias($con, $subcategoria);
        $subcategorias = array_merge($subcategorias, $subsubcategorias);
    }

    return $subcategorias;
}
?>


<!DOCTYPE html>
<html lang="es" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catalogo</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
    <link href="css/estilos.css" rel="stylesheet">

    <style>

    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php include 'header.php'; ?>

    <!-- Contenido -->
    <main class="flex-shrink-0">
        <div class="container p-3">
            <div class="row">
                <div class="col-12 col-md-3 col-lg-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            Filtros
                        </div>
                        <div class="card-body">
                            <!-- Filtro de categoría -->
                            <form method="get" action="index.php">
                                <!-- Filtro de Categoría -->
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <select class="form-select" id="categoria" name="cat" onchange="actualizarSubcategorias()">
                                        <option value="">Selecciona una categoría</option>
                                        <?php foreach ($categoriasArray as $categoriaId => $categoria) { ?>
                                            <option value="<?php echo $categoriaId; ?>" <?php echo ($categoriaId == $idCategoria) ? 'selected' : ''; ?>>
                                                <?php echo $categoria['nombre']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <!-- Filtro de Subcategoría -->
                                <div class="mb-3" id="subcategoria-container">
                                    <label for="subcategoria" class="form-label">Subcategoría</label>
                                    <select class="form-select" id="subcategoria" name="subcategoria" onchange="actualizarSubsubcategorias()">
                                        <option value="">Selecciona una subcategoría</option>
                                        <?php foreach ($categoriasArray[$idCategoria]['subcategorias'] ?? [] as $subcategoria) { ?>
                                            <option value="<?php echo $subcategoria['id']; ?>" <?php echo ($subcategoria['id'] == $idSubcategoria) ? 'selected' : ''; ?>>
                                                <?php echo $subcategoria['nombre']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <!-- Filtro de Sub-subcategoría -->
                                <div class="mb-3" id="subsubcategoria-container">
                                    <label for="subsubcategoria" class="form-label">Sub-subcategoría</label>
                                    <select class="form-select" id="subsubcategoria" name="subsubcategoria">
                                        <option value="">Selecciona una sub-subcategoría</option>
                                        <?php if (!empty($subsubcategorias)) { ?>
                                            <?php foreach ($subsubcategorias as $subsubcategoria) { ?>
                                                <option value="<?php echo $subsubcategoria['id']; ?>" <?php echo ($subsubcategoria['id'] == $idSubsubcategoria) ? 'selected' : ''; ?>>
                                                    <?php echo $subsubcategoria['nombre']; ?>
                                                </option>
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>

                                <!-- Filtro de Búsqueda -->
                                <div class="mb-3">
                                    <label for="search" class="form-label">Buscar</label>
                                    <input type="text" name="q" class="form-control" placeholder="Buscar..." aria-describedby="icon-buscar" value="<?php echo htmlspecialchars($buscar); ?>">
                                </div>

                                <!-- Botón de Aplicar Filtros -->

                                <label for="cbx-orden" class="form-label">Ordena por</label>
                                <select class="form-select d-inline-block w-auto pt-1 form-select-sm" name="orden" id="orden">
                                    <option value="vacio" <?php echo (empty($orden)) ? 'selected' : ''; ?>></option>
                                    <option value="precio_alto" <?php echo ($orden === 'precio_alto') ? 'selected' : ''; ?>>Precios más altos</option>
                                    <option value="precio_bajo" <?php echo ($orden === 'precio_bajo') ? 'selected' : ''; ?>>Precios más bajos</option>
                                    <option value="asc" <?php echo ($orden === 'asc') ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="desc" <?php echo ($orden === 'desc') ? 'selected' : ''; ?>>Nombre Z-A</option>
                                </select>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <a href="index.php" class="btn btn-secondary ms-auto">Borrar filtros</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-9 col-lg-9">
                    <header class="d-sm-flex align-items-center border-bottom mb-4 pb-3">
                        <strong class="d-block py-2"><?php echo $totalRegistros; ?> Artículos encontrados </strong>
                    </header>
                    <div class="row">
                        <!--Poner aca el filtro que se puso-->
                    </div>
                    <div class="row">
                        <?php foreach ($resultado as $row) { ?>
                            <div class="col-lg-4 col-md-6 col-sm-6 d-flex">
                                <div class="card w-100 my-2 shadow-2-strong">

                                    <?php
                                    $id = $row['id'];
                                    $descuento = $row['descuento'];
                                    $precio = ($row['precio']);
                                    $precioIva = redondearPrecio($precio * 1.19);
                                    $precio_desc = $precio - (($precio * $descuento) / 100);
                                    $precio_descIva = redondearPrecio($precio_desc * 1.19);
                                    $imagen = "images/productos/$id/principal.jpg";

                                    if (!file_exists($imagen)) {
                                        $imagen = "images/no-photo.jpg";
                                    }
                                    ?>
                                    <a href="details/<?php echo $row['slug']; ?>">
                                        <img src="<?php echo $imagen; ?>" class="img-thumbnail" style="max-height: 300px">
                                    </a>

                                    <div class="card-body ">
                                        <div class="row">
                                            <?php if ($descuento > 0) { ?>
                                                <div class="col-12">
                                                    <h3 class="precio_desc" style=" display: inline-block; margin-bottom: -8px;"> <?php echo MONEDA . number_format($precio_descIva, 0, '.', ','); ?> </h3>
                                                    <h4 class="descuento" style="color: #28A745; display: inline-block; margin-bottom: -8px;"><b> <?php echo $descuento; ?>% OFF</b></h4>
                                                    <p class="text-muted" style="margin-top: -5px;"><small>IVA Incluído</small></p>
                                                </div>

                                                <div class="col-12">
                                                    <h5 class="precio" style="color: #FF0000; display: inline-block;"><del><?php echo  MONEDA . number_format($precioIva, 0, '.', '.'); ?></del></h5>
                                                </div>

                                            <?php } else { ?>
                                                <div class="col-12">
                                                    <h3 style="margin-bottom: -8px;"><?php echo MONEDA . number_format($precioIva, 0, '.', '.'); ?> </h3>
                                                    <p class="text-muted" style="margin-top: -5px;"><small>IVA Incluído</small></p>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <p class=" card-text"><?php echo $row['nombre']; ?></p>
                                    </div>

                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <a class="btn btn-success" onClick="addProducto('<?php echo $row['id']; ?>')">Agregar</a>
                                            <div class="btn-group">
                                                <a href="details/<?php echo $row['slug']; ?>" class="btn btn-primary">Detalles</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
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
            <div class="sm-shake bottom-center sm-fixed sm-button  sm-button-text sm-rounded">
                <i class="fab fa-whatsapp"></i> <!-- Icono de WhatsApp -->
                <span>&nbsp;</span>
                <span>&nbsp;</span>
                <span style="color: white;">Contáctanos por WhatsApp</span>
            </div>
        </a>

    </main>

    <?php include 'footer.php'; ?>

    <script src="<?php echo SITE_URL; ?>js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/all.min.js"></script>

    <script>
        function addProducto(id) {
            var url = 'clases/carrito.php';
            var formData = new FormData();
            formData.append('id', id);
            console.log(id)
            fetch(url, {
                    method: 'POST',
                    body: formData,
                    mode: 'cors',
                }).then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        let elemento = document.getElementById("num_cart")
                        elemento.innerHTML = data.numero;
                    } else {
                        alert("Lo sentimos.. En este momento, no hay suficientes existencias")
                    }
                })
        }

        function submitForm() {
            document.getElementById("ordenForm").submit();
        }

        function actualizarSubcategorias() {
            var categoriaId = document.getElementById('categoria').value;
            var subcategoriaSelect = document.getElementById('subcategoria');
            var subsubcategoriaSelect = document.getElementById('subsubcategoria');
            var subcategoriaContainer = document.getElementById('subcategoria-container');
            var subsubcategoriaContainer = document.getElementById('subsubcategoria-container');

            // Limpiar opciones anteriores
            subcategoriaSelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
            subsubcategoriaSelect.innerHTML = '<option value="">Selecciona una sub-subcategoría</option>';

            // Si se seleccionó una categoría
            if (categoriaId) {
                // Aquí puedes hacer una solicitud AJAX al servidor para obtener las subcategorías de la categoría seleccionada
                var subcategorias = <?php echo json_encode($categoriasArray); ?>;
                var selectedCategoria = subcategorias[categoriaId]?.subcategorias || [];

                // Agregar subcategorías al select
                selectedCategoria.forEach(function(subcategoria) {
                    var option = document.createElement('option');
                    option.value = subcategoria.id;
                    option.text = subcategoria.nombre;
                    subcategoriaSelect.appendChild(option);
                });

                // Mostrar contenedor de subcategoría
                subcategoriaContainer.style.display = 'block';
            } else {
                subcategoriaContainer.style.display = 'none';
                subsubcategoriaContainer.style.display = 'none';
            }
        }

        function actualizarSubsubcategorias() {
            var subcategoriaId = document.getElementById('subcategoria').value;
            var subsubcategoriaSelect = document.getElementById('subsubcategoria');

            // Limpiar opciones anteriores
            subsubcategoriaSelect.innerHTML = '<option value="">Selecciona una sub-subcategoría</option>';

            // Si se seleccionó una subcategoría
            if (subcategoriaId) {
                var subcategorias = <?php echo json_encode($categoriasArray); ?>;
                var subcategoria = Object.values(subcategorias)
                    .flatMap(c => c.subcategorias)
                    .find(sub => sub.id == subcategoriaId);

                var subsubcategorias = subcategoria ? subcategoria.subsubcategorias : [];

                // Agregar sub-subcategorías al select
                subsubcategorias.forEach(function(subsubcategoria) {
                    var option = document.createElement('option');
                    option.value = subsubcategoria.id;
                    option.text = subsubcategoria.nombre;
                    subsubcategoriaSelect.appendChild(option);
                });

                // Mostrar contenedor de sub-subcategoría
                subsubcategoriaContainer.style.display = 'block';
            } else {
                subsubcategoriaContainer.style.display = 'none';
            }
        }
    </script>

</body>

</html>